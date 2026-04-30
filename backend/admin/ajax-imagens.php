<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$pdo    = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function ajaxOk(mixed $data = null): void {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function ajaxErro(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'erro' => $msg]);
    exit;
}

// ── GET: listar imagens de um produto ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $produtoId = (int) ($_GET['produto_id'] ?? 0);
    if (!$produtoId) ajaxErro('produto_id inválido');

    $stmt = $pdo->prepare("
        SELECT id, caminho, ordem, principal
        FROM produto_imagens
        WHERE produto_id = :pid
        ORDER BY ordem ASC, id ASC
    ");
    $stmt->execute([':pid' => $produtoId]);
    $imgs = $stmt->fetchAll();
    foreach ($imgs as &$img) {
        $img['id']        = (int) $img['id'];
        $img['ordem']     = (int) $img['ordem'];
        $img['principal'] = (int) $img['principal'];
    }
    ajaxOk($imgs);
}

// ── POST: ações ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Upload de nova imagem
    if ($action === 'upload') {
        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        if (!$produtoId) ajaxErro('produto_id inválido');

        $existe = $pdo->prepare("SELECT id FROM produtos WHERE id = :id");
        $existe->execute([':id' => $produtoId]);
        if (!$existe->fetch()) ajaxErro('Produto não encontrado', 404);

        if (empty($_FILES['imagem']['name']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            ajaxErro('Nenhum arquivo enviado ou erro no upload');
        }

        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            ajaxErro('Imagem muito grande. Máximo: 5 MB.');
        }

        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            ajaxErro('Formato inválido. Use JPG, PNG ou WebP.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['imagem']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            ajaxErro('Arquivo não é uma imagem válida.');
        }

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produto_imagens WHERE produto_id = :pid");
        $stmtCount->execute([':pid' => $produtoId]);
        $total = (int) $stmtCount->fetchColumn();

        $stmtOrdem = $pdo->prepare("SELECT COALESCE(MAX(ordem), -1) + 1 FROM produto_imagens WHERE produto_id = :pid");
        $stmtOrdem->execute([':pid' => $produtoId]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $nomeArquivo = 'prod_' . $produtoId . '_' . uniqid() . '.' . $ext;
        $destino     = __DIR__ . '/../../img/' . $nomeArquivo;

        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            ajaxErro('Erro ao salvar arquivo no servidor.');
        }

        $caminho   = 'img/' . $nomeArquivo;
        $principal = ($total === 0) ? 1 : 0;

        $stmtIns = $pdo->prepare("
            INSERT INTO produto_imagens (produto_id, caminho, ordem, principal)
            VALUES (:pid, :caminho, :ordem, :principal)
        ");
        $stmtIns->execute([
            ':pid'       => $produtoId,
            ':caminho'   => $caminho,
            ':ordem'     => $ordem,
            ':principal' => $principal,
        ]);

        ajaxOk([
            'id'        => (int) $pdo->lastInsertId(),
            'caminho'   => $caminho,
            'ordem'     => $ordem,
            'principal' => $principal,
        ]);
    }

    // Definir imagem principal
    if ($action === 'set-principal') {
        $imgId     = (int) ($_POST['img_id'] ?? 0);
        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        if (!$imgId || !$produtoId) ajaxErro('Parâmetros inválidos');

        $pdo->prepare("UPDATE produto_imagens SET principal = 0 WHERE produto_id = :pid")
            ->execute([':pid' => $produtoId]);
        $pdo->prepare("UPDATE produto_imagens SET principal = 1 WHERE id = :id AND produto_id = :pid")
            ->execute([':id' => $imgId, ':pid' => $produtoId]);

        ajaxOk(null);
    }

    // Reordenar (recebe ids[] na ordem desejada)
    if ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) ajaxErro('ids inválido');

        $stmt = $pdo->prepare("UPDATE produto_imagens SET ordem = :ordem WHERE id = :id");
        foreach ($ids as $index => $id) {
            $stmt->execute([':ordem' => (int) $index, ':id' => (int) $id]);
        }

        ajaxOk(null);
    }

    // Deletar imagem
    if ($action === 'delete') {
        $imgId     = (int) ($_POST['img_id'] ?? 0);
        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        if (!$imgId || !$produtoId) ajaxErro('Parâmetros inválidos');

        $stmtImg = $pdo->prepare("SELECT * FROM produto_imagens WHERE id = :id AND produto_id = :pid");
        $stmtImg->execute([':id' => $imgId, ':pid' => $produtoId]);
        $img = $stmtImg->fetch();
        if (!$img) ajaxErro('Imagem não encontrada', 404);

        $wasPrincipal = (bool) $img['principal'];

        $pdo->prepare("DELETE FROM produto_imagens WHERE id = :id")->execute([':id' => $imgId]);

        $filePath = __DIR__ . '/../../' . $img['caminho'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if ($wasPrincipal) {
            $proxima = $pdo->prepare("SELECT id FROM produto_imagens WHERE produto_id = :pid ORDER BY ordem ASC, id ASC LIMIT 1");
            $proxima->execute([':pid' => $produtoId]);
            $prox = $proxima->fetch();
            if ($prox) {
                $pdo->prepare("UPDATE produto_imagens SET principal = 1 WHERE id = :id")
                    ->execute([':id' => $prox['id']]);
            }
        }

        ajaxOk(null);
    }

    ajaxErro('Ação inválida');
}

ajaxErro('Método não permitido', 405);
