<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: produtos.php');
    exit;
}

$id  = (int) ($_POST['id'] ?? 0);
$pdo = getDB();

// Verifica se produto tem pedidos vinculados
$pedidos = (int) $pdo->prepare("SELECT COUNT(*) FROM itens_pedido WHERE produto_id = :id")
    ->execute([':id' => $id]) ? $pdo->query("SELECT COUNT(*) FROM itens_pedido WHERE produto_id = $id")->fetchColumn() : 0;

if ($pedidos > 0) {
    // Tem pedidos — desativa em vez de excluir
    $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = :id")->execute([':id' => $id]);
    header('Location: produtos.php?msg=Produto+desativado+(possui+pedidos+vinculados).');
} else {
    $pdo->prepare("DELETE FROM produtos WHERE id = :id")->execute([':id' => $id]);
    header('Location: produtos.php?msg=Produto+excluído+com+sucesso.');
}
exit;
