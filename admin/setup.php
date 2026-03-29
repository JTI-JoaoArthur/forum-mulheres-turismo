<?php
/**
 * Setup inicial — Cria o primeiro usuário administrador
 *
 * Este arquivo se auto-desabilita após o primeiro uso.
 * Acesse apenas uma vez: /admin/setup.php
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::startSession();

// Verificar se já existe usuário — se sim, bloquear acesso
$userCount = Database::fetchOne("SELECT COUNT(*) as total FROM users")['total'];
if ($userCount > 0) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Acesso Negado</title></head><body>';
    echo '<h1>Setup já realizado</h1><p>O administrador já foi criado. <a href="/admin/">Ir para o login</a></p>';
    echo '</body></html>';
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    // Validações
    if (mb_strlen($name) < 2) {
        $error = 'Nome deve ter pelo menos 2 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } elseif (mb_strlen($password) < 12) {
        $error = 'Senha deve ter pelo menos 12 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'As senhas não coincidem.';
    } else {
        Auth::createUser($email, $password, $name);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Setup — Painel Administrativo</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <style>
        body { background: #f4f2f7; }
        .setup-card { max-width: 480px; margin: 80px auto; }
        .brand-color { color: #64428c; }
        .btn-primary { background: #64428c; border-color: #64428c; }
        .btn-primary:hover { background: #523672; border-color: #523672; }
    </style>
</head>
<body>
<div class="container">
    <div class="setup-card">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center brand-color mb-4">Configuração Inicial</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Administrador criado com sucesso!
                        <a href="/admin/" class="alert-link">Ir para o login</a>.
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">Crie o primeiro usuário administrador do painel.</p>

                    <form method="post" autocomplete="off">
                        <?= CSRF::field() ?>
                        <div class="form-group mb-3">
                            <label for="name">Nome</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($name ?? '') ?>" required minlength="2">
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($email ?? '') ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Senha <small class="text-muted">(mínimo 12 caracteres)</small></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required minlength="12">
                        </div>
                        <div class="form-group mb-4">
                            <label for="password_confirm">Confirmar senha</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   required minlength="12">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">Criar Administrador</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
