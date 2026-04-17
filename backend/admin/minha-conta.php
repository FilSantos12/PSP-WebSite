<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$pdo  = getDB();
$erro = '';
$ok   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = $_POST['senha_atual']      ?? '';
    $novaSenha  = $_POST['nova_senha']       ?? '';
    $confirmar  = $_POST['confirmar_senha']  ?? '';

    $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $user = $stmt->fetch();

    if (!password_verify($senhaAtual, $user['senha_hash'])) {
        $erro = 'Senha atual incorreta.';
    } elseif (strlen($novaSenha) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($novaSenha !== $confirmar) {
        $erro = 'A confirmação não coincide com a nova senha.';
    } else {
        $pdo->prepare("UPDATE usuarios SET senha_hash = :hash WHERE id = :id")
            ->execute([
                ':hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
                ':id'   => $_SESSION['admin_id'],
            ]);
        $ok = true;
    }
}

layout_head('Minha Conta');
?>

<div class="card shadow-sm" style="max-width:480px">
    <div class="card-header bg-white fw-semibold">Alterar Senha</div>
    <div class="card-body">

        <?php if ($ok): ?>
            <div class="alert alert-success">Senha alterada com sucesso!</div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Senha atual</label>
                <input type="password" name="senha_atual" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Nova senha <span class="text-muted small">(mín. 8 caracteres)</span></label>
                <input type="password" name="nova_senha" class="form-control" required minlength="8">
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar nova senha</label>
                <input type="password" name="confirmar_senha" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary w-100">Salvar Nova Senha</button>
        </form>
    </div>
</div>

<?php layout_foot(); ?>
