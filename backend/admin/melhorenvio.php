<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/melhorenvio.php';
require_once __DIR__ . '/../helpers/MelhorEnvio.php';

$status = MelhorEnvio::getStatus();

$badgeMap = [
    'ok'                  => ['success', 'fa-check-circle',    'Conectado'],
    'expira_em_breve'     => ['warning', 'fa-clock',           'Expira em breve'],
    'expirado'            => ['danger',  'fa-times-circle',    'Expirado'],
    'requer_reautorizacao'=> ['danger',  'fa-exclamation-circle', 'Reautorização necessária'],
    'nao_configurado'     => ['secondary','fa-plug',           'Não configurado'],
    'sem_tabela'          => ['danger',  'fa-database',        'Migração pendente'],
];
$badge = $badgeMap[$status['status']] ?? ['secondary', 'fa-question-circle', 'Desconhecido'];

$precisaConectar = in_array($status['status'], ['nao_configurado', 'sem_tabela', 'expirado', 'requer_reautorizacao']);

layout_head('Integrações — Melhor Envio');
?>

<div class="mb-4">
    <p class="text-muted mb-0">Gerencie a conexão OAuth2 com o Melhor Envio para cálculo de frete.</p>
</div>

<div class="row g-4">

    <!-- Card de status -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-card">
                        <div class="icon bg-<?= $badge[0] ?> bg-opacity-10 text-<?= $badge[0] ?>">
                            <i class="fas <?= $badge[1] ?>"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-semibold">Melhor Envio</div>
                        <span class="badge bg-<?= $badge[0] ?>"><?= $badge[2] ?></span>
                    </div>
                </div>

                <p class="text-muted small mb-3"><?= htmlspecialchars($status['mensagem']) ?></p>

                <?php if ($status['updated_at']): ?>
                <div class="text-muted small mb-3">
                    <i class="fas fa-history me-1"></i>
                    Última atualização: <?= date('d/m/Y H:i', strtotime($status['updated_at'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($precisaConectar): ?>
                <a href="melhorenvio-conectar.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plug me-1"></i> Conectar Melhor Envio
                </a>
                <?php else: ?>
                <a href="melhorenvio-conectar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-rotate-left me-1"></i> Reconectar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card de configuração -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Configuração atual</h6>
                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted" style="width:45%">Ambiente</td>
                        <td>
                            <?php
                            $url = MELHORENVIO_BASE_URL;
                            echo strpos($url, 'sandbox') !== false
                                ? '<span class="badge bg-warning text-dark">Sandbox</span>'
                                : '<span class="badge bg-success">Produção</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Client ID</td>
                        <td>
                            <?= MELHORENVIO_CLIENT_ID !== ''
                                ? '<span class="text-success"><i class="fas fa-check me-1"></i>Configurado</span>'
                                : '<span class="text-danger"><i class="fas fa-times me-1"></i>Não configurado</span>'
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Client Secret</td>
                        <td>
                            <?= MELHORENVIO_CLIENT_SECRET !== ''
                                ? '<span class="text-success"><i class="fas fa-check me-1"></i>Configurado</span>'
                                : '<span class="text-danger"><i class="fas fa-times me-1"></i>Não configurado</span>'
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Redirect URI</td>
                        <td class="text-break"><code style="font-size:.75rem;"><?= htmlspecialchars(MELHORENVIO_REDIRECT_URI) ?></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Scopes</td>
                        <td><code style="font-size:.75rem;"><?= htmlspecialchars(MELHORENVIO_SCOPES) ?></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">CEP de origem</td>
                        <td><code style="font-size:.75rem;"><?= htmlspecialchars(LOJA_CEP_ORIGEM) ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Instruções -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Como conectar</h6>
                <ol class="small text-muted mb-0" style="line-height:1.9">
                    <li>Acesse <strong>app-sandbox.melhorenvio.com.br/integracoes/area-dev</strong> e crie um aplicativo.</li>
                    <li>Copie o <strong>Client ID</strong> e o <strong>Client Secret</strong> para <code>backend/config/melhorenvio.php</code>.</li>
                    <li>Cadastre a redirect URI <code><?= htmlspecialchars(MELHORENVIO_REDIRECT_URI) ?></code> no aplicativo (deve ser idêntica).</li>
                    <li>Clique em <strong>"Conectar Melhor Envio"</strong> acima — você será redirecionado para autorizar o acesso.</li>
                    <li>Após autorizar, o token é salvo automaticamente e a cotação de frete passa a funcionar.</li>
                </ol>
            </div>
        </div>
    </div>

</div>

<?php layout_foot(); ?>
