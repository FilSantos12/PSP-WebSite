<?php
/**
 * POST /backend/api/webhook.php
 * Recebe notificações do Mercado Pago (SDK v3) e atualiza o status do pedido.
 */

require_once __DIR__ . '/_core.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/mercadopago.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

// MP envia GET para validação de URL
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    exit;
}

exigir_metodo('POST');

// ── Log do payload para diagnóstico ─────────────────────────────────────────
$payload = file_get_contents('php://input');
$logDir  = __DIR__ . '/../../logs';
$logFile = $logDir . '/webhook.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ' . $payload . PHP_EOL, FILE_APPEND | LOCK_EX);

// ── Processa a notificação ───────────────────────────────────────────────────
$data = json_decode($payload, true);

$tipo = $data['type'] ?? ($_GET['type'] ?? '');
$id   = $data['data']['id'] ?? ($_GET['data_id'] ?? '');

if ($tipo !== 'payment' || !$id) {
    http_response_code(200);
    exit;
}

try {
    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

    $client    = new PaymentClient();
    $pagamento = $client->get((int) $id);

    if (!$pagamento) {
        http_response_code(200);
        exit;
    }

    $pedidoId = (int) $pagamento->external_reference;

    $statusMap = [
        'approved'     => 'aprovado',
        'pending'      => 'pendente',
        'in_process'   => 'em_analise',
        'rejected'     => 'recusado',
        'cancelled'    => 'cancelado',
        'refunded'     => 'reembolsado',
        'charged_back' => 'contestado',
    ];

    $novoStatus = $statusMap[$pagamento->status] ?? 'pendente';

    $pdo = getDB();
    $pdo->prepare("UPDATE pedidos SET status = :status WHERE id = :id")
        ->execute([':status' => $novoStatus, ':id' => $pedidoId]);

} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ERRO: ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
}

http_response_code(200);
echo json_encode(['recebido' => true], JSON_UNESCAPED_UNICODE);
