<?php
/**
 * Gestão de Usuários — Admin only
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::requireAdmin();

$pageTitle = 'Usuários';
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ── Ações POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($postAction === 'reset_password' && $id > 0) {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if ($user) {
            $newPassword = $_POST['new_password'] ?? '';
            if (mb_strlen($newPassword) < 12) {
                $_SESSION['flash_message'] = 'Senha deve ter pelo menos 12 caracteres.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                Auth::setPassword($id, $newPassword);
                Database::query("UPDATE users SET needs_password = 1 WHERE id = ?", [$id]);
                Auth::log(Auth::user()['id'], 'user_password_reset', "Senha redefinida para {$user['name']} (#{$id})");
                $_SESSION['flash_message'] = "Senha de {$user['name']} redefinida. No próximo login será solicitada a troca.";
                $_SESSION['flash_type'] = 'success';
            }
        }
        header('Location: /admin/users.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if ($user && $user['id'] !== Auth::user()['id']) {
            $newVal = $user['is_active'] ? 0 : 1;
            Database::query("UPDATE users SET is_active = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", [$newVal, $id]);
            $status = $newVal ? 'ativada' : 'desativada';
            Auth::log(Auth::user()['id'], 'user_toggled', "Conta {$user['name']} (#{$id}) {$status}");
            $_SESSION['flash_message'] = "Conta {$user['name']} {$status}.";
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: /admin/users.php');
        exit;
    }

    if ($postAction === 'update_recovery' && $id > 0) {
        $recovery1 = mb_strtolower(trim($_POST['recovery_email1'] ?? ''));
        $recovery2 = mb_strtolower(trim($_POST['recovery_email2'] ?? ''));
        $errors = [];
        if ($recovery1 && !filter_var($recovery1, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail de recuperação 1 inválido.';
        if ($recovery2 && !filter_var($recovery2, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail de recuperação 2 inválido.';
        if (!empty($errors)) {
            $_SESSION['flash_message'] = implode(' ', $errors);
            $_SESSION['flash_type'] = 'danger';
        } else {
            Database::query(
                "UPDATE users SET recovery_email1 = ?, recovery_email2 = ?, updated_at = datetime('now', 'localtime') WHERE id = ?",
                [$recovery1 ?: null, $recovery2 ?: null, $id]
            );
            $user = Database::fetchOne("SELECT name FROM users WHERE id = ?", [$id]);
            Auth::log(Auth::user()['id'], 'user_recovery_updated', "E-mails de recuperação atualizados para {$user['name']} (#{$id})");
            $_SESSION['flash_message'] = "E-mails de recuperação de {$user['name']} atualizados.";
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: /admin/users.php');
        exit;
    }

    if ($postAction === 'unlock' && $id > 0) {
        Database::query(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL, updated_at = datetime('now', 'localtime') WHERE id = ?",
            [$id]
        );
        Auth::log(Auth::user()['id'], 'user_unlocked', "Conta #{$id} desbloqueada");
        $_SESSION['flash_message'] = 'Conta desbloqueada.';
        $_SESSION['flash_type'] = 'success';
        header('Location: /admin/users.php');
        exit;
    }
}

$users = Database::fetchAll("SELECT * FROM users ORDER BY id ASC");

require __DIR__ . '/templates/header.php';

if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<h2 class="mb-4">Usuários</h2>

<div class="row">
<?php foreach ($users as $u): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars($u['name']) ?></strong>
                <div>
                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-dark' : 'badge-secondary' ?>">
                        <?= $u['role'] === 'admin' ? 'Admin' : 'Editor' ?>
                    </span>
                    <?php if (!$u['is_active']): ?>
                        <span class="badge badge-danger">Inativo</span>
                    <?php endif; ?>
                    <?php if ($u['needs_password']): ?>
                        <span class="badge badge-warning">Sem senha</span>
                    <?php endif; ?>
                    <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                        <span class="badge badge-danger">Bloqueado</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:140px">E-mail:</td>
                        <td><code><?= htmlspecialchars($u['email']) ?></code></td>
                    </tr>
                    <?php if ($u['recovery_email1']): ?>
                    <tr>
                        <td class="text-muted">Recuperação 1:</td>
                        <td><code><?= htmlspecialchars($u['recovery_email1']) ?></code></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($u['recovery_email2']): ?>
                    <tr>
                        <td class="text-muted">Recuperação 2:</td>
                        <td><code><?= htmlspecialchars($u['recovery_email2']) ?></code></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Último login:</td>
                        <td><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '<em class="text-muted">Nunca</em>' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tentativas falhas:</td>
                        <td><?= $u['failed_attempts'] ?></td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <!-- Redefinir Senha -->
                <button class="btn btn-outline-primary btn-sm" type="button"
                        data-toggle="collapse" data-target="#resetPw<?= $u['id'] ?>">
                    <i class="fas fa-key"></i> Redefinir Senha
                </button>

                <!-- E-mails de Recuperação -->
                <button class="btn btn-outline-info btn-sm" type="button"
                        data-toggle="collapse" data-target="#recovery<?= $u['id'] ?>">
                    <i class="fas fa-envelope"></i> Recuperação
                </button>

                <!-- Ativar/Desativar (não pode desativar a si mesmo) -->
                <?php if ($u['id'] !== Auth::user()['id']): ?>
                <form method="post" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?> btn-sm"
                            data-confirm="<?= $u['is_active'] ? 'Desativar' : 'Ativar' ?> conta de <?= htmlspecialchars($u['name']) ?>?">
                        <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                        <?= $u['is_active'] ? 'Desativar' : 'Ativar' ?>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Desbloquear (se bloqueado) -->
                <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                <form method="post" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="action" value="unlock">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-unlock"></i> Desbloquear
                    </button>
                </form>
                <?php endif; ?>

                <!-- Formulário de redefinir senha (colapsado) -->
                <div class="collapse mt-3" id="resetPw<?= $u['id'] ?>">
                    <form method="post" class="form-inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="password" name="new_password" class="form-control form-control-sm mr-2"
                               placeholder="Nova senha (mín. 12)" minlength="12" required style="width:220px">
                        <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                    </form>
                </div>

                <!-- Formulário de e-mails de recuperação (colapsado) -->
                <div class="collapse mt-3" id="recovery<?= $u['id'] ?>">
                    <form method="post">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="action" value="update_recovery">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <div class="form-group mb-2">
                            <input type="email" name="recovery_email1" class="form-control form-control-sm"
                                   placeholder="E-mail de recuperação 1" value="<?= htmlspecialchars($u['recovery_email1'] ?? '') ?>">
                        </div>
                        <div class="form-group mb-2">
                            <input type="email" name="recovery_email2" class="form-control form-control-sm"
                                   placeholder="E-mail de recuperação 2" value="<?= htmlspecialchars($u['recovery_email2'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-info btn-sm">Salvar e-mails</button>
                        <small class="form-text text-muted">Usados para recuperação de senha pelo próprio usuário.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/templates/footer.php';
