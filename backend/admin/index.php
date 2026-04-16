<?php
session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, nome, senha_hash FROM usuarios WHERE email = :email AND ativo = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha_hash'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_nome'] = $user['nome'];
            header('Location: dashboard.php');
            exit;
        }
    }
    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — PSPart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1e2a3a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 380px; }
        .brand { color: #4e9af1; font-weight: 700; font-size: 1.6rem; }
    </style>
</head>
<body>
<div class="login-card card shadow-lg border-0 p-4">
    <div class="text-center mb-4">
        <div class="brand">PSPart</div>
        <p class="text-muted small mb-0">Área Administrativa</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required autofocus
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-4">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
</div>
</body>
</html>
