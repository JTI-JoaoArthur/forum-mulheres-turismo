<?php
/**
 * Definir senha — Primeiro acesso
 *
 * Permite que contas com needs_password=1 definam sua senha
 * mediante verificação de um dos e-mails de recuperação cadastrados.
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::startSession();

// Se já logado, vai para o dashboard
if (Auth::check()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error   = '';
$success = false;
$step    = $_POST['step'] ?? 'identify'; // identify → set_password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();

    if ($step === 'identify') {
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $recoveryEmail = mb_strtolower(trim($_POST['recovery_email'] ?? ''));

        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND needs_password = 1 AND is_active = 1",
            [$email]
        );

        if (!$user) {
            $error = 'Conta não encontrada ou senha já definida.';
        } elseif (
            mb_strtolower($user['recovery_email1'] ?? '') !== $recoveryEmail &&
            mb_strtolower($user['recovery_email2'] ?? '') !== $recoveryEmail
        ) {
            $error = 'E-mail de recuperação não confere.';
            Auth::log($user['id'], 'set_password_failed', "E-mail de recuperação incorreto: {$recoveryEmail}");
        } else {
            // Verificação OK — avançar para definir senha
            $_SESSION['set_password_user_id'] = $user['id'];
            $_SESSION['set_password_email'] = $user['email'];
            $step = 'set_password';
        }
    } elseif ($step === 'set_password') {
        $userId = $_SESSION['set_password_user_id'] ?? null;

        if (!$userId) {
            $error = 'Sessão expirada. Reinicie o processo.';
            $step = 'identify';
        } else {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            if (mb_strlen($password) < 12) {
                $error = 'Senha deve ter pelo menos 12 caracteres.';
                $step = 'set_password';
            } elseif ($password !== $confirm) {
                $error = 'As senhas não coincidem.';
                $step = 'set_password';
            } else {
                Auth::setPassword($userId, $password);
                Auth::log($userId, 'password_set', 'Senha definida no primeiro acesso');
                unset($_SESSION['set_password_user_id'], $_SESSION['set_password_email']);
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Definir Senha — Painel Administrativo</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="admin-login">
<div class="container">
    <div class="login-card">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center brand-color mb-1">Definir Senha</h3>
                <p class="text-center text-muted mb-4">Primeiro acesso ao painel</p>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Senha definida com sucesso!
                        <a href="/admin/" class="alert-link">Ir para o login</a>.
                    </div>

                <?php elseif ($step === 'set_password'): ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <p class="text-muted mb-3">
                        Conta: <strong><?= htmlspecialchars($_SESSION['set_password_email'] ?? '') ?></strong>
                    </p>
                    <form method="post" autocomplete="off">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="step" value="set_password">
                        <div class="form-group mb-3">
                            <label for="password">Nova senha <small class="text-muted">(mínimo 12 caracteres)</small></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required minlength="12" autofocus>
                        </div>
                        <div class="form-group mb-4">
                            <label for="password_confirm">Confirmar senha</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   required minlength="12">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">Definir Senha</button>
                    </form>

                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <p class="text-muted mb-3">
                        Informe seu e-mail de acesso e um dos e-mails de recuperação cadastrados para verificação.
                    </p>
                    <form method="post" autocomplete="off">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="step" value="identify">
                        <div class="form-group mb-3">
                            <label for="email">E-mail de acesso</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($email ?? '') ?>" required autofocus>
                        </div>
                        <div class="form-group mb-4">
                            <label for="recovery_email">E-mail de recuperação</label>
                            <input type="email" class="form-control" id="recovery_email" name="recovery_email"
                                   value="<?= htmlspecialchars($recoveryEmail ?? '') ?>" required>
                            <small class="form-text text-muted">Um dos e-mails institucionais vinculados à sua conta.</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">Verificar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <small><a href="/admin/" class="text-muted">Voltar ao login</a></small>
        </p>
    </div>
</div>
</body>
</html>
