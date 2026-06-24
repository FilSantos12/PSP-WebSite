<?php
/**
 * Migração idempotente: adiciona colunas de escolha de frete em order_tracking.
 * Executar uma vez via browser e apagar o arquivo após confirmar o sucesso.
 *
 * URL: http://localhost:8000/migrate_shipping_choice.php
 */

require_once __DIR__ . '/backend/config/database.php';

$pdo = getDB();

$colunas = [
    'chosen_carrier'    => "TEXT    DEFAULT NULL",
    'chosen_service'    => "TEXT    DEFAULT NULL",
    'chosen_service_id' => "INTEGER DEFAULT NULL",
    'shipping_price'    => "REAL    DEFAULT NULL",
    'shipping_deadline' => "INTEGER DEFAULT NULL",
    'destination_cep'   => "TEXT    DEFAULT NULL",
];

$info = $pdo->query("PRAGMA table_info(order_tracking)")->fetchAll(PDO::FETCH_ASSOC);
$existentes = array_column($info, 'name');

$adicionadas = [];
$ignoradas   = [];

foreach ($colunas as $coluna => $def) {
    if (in_array($coluna, $existentes)) {
        $ignoradas[] = $coluna;
    } else {
        $pdo->exec("ALTER TABLE order_tracking ADD COLUMN $coluna $def");
        $adicionadas[] = $coluna;
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== migrate_shipping_choice.php ===\n\n";

if ($adicionadas) {
    echo "Colunas ADICIONADAS:\n";
    foreach ($adicionadas as $c) echo "  + $c\n";
    echo "\n";
}
if ($ignoradas) {
    echo "Colunas já existentes (ignoradas):\n";
    foreach ($ignoradas as $c) echo "  = $c\n";
    echo "\n";
}

echo "Migração concluída. Apague este arquivo após verificar.\n";
