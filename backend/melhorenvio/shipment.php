<?php
/**
 * Serviço de emissão de etiquetas — Melhor Envio
 *
 * Fluxo obrigatório (4 etapas):
 *   meCartAdd()  → meCheckout()  → meGenerate()  → mePrint()
 *
 * ⚠️  meCheckout() DEBITA SALDO da carteira Melhor Envio.
 *     Só executa com confirmação explícita do admin e LOJA_DADOS_REAIS === true.
 *
 * Toda função lê dados do banco (PDO); nunca usa request HTTP do navegador.
 * Token: sempre via MelhorEnvio::getValidToken() (tabela melhorenvio_auth).
 */

require_once __DIR__ . '/../helpers/MelhorEnvio.php';
require_once __DIR__ . '/../config/melhorenvio.php';
require_once __DIR__ . '/../config/loja.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/status.php';

// ── Log interno ───────────────────────────────────────────────────────────────

function _meLog(string $method, string $path, int $code, string $body): void
{
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $method . ' ' . $path
          . ' → HTTP ' . $code . ' | ' . mb_substr($body, 0, 600) . PHP_EOL;
    file_put_contents($logDir . '/melhorenvio-etiqueta.log', $line, FILE_APPEND | LOCK_EX);
}

// ── Helper: busca row de tracking ─────────────────────────────────────────────

function _meGetTracking(int $pedidoId, PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = :oid LIMIT 1");
    $stmt->execute([':oid' => (string) $pedidoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// ── Helper: chama a API e loga ────────────────────────────────────────────────

function _meCall(string $method, string $path, ?array $payload = null): array
{
    $me   = new MelhorEnvio();
    $resp = $me->request($method, $path, $payload);
    _meLog($method, $path, $resp['code'], $resp['body']);
    return $resp;
}

// ─────────────────────────────────────────────────────────────────────────────
// Etapa 1 — Carrinho
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Insere o envio no carrinho Melhor Envio e persiste o ID retornado.
 * Idempotente: se melhorenvio_order_id já existe, retorna estado atual sem nova chamada.
 *
 * @throws RuntimeException em caso de dado faltante ou erro da API.
 */
function meCartAdd(int $pedidoId, PDO $pdo): array
{
    $tracking = _meGetTracking($pedidoId, $pdo);

    if (!$tracking) {
        throw new RuntimeException('Pedido #' . $pedidoId . ' não possui registro de frete/tracking.');
    }

    // ── Idempotência: não criar segundo envio ─────────────────────────────────
    if (!empty($tracking['melhorenvio_order_id'])) {
        return [
            'ok'                  => true,
            'melhorenvio_order_id' => $tracking['melhorenvio_order_id'],
            'ja_existia'          => true,
            'mensagem'            => 'Envio já está no carrinho ME (ID: ' . $tracking['melhorenvio_order_id'] . ').',
        ];
    }

    $serviceId = (int) ($tracking['chosen_service_id'] ?? 0);
    if ($serviceId <= 0) {
        throw new RuntimeException(
            'ID do serviço Melhor Envio não encontrado para este pedido. ' .
            'O cliente deve ter escolhido um serviço de frete no checkout.'
        );
    }

    // ── Busca pedido + produto ────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT p.nome_comprador, p.email_comprador, p.telefone_comprador,
               p.cep, p.endereco, p.numero, p.complemento, p.bairro, p.cidade, p.estado,
               ip.quantidade, ip.preco_unitario,
               pr.nome AS produto_nome, pr.peso, pr.largura, pr.altura, pr.comprimento
        FROM pedidos p
        JOIN itens_pedido ip ON ip.pedido_id = p.id
        JOIN produtos pr     ON pr.id = ip.produto_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $pedidoId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        throw new RuntimeException('Pedido #' . $pedidoId . ' não encontrado.');
    }

    // ── Remetente (loja.php) ──────────────────────────────────────────────────
    $from = [
        'name'        => LOJA_NOME,
        'phone'       => preg_replace('/\D/', '', LOJA_TELEFONE),
        'email'       => LOJA_EMAIL,
        'address'     => LOJA_LOGRADOURO,
        'number'      => LOJA_NUMERO,
        'complement'  => LOJA_COMPLEMENTO ?: null,
        'district'    => LOJA_BAIRRO,
        'city'        => LOJA_CIDADE,
        'state_abbr'  => LOJA_UF,
        'country_id'  => 'BR',
        'postal_code' => preg_replace('/\D/', '', LOJA_CEP),
    ];

    if (LOJA_TIPO_DOC === 'cnpj') {
        $from['company_document'] = LOJA_DOCUMENTO;
    } else {
        $from['document'] = LOJA_DOCUMENTO;
    }

    // ── Destinatário (pedido) ─────────────────────────────────────────────────
    // Em produção o Melhor Envio pode exigir o CPF/CNPJ do comprador.
    // Quando necessário, adicionar via: ALTER TABLE pedidos ADD COLUMN documento_comprador TEXT DEFAULT NULL
    // e incluir aqui: 'document' => $p['documento_comprador'] ?? null
    $to = [
        'name'        => $p['nome_comprador'],
        'phone'       => preg_replace('/\D/', '', $p['telefone_comprador'] ?? ''),
        'email'       => $p['email_comprador'],
        'address'     => $p['endereco'],
        'number'      => $p['numero'],
        'complement'  => $p['complemento'] ?: null,
        'district'    => $p['bairro'],
        'city'        => $p['cidade'],
        'state_abbr'  => $p['estado'],
        'country_id'  => 'BR',
        'postal_code' => preg_replace('/\D/', '', $p['cep'] ?? ''),
    ];

    $seguro   = (float) $p['preco_unitario'] * (int) $p['quantidade'];
    $peso     = (float) $p['peso'];

    // ── Payload do carrinho ───────────────────────────────────────────────────
    // Referência: https://docs.melhorenvio.com.br/reference/inserir-envio-no-carrinho
    $payload = [
        'service'  => $serviceId,
        'agency'   => null,
        'from'     => $from,
        'to'       => $to,
        'products' => [[
            'name'          => $p['produto_nome'],
            'quantity'      => (int) $p['quantidade'],
            'unitary_value' => (float) $p['preco_unitario'],
            'weight'        => $peso,
        ]],
        'volumes'  => [[
            'height' => (float) $p['altura'],
            'width'  => (float) $p['largura'],
            'length' => (float) $p['comprimento'],
            'weight' => $peso,
        ]],
        'options'  => [
            'insurance_value' => $seguro,
            'receipt'         => false,
            'own_hand'        => false,
            'reverse'         => false,
            'non_commercial'  => false,
        ],
    ];

    $resp = _meCall('POST', '/api/v2/me/cart', $payload);

    if ($resp['code'] !== 201 && $resp['code'] !== 200) {
        $msg = $resp['data']['message'] ?? ('Resposta inesperada: ' . $resp['body']);
        throw new RuntimeException('Erro ao adicionar ao carrinho ME (HTTP ' . $resp['code'] . '): ' . $msg);
    }

    $meOrderId = $resp['data']['id'] ?? null;
    if (!$meOrderId) {
        throw new RuntimeException('Melhor Envio não retornou o ID do envio no carrinho.');
    }

    $pdo->prepare("
        UPDATE order_tracking
        SET melhorenvio_order_id = :me_id, updated_at = :now
        WHERE order_id = :oid
    ")->execute([':me_id' => (string) $meOrderId, ':oid' => (string) $pedidoId, ':now' => date('Y-m-d H:i:s')]);

    return [
        'ok'                   => true,
        'melhorenvio_order_id' => $meOrderId,
        'ja_existia'           => false,
        'mensagem'             => 'Envio adicionado ao carrinho ME com sucesso.',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Etapa 2 — Checkout (DEBITA SALDO)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Faz checkout do envio no Melhor Envio — DEBITA SALDO da carteira.
 *
 * Bloqueado quando LOJA_DADOS_REAIS !== true.
 * Idempotente: trata graciosamente resposta de "já pago" da API.
 *
 * @throws RuntimeException se dados fictícios, sem carrinho ou erro da API.
 */
function meCheckout(int $pedidoId, PDO $pdo): array
{
    // ── Trava de produção ─────────────────────────────────────────────────────
    if (LOJA_DADOS_REAIS !== true) {
        throw new RuntimeException(
            'Checkout bloqueado: dados da loja ainda são fictícios (sandbox). ' .
            'Preencha o remetente real em backend/config/loja.php e defina LOJA_DADOS_REAIS = true.'
        );
    }

    $tracking  = _meGetTracking($pedidoId, $pdo);
    $meOrderId = $tracking['melhorenvio_order_id'] ?? null;

    if (!$meOrderId) {
        throw new RuntimeException('Adicione o envio ao carrinho ME antes de fazer checkout.');
    }

    $resp = _meCall('POST', '/api/v2/me/shipment/checkout', ['orders' => [$meOrderId]]);

    // Idempotência: ME pode retornar 4xx se envio já foi pago
    if ($resp['code'] >= 400) {
        $msg = is_array($resp['data']) ? ($resp['data']['message'] ?? $resp['body']) : $resp['body'];
        // Trata "já pago" como sucesso — evita cobrança duplicada
        if (stripos($msg, 'already') !== false || stripos($msg, 'pago') !== false || stripos($msg, 'paid') !== false) {
            return ['ok' => true, 'ja_pago' => true, 'mensagem' => 'Envio já havia sido pago anteriormente.'];
        }
        throw new RuntimeException('Erro no checkout ME (HTTP ' . $resp['code'] . '): ' . $msg);
    }

    return ['ok' => true, 'ja_pago' => false, 'mensagem' => 'Checkout realizado. Saldo debitado da carteira ME.'];
}

// ─────────────────────────────────────────────────────────────────────────────
// Etapa 3 — Geração
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Gera a etiqueta no Melhor Envio (comunica a transportadora).
 * Processo assíncrono — aguardar alguns segundos antes de imprimir.
 *
 * @throws RuntimeException se sem carrinho ou erro da API.
 */
function meGenerate(int $pedidoId, PDO $pdo): array
{
    $tracking  = _meGetTracking($pedidoId, $pdo);
    $meOrderId = $tracking['melhorenvio_order_id'] ?? null;

    if (!$meOrderId) {
        throw new RuntimeException('Adicione o envio ao carrinho e faça checkout antes de gerar.');
    }

    $resp = _meCall('POST', '/api/v2/me/shipment/generate', ['orders' => [$meOrderId]]);

    if ($resp['code'] >= 400) {
        $msg = is_array($resp['data']) ? ($resp['data']['message'] ?? $resp['body']) : $resp['body'];
        throw new RuntimeException('Erro ao gerar etiqueta ME (HTTP ' . $resp['code'] . '): ' . $msg);
    }

    return [
        'ok'       => true,
        'mensagem' => 'Etiqueta em geração. Aguarde alguns segundos e clique em "Imprimir / Baixar".',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Etapa 4 — Impressão
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Obtém o link da etiqueta, persiste no banco e tenta capturar o tracking_code.
 * Inclui delay de 5s para compensar a assincronia da geração.
 *
 * @throws RuntimeException se sem carrinho, geração pendente ou erro da API.
 */
function mePrint(int $pedidoId, PDO $pdo): array
{
    $tracking  = _meGetTracking($pedidoId, $pdo);
    $meOrderId = $tracking['melhorenvio_order_id'] ?? null;

    if (!$meOrderId) {
        throw new RuntimeException('Execute as etapas de carrinho, checkout e geração antes de imprimir.');
    }

    // Delay: geração é assíncrona — aguarda antes de pedir o link
    sleep(5);

    $resp = _meCall('POST', '/api/v2/me/shipment/print', [
        'mode'   => 'private',
        'orders' => [$meOrderId],
    ]);

    if ($resp['code'] >= 400) {
        $msg = is_array($resp['data']) ? ($resp['data']['message'] ?? $resp['body']) : $resp['body'];
        // Se a geração ainda não concluiu, orienta o admin
        if (stripos($msg, 'gerado') !== false || stripos($msg, 'generat') !== false || stripos($msg, 'process') !== false) {
            throw new RuntimeException('A etiqueta ainda está sendo gerada. Aguarde mais alguns segundos e tente novamente.');
        }
        throw new RuntimeException('Erro ao obter etiqueta ME (HTTP ' . $resp['code'] . '): ' . $msg);
    }

    $labelUrl = $resp['data']['url'] ?? null;
    if (!$labelUrl) {
        throw new RuntimeException('ME não retornou URL da etiqueta. Aguarde alguns segundos e tente novamente.');
    }

    $now = date('Y-m-d H:i:s');
    $pdo->prepare("
        UPDATE order_tracking SET label_url = :url, updated_at = :now WHERE order_id = :oid
    ")->execute([':url' => $labelUrl, ':oid' => (string) $pedidoId, ':now' => $now]);

    // Tenta capturar tracking_code imediatamente
    try {
        meTracking($pedidoId, $pdo);
    } catch (RuntimeException $e) {
        // Não fatal: o código de rastreio pode demorar mais para aparecer
    }

    return ['ok' => true, 'label_url' => $labelUrl, 'mensagem' => 'Etiqueta gerada com sucesso.'];
}

// ─────────────────────────────────────────────────────────────────────────────
// Rastreio
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Consulta o rastreio no ME e atualiza tracking_code/status no banco.
 * Guard: só promove order_tracking.status = 3 se pedido estiver em estado pago/em preparação.
 * Nunca sobrescreve pedido cancelado/recusado.
 *
 * @throws RuntimeException se sem melhorenvio_order_id.
 */
function meTracking(int $pedidoId, PDO $pdo): array
{
    $tracking  = _meGetTracking($pedidoId, $pdo);
    $meOrderId = $tracking['melhorenvio_order_id'] ?? null;

    if (!$meOrderId) {
        throw new RuntimeException('Sem melhorenvio_order_id para consultar rastreio.');
    }

    $resp = _meCall('GET', '/api/v2/me/orders/' . $meOrderId, null);

    if ($resp['code'] !== 200) {
        throw new RuntimeException('Não foi possível consultar o rastreio no ME (HTTP ' . $resp['code'] . ').');
    }

    $data         = $resp['data'];
    $trackingCode = $data['tracking'] ?? null;
    $carrierName  = $data['service']['company']['name'] ?? null;
    $now          = date('Y-m-d H:i:s');

    if ($trackingCode) {
        // Guard: só promove status se pedido está pago e em estado válido
        $stmtStatus = $pdo->prepare("SELECT status FROM pedidos WHERE id = :id LIMIT 1");
        $stmtStatus->execute([':id' => $pedidoId]);
        $pedidoStatus = $stmtStatus->fetchColumn() ?: '';

        if (statusPermiteRastreamento($pedidoStatus)) {
            $pdo->prepare("
                UPDATE order_tracking
                SET tracking_code = :code,
                    status        = 3,
                    carrier       = COALESCE(:carrier, carrier),
                    updated_at    = :now
                WHERE order_id = :oid
            ")->execute([
                ':code'    => $trackingCode,
                ':carrier' => $carrierName,
                ':oid'     => (string) $pedidoId,
                ':now'     => $now,
            ]);
        }
    }

    return [
        'ok'            => true,
        'tracking_code' => $trackingCode,
        'status_me'     => $data['status'] ?? null,
        'mensagem'      => $trackingCode
            ? 'Código de rastreio capturado: ' . $trackingCode
            : 'Rastreio consultado — código ainda não disponível.',
    ];
}
