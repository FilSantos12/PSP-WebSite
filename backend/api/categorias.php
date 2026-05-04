<?php
/**
 * GET /backend/api/categorias.php
 * Retorna todas as categorias ordenadas para uso no catálogo.
 */
require_once __DIR__ . '/_core.php';

exigir_metodo('GET');

$pdo  = getDB();
$stmt = $pdo->query("SELECT slug, nome FROM categorias ORDER BY ordem ASC, nome ASC");
json_ok($stmt->fetchAll());
