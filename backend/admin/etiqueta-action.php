<?php
/**
 * POST /backend/admin/etiqueta-action.php
 * Endpoint AJAX para as ações de etiqueta Melhor Envio no painel admin.
 *
 * Body JSON: { "action": "cart"|"checkout"|"generate"|"print"|"tracking", "pedido_id": 42 }
 *
 * Requer sessão de admin ativa.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido.']);
    exit;
}

$action   = trim($body['action']    ?? '');
$pedidoId = (int) ($body['pedido_id'] ?? 0);

if (!in_array($action, ['cart', 'checkout', 'generate', 'print', 'tracking'], true)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida. Use: cart, checkout, generate, print ou tracking.']);
    exit;
}

if ($pedidoId <= 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'pedido_id inválido.']);
    exit;
}

require_once __DIR__ . '/../melhorenvio/shipment.php';

try {
    $pdo    = getDB();
    $result = match ($action) {
        'cart'     => meCartAdd($pedidoId, $pdo),
        'checkout' => meCheckout($pedidoId, $pdo),
        'generate' => meGenerate($pedidoId, $pdo),
        'print'    => mePrint($pedidoId, $pdo),
        'tracking' => meTracking($pedidoId, $pdo),
    };

    http_response_code(200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
