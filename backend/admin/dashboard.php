<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$pdo = getDB();

$totalPedidos   = (int)   $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$receitaTotal   = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE status = 'aprovado'")->fetchColumn();
$pedidosPendentes = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendente'")->fetchColumn();
$pedidosAprovados = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'aprovado'")->fetchColumn();

$ultimosPedidos = $pdo->query("
    SELECT id, nome_comprador, total, status, criado_em
    FROM pedidos ORDER BY id DESC LIMIT 8
")->fetchAll();

$estoqueBaixo = $pdo->query("
    SELECT id, nome, estoque, categoria
    FROM produtos WHERE ativo = 1 AND estoque < 5
    ORDER BY estoque ASC
")->fetchAll();

$statusBadge = [
    'aprovado'   => 'success',
    'pendente'   => 'warning',
    'em_analise' => 'info',
    'recusado'   => 'danger',
    'cancelado'  => 'secondary',
    'reembolsado'=> 'secondary',
    'contestado' => 'danger',
];

layout_head('Dashboard');
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-shopping-cart"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?= $totalPedidos ?></div>
                    <div class="text-muted small">Total de Pedidos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-success bg-opacity-10 text-success"><i class="fas fa-dollar-sign"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?= 'R$ ' . number_format($receitaTotal, 2, ',', '.') ?></div>
                    <div class="text-muted small">Receita Aprovada</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?= $pedidosPendentes ?></div>
                    <div class="text-muted small">Pedidos Pendentes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?= $pedidosAprovados ?></div>
                    <div class="text-muted small">Pedidos Aprovados</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Últimos Pedidos</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Comprador</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimosPedidos as $p): ?>
                        <tr>
                            <td class="text-muted small"><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['nome_comprador']) ?></td>
                            <td>R$ <?= number_format($p['total'], 2, ',', '.') ?></td>
                            <td><span class="badge bg-<?= $statusBadge[$p['status']] ?? 'secondary' ?>"><?= $p['status'] ?></span></td>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                            <td><a href="pedido-detalhe.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ultimosPedidos)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Nenhum pedido ainda.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold text-warning">
                <i class="fas fa-exclamation-triangle me-1"></i> Estoque Baixo
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Produto</th><th>Estoque</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($estoqueBaixo as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nome']) ?></td>
                            <td><span class="badge bg-<?= $p['estoque'] == 0 ? 'danger' : 'warning' ?> text-dark"><?= $p['estoque'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($estoqueBaixo)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Tudo em dia!</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php layout_foot(); ?>
