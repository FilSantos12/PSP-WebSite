<?php
/**
 * GET /backend/api/produtos.php
 * Retorna todos os produtos ativos com imagens e data sheet.
 */

require_once __DIR__ . '/_core.php';

exigir_metodo('GET');

try {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT id, nome, descricao, preco, categoria, imagem, estoque, codigo_interno, datasheet, especificacao_tecnica
        FROM produtos
        WHERE ativo = 1
        ORDER BY id ASC
    ");

    $produtos = $stmt->fetchAll();

    $stmtImgs = $pdo->prepare("
        SELECT caminho, ordem, principal
        FROM produto_imagens
        WHERE produto_id = :pid
        ORDER BY ordem ASC, id ASC
    ");

    foreach ($produtos as &$p) {
        $p['id']      = (int)   $p['id'];
        $p['preco']   = (float) $p['preco'];
        $p['estoque'] = (int)   $p['estoque'];

        $stmtImgs->execute([':pid' => $p['id']]);
        $imgs = $stmtImgs->fetchAll();
        foreach ($imgs as &$img) {
            $img['principal'] = (int) $img['principal'];
        }
        $p['imagens'] = $imgs;
    }

    json_ok($produtos);

} catch (Exception $e) {
    json_erro('Erro ao buscar produtos.', 500);
}
