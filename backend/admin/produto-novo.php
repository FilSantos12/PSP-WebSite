<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$erros = [];
$dados = ['nome'=>'','descricao'=>'','preco'=>'','categoria'=>'','estoque'=>'','ativo'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']      = trim($_POST['nome'] ?? '');
    $dados['descricao'] = trim($_POST['descricao'] ?? '');
    $dados['preco']     = $_POST['preco'] ?? '';
    $dados['categoria'] = trim($_POST['categoria'] ?? '');
    $dados['estoque']   = $_POST['estoque'] ?? '';
    $dados['ativo']     = isset($_POST['ativo']) ? 1 : 0;

    if (!$dados['nome'])      $erros[] = 'Nome é obrigatório.';
    if (!$dados['categoria']) $erros[] = 'Categoria é obrigatória.';
    if (!is_numeric($dados['preco']) || $dados['preco'] < 0) $erros[] = 'Preço inválido.';
    if (!is_numeric($dados['estoque']) || $dados['estoque'] < 0) $erros[] = 'Estoque inválido.';

    // Upload de imagem
    $imagem = null;
    if (!empty($_FILES['imagem']['name'])) {
        $ext      = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $permitidos)) {
            $erros[] = 'Formato de imagem inválido. Use JPG, PNG ou WebP.';
        } else {
            $destino = __DIR__ . '/../../img/' . uniqid('prod_') . '.' . $ext;
            move_uploaded_file($_FILES['imagem']['tmp_name'], $destino);
            $imagem = 'img/' . basename($destino);
        }
    }

    if (empty($erros)) {
        $pdo = getDB();
        $pdo->prepare("
            INSERT INTO produtos (nome, descricao, preco, categoria, imagem, estoque, ativo)
            VALUES (:nome, :descricao, :preco, :categoria, :imagem, :estoque, :ativo)
        ")->execute([
            ':nome'      => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':preco'     => (float) $dados['preco'],
            ':categoria' => $dados['categoria'],
            ':imagem'    => $imagem,
            ':estoque'   => (int) $dados['estoque'],
            ':ativo'     => $dados['ativo'],
        ]);
        header('Location: produtos.php?msg=Produto+criado+com+sucesso.');
        exit;
    }
}

layout_head('Novo Produto');
?>

<div class="mb-3">
    <a href="produtos.php" class="text-decoration-none text-muted small">
        <i class="fas fa-arrow-left me-1"></i> Voltar para Produtos
    </a>
</div>

<?php if ($erros): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($erros as $e) echo "<li>$e</li>"; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:680px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($dados['nome']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($dados['descricao']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Preço (R$) <span class="text-danger">*</span></label>
                    <input type="number" name="preco" class="form-control" step="0.01" min="0" required value="<?= htmlspecialchars($dados['preco']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estoque <span class="text-danger">*</span></label>
                    <input type="number" name="estoque" class="form-control" min="0" required value="<?= htmlspecialchars($dados['estoque']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                    <select name="categoria" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach (['motorizacao','robotica','acesso','bluetooth'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $dados['categoria'] === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagem</label>
                    <input type="file" name="imagem" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= $dados['ativo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Produto ativo (visível no site)</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar Produto
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php layout_foot(); ?>
