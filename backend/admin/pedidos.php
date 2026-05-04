<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$pdo    = getDB();
$filtro = $_GET['status'] ?? '';

$sql = "SELECT * FROM pedidos";
if ($filtro) $sql .= " WHERE status = " . $pdo->quote($filtro);
$sql .= " ORDER BY id DESC";

$pedidos = $pdo->query($sql)->fetchAll();

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

$statusOpcoes = ['', 'aprovado', 'pendente', 'em_analise', 'em_processamento', 'enviado', 'recusado', 'cancelado'];

layout_head('Pedidos');
?>
<style>
  .bg-purple { background-color: #6f42c1 !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="text-muted small"><?= count($pedidos) ?> pedido(s)</span>
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (array_filter($statusOpcoes) as $s): ?>
                <option value="<?= $s ?>" <?= $filtro === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filtro): ?>
            <a href="pedidos.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Comprador</th>
                    <th>E-mail</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td class="text-muted small"><?= $p['id'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nome_comprador']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($p['email_comprador']) ?></td>
                    <td>R$ <?= number_format($p['total'], 2, ',', '.') ?></td>
                    <td><span class="badge bg-<?= $statusBadge[$p['status']] ?? 'secondary' ?>"><?= $p['status'] ?></span></td>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                    <td><a href="pedido-detalhe.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum pedido encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php layout_foot(); ?>
