<?php
/**
 * _core.php — Funções compartilhadas da API
 */

date_default_timezone_set('America/Sao_Paulo');

// ── CORS ──────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function json_ok(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_erro(string $mensagem, int $status = 400): never {
    http_response_code($status);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function exigir_metodo(string $metodo): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($metodo)) {
        json_erro('Método não permitido.', 405);
    }
}

function body_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_erro('JSON inválido no corpo da requisição.', 400);
    }
    return $data;
}

function campo(array $data, string $chave): string {
    $valor = trim($data[$chave] ?? '');
    if ($valor === '') {
        json_erro("Campo obrigatório ausente: {$chave}", 422);
    }
    return $valor;
}

// ── Database ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
