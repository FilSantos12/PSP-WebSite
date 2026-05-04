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
           pr.nome AS produto_nome, pr.imagem
    FROM itens_pedido ip
    JOIN produtos pr ON pr.id = ip.produto_id
    WHERE ip.pedido_id = :id
");
$itens->execute([':id' => $id]);
$itens = $itens->fetchAll(PDO::FETCH_ASSOC);

$statusValidos = ['pendente','aprovado','em_analise','recusado','cancelado','reembolsado','contestado','em_processamento','enviado'];
$mensagemSucesso = '';

// Atualização de status manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $novoStatus   = $_POST['status'];
    $novoRastreio = trim($_POST['codigo_rastreio'] ?? '');

    if (in_array($novoStatus, $statusValidos)) {
        $rastreioAnterior = $pedido['codigo_rastreio'] ?? '';

        $pdo->prepare("UPDATE pedidos SET status = :status, codigo_rastreio = :rastreio WHERE id = :id")
            ->execute([':status' => $novoStatus, ':rastreio' => $novoRastreio ?: null, ':id' => $id]);

        $pedido['status']          = $novoStatus;
        $pedido['codigo_rastreio'] = $novoRastreio;

        // Dispara e-mail apenas na primeira vez que o código é inserido com status "enviado"
        if ($novoStatus === 'enviado' && $novoRastreio !== '' && empty($rastreioAnterior)) {
            emailPedidoEnviado($pedido, $itens, $pedido['token_acompanhamento'] ?? '', $novoRastreio);
            $mensagemSucesso = 'Status atualizado e e-mail de rastreio enviado ao comprador.';
        } else {
            $mensagemSucesso = 'Status atualizado com sucesso.';
        }
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
                    <div class="d-flex gap-2 align-items-center mb-3">
                        <select name="status" id="statusSelect" class="form-select form-select-sm" onchange="toggleRastreio()">
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
                                        'enviado'          => 'Enviado',
                                        default            => ucfirst($s),
                                    } ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Atualizar</button>
                    </div>
                    <div id="rastreioField" style="display:<?= $pedido['status'] === 'enviado' ? 'block' : 'none' ?>;">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="fas fa-truck me-1 text-primary"></i>Código de Rastreio (Correios)
                        </label>
                        <input type="text" name="codigo_rastreio" class="form-control form-control-sm font-monospace text-uppercase"
                               placeholder="Ex: AA123456789BR"
                               value="<?= htmlspecialchars($pedido['codigo_rastreio'] ?? '') ?>"
                               maxlength="13" oninput="this.value=this.value.toUpperCase()">
                        <?php if (empty($pedido['codigo_rastreio'])): ?>
                        <div class="form-text text-warning small">
                            <i class="fas fa-info-circle me-1"></i>Ao salvar com código pela primeira vez, um e-mail será enviado automaticamente ao comprador.
                        </div>
                        <?php else: ?>
                        <div class="form-text text-muted small">
                            Código já enviado ao comprador. Alterar não reenvia e-mail.
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
                <script>
                function toggleRastreio() {
                    const s = document.getElementById('statusSelect').value;
                    document.getElementById('rastreioField').style.display = s === 'enviado' ? 'block' : 'none';
                }
                </script>
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
                                    <?= htmlspecialchars($item['produto_nome']) ?>
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
