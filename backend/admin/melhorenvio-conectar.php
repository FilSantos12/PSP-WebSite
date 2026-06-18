<?php
/**
 * Inicia o fluxo OAuth2 com o Melhor Envio.
 * Gera state CSRF, armazena na sessão e redireciona para /oauth/authorize.
 * Acesso restrito ao admin.
 */

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/melhorenvio.php';

if (empty(MELHORENVIO_CLIENT_ID) || empty(MELHORENVIO_CLIENT_SECRET)) {
    die('CLIENT_ID e CLIENT_SECRET não configurados em backend/config/melhorenvio.php.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['me_oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => MELHORENVIO_CLIENT_ID,
    'redirect_uri'  => MELHORENVIO_REDIRECT_URI,
    'response_type' => 'code',
    'state'         => $state,
    'scope'         => MELHORENVIO_SCOPES,
]);

header('Location: ' . MELHORENVIO_BASE_URL . '/oauth/authorize?' . $params);
exit;
