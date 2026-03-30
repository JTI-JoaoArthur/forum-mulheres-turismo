<?php
/**
 * Alterar senha — Primeiro acesso ou troca voluntária
 *
 * Usuário já autenticado. Pede senha atual + nova senha.
 * No primeiro acesso (needs_password), exibe aviso amigável.
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::require();

$pageTitle   = 'Alterar Senha';
$error       = '';
$success     = false;
$firstAccess = Auth::needsPassword();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();

    $action = $_POST['action'] ?? '';

    // Pular troca no primeiro acesso (manter senha atual)
    if ($action === 'skip' && $firstAccess) {
        $userId = Auth::user()['id'];
        Database::query(
            "UPDATE users SET needs_password = 0, updated_at = datetime('now', 'localtime') WHERE id = ?",
            [$userId]
        );
        Auth::clearNeedsPassword();
        Auth::log($userId, 'password_change_skipped', 'Usuário optou por manter a senha padrão');
        header('Location: /admin/dashboard.php');
        exit;
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirm         = $_POST['confirm_password'] ?? '';

    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [Auth::user()['id']]);

    if (!password_verify($currentPassword, $user['password_hash'])) {
        $error = 'Senha atual incorreta.';
    } elseif (mb_strlen($newPassword) < 12) {
        $error = 'Nova senha deve ter pelo menos 12 caracteres.';
    } elseif ($newPassword !== $confirm) {
        $error = 'As senhas não coincidem.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'A nova senha deve ser diferente da atual.';
    } else {
        Auth::setPassword($user['id'], $newPassword);
        Auth::clearNeedsPassword();
        Auth::log($user['id'], 'password_changed', $firstAccess ? 'Senha alterada no primeiro acesso' : 'Senha alterada pelo usuário');
        $success = true;
    }
}

require __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <?php if ($firstAccess && !$success): ?>
        <div class="alert alert-warning">
            <strong>Primeiro acesso detectado.</strong><br>
            Recomendamos que você altere sua senha padrão por uma senha pessoal.
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-key"></i> Alterar Senha</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Senha alterada com sucesso!
                        <a href="/admin/dashboard.php" class="alert-link">Ir para o dashboard</a>.
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?= CSRF::field() ?>
                        <div class="form-group mb-3">
                            <label for="current_password">Senha atual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required autofocus>
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password">Nova senha <small class="text-muted">(mínimo 12 caracteres)</small></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="12">
                        </div>
                        <div class="form-group mb-4">
                            <label for="confirm_password">Confirmar nova senha</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="12">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">
                            <i class="fas fa-save"></i> Alterar Senha
                        </button>
                    </form>

                    <?php if ($firstAccess): ?>
                    <hr>
                    <form method="post">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="action" value="skip">
                        <button type="submit" class="btn btn-outline-secondary btn-block w-100">
                            Manter senha atual e continuar
                        </button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/templates/footer.php';
