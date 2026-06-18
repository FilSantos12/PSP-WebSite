<?php
/**
 * Callback OAuth2 do Melhor Envio.
 * Valida state CSRF, troca o code por token e persiste na tabela melhorenvio_auth.
 * Acesso restrito ao admin.
 */

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/melhorenvio.php';

$erro   = '';
$sucesso = false;

$code  = trim($_GET['code']  ?? '');
$state = trim($_GET['state'] ?? '');
$errorParam = trim($_GET['error'] ?? '');

// Usuário negou acesso no painel do Melhor Envio
if ($errorParam !== '') {
    $erro = 'Autorização negada pelo Melhor Envio: ' . htmlspecialchars($errorParam);
}

// Valida state CSRF
if (!$erro && ($state === '' || $state !== ($_SESSION['me_oauth_state'] ?? ''))) {
    $erro = 'Falha de segurança: state inválido. Tente novamente.';
}
unset($_SESSION['me_oauth_state']);

// Troca code por token
if (!$erro && $code !== '') {
    $ch = curl_init(MELHORENVIO_BASE_URL . '/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: ' . MELHORENVIO_USER_AGENT,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'grant_type'    => 'authorization_code',
            'client_id'     => MELHORENVIO_CLIENT_ID,
            'client_secret' => MELHORENVIO_CLIENT_SECRET,
            'redirect_uri'  => MELHORENVIO_REDIRECT_URI,
            'code'          => $code,
        ]),
    ]);

    $body    = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false || $curlErr !== '') {
        $erro = 'Falha de conexão com o Melhor Envio: ' . htmlspecialchars($curlErr);
    } elseif ($httpCode !== 200) {
        $data = json_decode($body, true);
        $msg  = $data['message'] ?? $data['error'] ?? 'Erro HTTP ' . $httpCode;
        $erro = 'O Melhor Envio recusou a troca do código: ' . htmlspecialchars($msg);
    } else {
        $data = json_decode($body, true);
        if (empty($data['access_token'])) {
            $erro = 'Resposta inesperada do Melhor Envio (sem access_token).';
        } else {
            $accessToken  = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? '';
            $expiresIn    = (int) ($data['expires_in'] ?? 2592000); // 30 dias padrão
            $expiresAt    = date('Y-m-d H:i:s', time() + $expiresIn);
            $now          = date('Y-m-d H:i:s');

            try {
                $pdo = getDB();
                $pdo->prepare("
                    INSERT INTO melhorenvio_auth (id, access_token, refresh_token, expires_at, requer_reautorizacao, updated_at)
                    VALUES (1, :at, :rt, :ea, 0, :now)
                    ON CONFLICT(id) DO UPDATE SET
                        access_token         = :at,
                        refresh_token        = :rt,
                        expires_at           = :ea,
                        requer_reautorizacao = 0,
                        updated_at           = :now
                ")->execute([':at' => $accessToken, ':rt' => $refreshToken, ':ea' => $expiresAt, ':now' => $now]);

                $sucesso = true;
            } catch (Exception $e) {
                $erro = 'Erro ao salvar token no banco: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
} elseif (!$erro) {
    $erro = 'Código de autorização ausente na resposta do Melhor Envio.';
}

layout_head('Melhor Envio — Resultado da Conexão');
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <?php if ($sucesso): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
                </div>
                <h4 class="fw-bold mb-2">Integração conectada com sucesso!</h4>
                <p class="text-muted mb-4">
                    O Melhor Envio está autenticado. A cotação de frete já está funcionando.
                </p>
                <a href="melhorenvio.php" class="btn btn-primary">
                    <i class="fas fa-plug me-1"></i> Ver status da integração
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm border-danger">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-times-circle text-danger" style="font-size:3rem;"></i>
                </div>
                <h4 class="fw-bold mb-2">Falha na conexão</h4>
                <p class="text-muted mb-3"><?= $erro ?></p>
                <a href="melhorenvio-conectar.php" class="btn btn-primary me-2">
                    <i class="fas fa-rotate-left me-1"></i> Tentar novamente
                </a>
                <a href="melhorenvio.php" class="btn btn-outline-secondary">
                    Voltar
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php layout_foot(); ?>
