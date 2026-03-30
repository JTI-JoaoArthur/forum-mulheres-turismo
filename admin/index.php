<?php
/**
 * Login — Página de autenticação do painel administrativo
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::startSession();

// Se já logado, redireciona ao dashboard
if (Auth::check()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Se não existe usuário, redireciona ao setup
$userCount = Database::fetchOne("SELECT COUNT(*) as total FROM users")['total'];
if ($userCount === 0) {
    header('Location: /admin/setup.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = Auth::attempt($email, $password);

    if ($result['success']) {
        header('Location: /admin/dashboard.php');
        exit;
    }

    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login — Painel Administrativo</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="admin-login">
<div class="container">
    <div class="login-card">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center brand-color mb-1">Painel Administrativo</h3>
                <p class="text-center text-muted mb-4">Fórum de Mulheres no Turismo</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= CSRF::field() ?>
                    <div class="form-group mb-3">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars($email ?? '') ?>" required autofocus>
                    </div>
                    <div class="form-group mb-4">
                        <label for="password">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block w-100">Entrar</button>
                    <p class="text-center mt-3 mb-0">
                        <small><a href="/admin/recover-password.php" class="text-muted">Esqueci minha senha</a></small>
                    </p>
                </form>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <small><a href="/" class="text-muted">Voltar ao site</a></small>
        </p>
    </div>
</div>
</body>
</html>
