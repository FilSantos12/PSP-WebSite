<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../helpers/email.php';

$pdo = getDB();
$id  = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = :id");
$stmt->execute([':id' => $id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

$itens = $pdo->prepare("
    SELECT ip.quantidade, ip.preco_unitario,
           pr.nome AS produto_nome, pr.imagem, pr.codigo_interno
    FROM itens_pedido ip
    JOIN produtos pr ON pr.id = ip.produto_id
    WHERE ip.pedido_id = :id
");
$itens->execute([':id' => $id]);
$itens = $itens->fetchAll(PDO::FETCH_ASSOC);

$statusValidos   = ['pendente','aprovado','em_analise','recusado','cancelado','reembolsado','contestado','em_processamento'];
$mensagemSucesso = '';
$mensagemFicha   = '';
$tipoFicha       = '';

// Envio da Ficha de Separação por e-mail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enviar_ficha') {
    $host    = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $isLocal = str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
    emailFichaSeparacao($pedido, $itens);
    if ($isLocal) {
        $mensagemFicha = 'Em ambiente local o e-mail não é enviado — registrado no log.';
        $tipoFicha     = 'warning';
    } else {
        $mensagemFicha = 'Ficha de separação enviada com sucesso para o setor interno.';
        $tipoFicha     = 'success';
    }
}

// Atualização de status manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $novoStatus = $_POST['status'];

    if (in_array($novoStatus, $statusValidos)) {
        $pdo->prepare("UPDATE pedidos SET status = :status WHERE id = :id")
            ->execute([':status' => $novoStatus, ':id' => $id]);

        $pedido['status'] = $novoStatus;
        $mensagemSucesso  = 'Status atualizado com sucesso.';
    }
}

$statusBadge = [
    'aprovado'         => 'success',
    'pendente'         => 'warning',
    'em_analise'       => 'info',
    'recusado'         => 'danger',
    'cancelado'        => 'secondary',
    'reembolsado'      => 'secondary',
    'contestado'       => 'danger',
    'em_processamento' => 'purple',
    'enviado'          => 'primary',
];

layout_head('Pedido #' . $id);
?>
<style>
  .bg-purple { background-color: #6f42c1 !important; }
</style>

<div class="mb-3">
    <a href="pedidos.php" class="text-decoration-none text-muted small">
        <i class="fas fa-arrow-left me-1"></i> Voltar para Pedidos
    </a>
</div>

<?php if ($mensagemSucesso): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensagemSucesso) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($mensagemFicha): ?>
<div class="alert alert-<?= $tipoFicha ?> alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-<?= $tipoFicha === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i><?= htmlspecialchars($mensagemFicha) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Dados do comprador -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Dados do Comprador</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4 text-muted">Nome</dt>
                    <dd class="col-8"><?= htmlspecialchars($pedido['nome_comprador']) ?></dd>
                    <dt class="col-4 text-muted">E-mail</dt>
                    <dd class="col-8"><?= htmlspecialchars($pedido['email_comprador']) ?></dd>
                    <dt class="col-4 text-muted">Telefone</dt>
                    <dd class="col-8"><?= htmlspecialchars($pedido['telefone_comprador']) ?></dd>
                    <?php if ($pedido['cep']): ?>
                    <dt class="col-4 text-muted">Endereço</dt>
                    <dd class="col-8">
                        <?= htmlspecialchars($pedido['endereco'] . ($pedido['numero'] ? ', ' . $pedido['numero'] : '')) ?>
                        <?php if ($pedido['complemento']): ?> — <?= htmlspecialchars($pedido['complemento']) ?><?php endif; ?><br>
                        <?= htmlspecialchars($pedido['bairro']) ?> — <?= htmlspecialchars($pedido['cidade']) ?>/<?= htmlspecialchars($pedido['estado']) ?><br>
                        CEP: <?= htmlspecialchars($pedido['cep']) ?>
                    </dd>
                    <?php endif; ?>
                    <dt class="col-4 text-muted">Data</dt>
                    <dd class="col-8"><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></dd>
                    <?php if ($pedido['mp_preferencia_id']): ?>
                    <dt class="col-4 text-muted">MP ID</dt>
                    <dd class="col-8 small text-truncate"><?= htmlspecialchars($pedido['mp_preferencia_id']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Status e total -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Status do Pedido</div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-<?= $statusBadge[$pedido['status']] ?? 'secondary' ?> fs-6">
                        <?= $pedido['status'] ?>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-success mb-3">
                    R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
                </div>
                <form method="POST">
                    <div class="d-flex gap-2 align-items-center">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statusValidos as $s): ?>
                                <option value="<?= $s ?>" <?= $pedido['status'] === $s ? 'selected' : '' ?>>
                                    <?= match($s) {
                                        'pendente'         => 'Pendente',
                                        'aprovado'         => 'Aprovado',
                                        'em_analise'       => 'Em Análise',
                                        'recusado'         => 'Recusado',
                                        'cancelado'        => 'Cancelado',
                                        'reembolsado'      => 'Reembolsado',
                                        'contestado'       => 'Contestado',
                                        'em_processamento' => 'Em Processamento',
                                        default            => ucfirst($s),
                                    } ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Atualizar</button>
                    </div>
                    <div class="form-text text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Código de rastreio e envio gerenciados em
                        <a href="tracking-admin.php" class="text-decoration-none">Rastreamento</a>.
                    </div>
                </form>

                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="pedido-ficha.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-print me-1"></i> Imprimir Ficha
                    </a>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="enviar_ficha">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-envelope me-1"></i> Enviar Ficha por E-mail
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do pedido -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Itens do Pedido</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unitário</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($item['imagem']): ?>
                                        <img src="/<?= htmlspecialchars($item['imagem']) ?>" width="40" height="40"
                                             style="object-fit:cover;border-radius:6px;">
                                    <?php endif; ?>
                                    <div>
                                        <?= htmlspecialchars($item['produto_nome']) ?>
                                        <div class="small text-muted" style="font-family:monospace;font-size:.75rem;">
                                            <?= !empty($item['codigo_interno']) ? htmlspecialchars($item['codigo_interno']) : '—' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="fw-bold">R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php layout_foot(); ?>
