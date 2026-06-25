<?php
/**
 * migrate_melhorenvio_auth.php
 * Cria a tabela melhorenvio_auth no banco SQLite (linha única — lojista único).
 * Execute uma única vez: php migrate_melhorenvio_auth.php  ou  acesse via browser.
 * Remova o arquivo após uso em produção.
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS melhorenvio_auth (
            id                   INTEGER  PRIMARY KEY CHECK (id = 1),
            access_token         TEXT     DEFAULT NULL,
            refresh_token        TEXT     DEFAULT NULL,
            expires_at           DATETIME DEFAULT NULL,
            requer_reautorizacao INTEGER  NOT NULL DEFAULT 0,
            updated_at           DATETIME DEFAULT NULL
        )
    ");

    // Garante que a linha única existe (sem sobrescrever tokens já gravados)
    $db->exec("INSERT OR IGNORE INTO melhorenvio_auth (id) VALUES (1)");

    echo 'Tabela melhorenvio_auth criada/verificada com sucesso.';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro: ' . htmlspecialchars($e->getMessage());
}
