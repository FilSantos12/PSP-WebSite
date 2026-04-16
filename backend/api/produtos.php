<?php
/**
 * GET /backend/api/produtos.php
 * Retorna todos os produtos ativos.
 */

require_once __DIR__ . '/_core.php';

exigir_metodo('GET');

try {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT id, nome, descricao, preco, categoria, imagem, estoque
        FROM produtos
        WHERE ativo = 1
        ORDER BY id ASC
    ");

    $produtos = $stmt->fetchAll();

    // Garante tipos corretos para o frontend
    foreach ($produtos as &$p) {
        $p['id']      = (int)   $p['id'];
        $p['preco']   = (float) $p['preco'];
        $p['estoque'] = (int)   $p['estoque'];
    }

    json_ok($produtos);

} catch (Exception $e) {
    json_erro('Erro ao buscar produtos.', 500);
}
