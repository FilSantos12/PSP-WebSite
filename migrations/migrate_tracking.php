<?php
/**
 * migrate_tracking.php
 * Cria a tabela order_tracking no banco SQLite.
 * Execute uma única vez: php migrate_tracking.php  ou  acesse via browser.
 * Remova o arquivo após uso em produção.
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS order_tracking (
            id            INTEGER  PRIMARY KEY AUTOINCREMENT,
            order_id      TEXT     NOT NULL UNIQUE,
            status        INTEGER  NOT NULL DEFAULT 0,
            tracking_code TEXT     DEFAULT NULL,
            carrier       TEXT     DEFAULT NULL,
            notes         TEXT     DEFAULT NULL,
            updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Índice para lookup por order_id (já coberto pelo UNIQUE, mas explícito)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_order_tracking_order_id ON order_tracking(order_id)");

    echo 'Tabela order_tracking criada/verificada com sucesso.';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro: ' . htmlspecialchars($e->getMessage());
}
