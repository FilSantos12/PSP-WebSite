<?php
/**
 * POST /backend/api/frete.php
 * Proxy de cotação de frete via Melhor Envio.
 *
 * Body JSON esperado:
 * { "produto_id": 1, "cep_destino": "01310100" }
 *
 * Resposta de sucesso:
 * {
 *   "ok": true,
 *   "cep": "01310-100",
 *   "servicos": [
 *     { "id": 1, "nome": "PAC", "transportadora": "Correios",
 *       "logo": "https://...", "preco": 23.90, "prazo_min": 5, "prazo_max": 8 }
 *   ],
 *   "cache": false
 * }
 */

require_once __DIR__ . '/_core.php';
require_once __DIR__ . '/../config/melhorenvio.php';
require_once __DIR__ . '/../helpers/MelhorEnvio.php';

exigir_metodo('POST');

$body      = body_json();
$produtoId = (int) ($body['produto_id'] ?? 0);
$cepRaw    = preg_replace('/\D/', '', $body['cep_destino'] ?? '');

if ($produtoId <= 0)        json_erro('produto_id inválido.', 422);
if (strlen($cepRaw) !== 8)  json_erro('CEP inválido. Informe 8 dígitos.', 422);

define('FRETE_CACHE_TTL', 12 * 3600);

try {
    $pdo = getDB();

    // Busca produto com dimensões
    $stmt = $pdo->prepare("
        SELECT id, nome, preco, peso, largura, altura, comprimento
        FROM produtos
        WHERE id = :id AND ativo = 1
    ");
    $stmt->execute([':id' => $produtoId]);
    $produto = $stmt->fetch();

    if (!$produto) json_erro('Produto não encontrado.', 404);

    // Verifica se dimensões foram preenchidas
    if ((float) $produto['peso'] <= 0) {
        json_erro('Produto sem dimensões cadastradas. Configure peso e dimensões no admin.', 422);
    }

    // Consulta cache
    $cacheKey = md5('v1:' . $produtoId . ':' . $cepRaw);

    $cStmt = $pdo->prepare("SELECT payload, criado_em FROM cache_cotacoes WHERE cache_key = :k");
    $cStmt->execute([':k' => $cacheKey]);
    $hit = $cStmt->fetch();

    if ($hit && (time() - strtotime($hit['criado_em'])) < FRETE_CACHE_TTL) {
        $cached          = json_decode($hit['payload'], true);
        $cached['cache'] = true;
        json_ok($cached);
    }

    // Verifica integração antes de chamar a API
    $meStatus = MelhorEnvio::getStatus();
    if (!in_array($meStatus['status'], ['ok', 'expira_em_breve'])) {
        json_erro('Cálculo de frete temporariamente indisponível.', 503);
    }

    // Chama API do Melhor Envio via helper
    $me       = new MelhorEnvio();
    $servicos = $me->calcularFrete($produto, $cepRaw);

    if (empty($servicos)) {
        json_erro('Nenhum serviço de entrega disponível para este CEP.', 422);
    }

    $resposta = [
        'ok'       => true,
        'cep'      => substr($cepRaw, 0, 5) . '-' . substr($cepRaw, 5),
        'servicos' => $servicos,
        'cache'    => false,
    ];

    // Grava no cache (INSERT ou UPDATE)
    $pdo->prepare("
        INSERT INTO cache_cotacoes (cache_key, payload, criado_em)
        VALUES (:k, :p, :t)
        ON CONFLICT(cache_key) DO UPDATE SET payload = :p, criado_em = :t
    ")->execute([
        ':k' => $cacheKey,
        ':p' => json_encode($resposta, JSON_UNESCAPED_UNICODE),
        ':t' => date('Y-m-d H:i:s'),
    ]);

    json_ok($resposta);

} catch (RuntimeException $e) {
    if ($e->getMessage() === 'integracao_nao_conectada') {
        json_erro('Cálculo de frete temporariamente indisponível.', 503);
    }
    json_erro($e->getMessage(), 502);
} catch (Exception $e) {
    json_erro('Erro ao calcular frete.', 500);
}
