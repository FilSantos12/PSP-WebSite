<?php
/**
 * POST /backend/api/pedidos.php
 * Cria um novo pedido com seus itens.
 *
 * Body JSON esperado:
 * {
 *   "nome_comprador":     "João Silva",
 *   "email_comprador":    "joao@email.com",
 *   "telefone_comprador": "11999999999",
 *   "produto_id":         1,
 *   "quantidade":         2
 * }
 */

require_once __DIR__ . '/_core.php';

exigir_metodo('POST');

$body = body_json();

$nome      = campo($body, 'nome_comprador');
$email     = campo($body, 'email_comprador');
$telefone  = campo($body, 'telefone_comprador');
$produtoId = (int) ($body['produto_id'] ?? 0);
$quantidade = (int) ($body['quantidade'] ?? 0);

if ($produtoId <= 0)  json_erro('produto_id inválido.', 422);
if ($quantidade <= 0) json_erro('quantidade deve ser maior que zero.', 422);

// Valida formato de e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_erro('E-mail inválido.', 422);
}

try {
    $pdo = getDB();

    // Busca o produto para obter preço atual
    $stmt = $pdo->prepare("SELECT id, preco, estoque, ativo FROM produtos WHERE id = :id");
    $stmt->execute([':id' => $produtoId]);
    $produto = $stmt->fetch();

    if (!$produto) {
        json_erro('Produto não encontrado.', 404);
    }
    if (!$produto['ativo']) {
        json_erro('Produto indisponível.', 422);
    }
    if ($produto['estoque'] < $quantidade) {
        json_erro('Estoque insuficiente.', 422);
    }

    $precoUnitario = (float) $produto['preco'];
    $total         = $precoUnitario * $quantidade;

    $pdo->beginTransaction();

    // Insere o pedido
    $stmt = $pdo->prepare("
        INSERT INTO pedidos (nome_comprador, email_comprador, telefone_comprador, total, status)
        VALUES (:nome, :email, :telefone, :total, 'pendente')
    ");
    $stmt->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':telefone' => $telefone,
        ':total'    => $total,
    ]);
    $pedidoId = (int) $pdo->lastInsertId();

    // Insere o item do pedido
    $stmt = $pdo->prepare("
        INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
        VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario)
    ");
    $stmt->execute([
        ':pedido_id'      => $pedidoId,
        ':produto_id'     => $produtoId,
        ':quantidade'     => $quantidade,
        ':preco_unitario' => $precoUnitario,
    ]);

    $pdo->commit();

    json_ok([
        'pedido_id' => $pedidoId,
        'total'     => $total,
        'status'    => 'pendente',
    ], 201);

} catch (Exception $e) {
    $pdo->inTransaction() && $pdo->rollBack();
    json_erro('Erro ao criar pedido.', 500);
}
