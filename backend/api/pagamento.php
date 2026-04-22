<?php
/**
 * POST /backend/api/pagamento.php
 * Cria uma preference no Mercado Pago (SDK v3) e retorna o init_point para redirect.
 *
 * Body JSON esperado:
 * { "pedido_id": 1 }
 */

require_once __DIR__ . '/_core.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/mercadopago.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

exigir_metodo('POST');

$body     = body_json();
$pedidoId = (int) ($body['pedido_id'] ?? 0);

if ($pedidoId <= 0) {
    json_erro('pedido_id inválido.', 422);
}

try {
    $pdo = getDB();

    // Busca o pedido com seus itens
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome_comprador, p.email_comprador, p.total,
               ip.quantidade, ip.preco_unitario,
               pr.nome AS produto_nome
        FROM pedidos p
        JOIN itens_pedido ip ON ip.pedido_id = p.id
        JOIN produtos pr     ON pr.id = ip.produto_id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $pedidoId]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        json_erro('Pedido não encontrado.', 404);
    }

    // ── Configura o SDK v3 ───────────────────────────────────────────────────
    MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

    // ── Cria a preference ────────────────────────────────────────────────────
    $client = new PreferenceClient();

    $preference = $client->create([
        'items' => [
            [
                'id'          => (string) $pedidoId,
                'title'       => $pedido['produto_nome'],
                'quantity'    => (int)   $pedido['quantidade'],
                'unit_price'  => (float) $pedido['preco_unitario'],
                'currency_id' => 'BRL',
            ],
        ],
        'payer' => [
            'email' => $pedido['email_comprador'],
            'name'  => $pedido['nome_comprador'],
        ],
        'back_urls' => [
            'success' => MP_BASE_URL . '/?pagamento=aprovado&pedido=' . $pedidoId,
            'failure' => MP_BASE_URL . '/?pagamento=recusado&pedido='  . $pedidoId,
            'pending' => MP_BASE_URL . '/?pagamento=pendente&pedido='  . $pedidoId,
        ],
        'payment_methods' => [
            'excluded_payment_types' => [],  // nenhum método excluído — PIX, boleto, débito e crédito disponíveis
            'installments'           => 12,  // máximo de parcelas no cartão
        ],
        'external_reference'   => (string) $pedidoId,
        'notification_url'     => MP_BASE_URL . '/backend/api/webhook.php',
    ]);

    // Salva o preference_id no pedido para rastreamento
    $pdo->prepare("UPDATE pedidos SET mp_preferencia_id = :mp_id WHERE id = :id")
        ->execute([':mp_id' => $preference->id, ':id' => $pedidoId]);

    json_ok([
        'preference_id' => $preference->id,
        'init_point'    => $preference->init_point,        // produção
        'sandbox_url'   => $preference->sandbox_init_point, // testes
    ]);

} catch (Exception $e) {
    json_erro('Erro ao processar pagamento: ' . $e->getMessage(), 500);
}
