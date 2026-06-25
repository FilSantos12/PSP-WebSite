<?php
/**
 * Migração: cria tabela categorias e popula com as 4 categorias iniciais.
 * Acesse uma vez em http://localhost:8000/migrate-categorias.php e delete o arquivo.
 */
require_once __DIR__ . '/backend/config/database.php';

$pdo = getDB();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS categorias (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        slug      TEXT NOT NULL UNIQUE,
        nome      TEXT NOT NULL,
        ordem     INTEGER DEFAULT 0,
        criado_em TEXT DEFAULT (datetime('now'))
    )
");

$count = (int) $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();

if ($count === 0) {
    $ins = $pdo->prepare("INSERT OR IGNORE INTO categorias (slug, nome, ordem) VALUES (?,?,?)");
    foreach ([
        ['motorizacao', 'Motorização',       1],
        ['robotica',    'Robótica',           2],
        ['acesso',      'Controle de Acesso', 3],
        ['bluetooth',   'Bluetooth',          4],
    ] as [$s, $n, $o]) {
        $ins->execute([$s, $n, $o]);
    }
    echo '<p style="font-family:sans-serif;color:green;font-size:1.2rem">✓ Tabela <b>categorias</b> criada e populada com sucesso.</p>';
} else {
    echo "<p style='font-family:sans-serif;color:orange;font-size:1.2rem'>Já existem {$count} categorias — nenhuma alteração feita.</p>";
}

echo '<p style="font-family:sans-serif"><a href="/backend/admin/">Ir para o Admin</a> · Pode apagar este arquivo agora.</p>';
