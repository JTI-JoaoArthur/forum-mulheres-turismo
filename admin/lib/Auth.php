<?php
/**
 * Auth — Autenticação e gerenciamento de sessão
 *
 * Segurança:
 * - bcrypt via password_hash/password_verify
 * - Sessão com timeout de 30 min de inatividade
 * - Bloqueio após 5 tentativas falhas (15 min)
 * - Regeneração de session ID após login
 * - Cookies seguros (httponly, samesite)
 * - Suporte a roles (admin, editor)
 * - Fluxo needs_password para primeiro acesso
 */

require_once __DIR__ . '/Database.php';

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const SESSION_TIMEOUT = 1800; // 30 minutos

    /**
     * Iniciar sessão segura
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/admin/',
            'secure'   => $secure,
            'httponly'  => true,
            'samesite' => 'Strict',
        ]);

        session_name('admin_session');
        session_start();

        // Timeout por inatividade
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
                self::logout();
                return;
            }
        }

        if (isset($_SESSION['admin_id'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Verificar se está autenticado
     */
    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_authenticated']);
    }

    /**
     * Exigir autenticação — redireciona para login se não autenticado
     */
    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /admin/index.php');
            exit;
        }

        // Primeiro acesso: redirecionar para troca de senha
        if (!empty($_SESSION['admin_needs_password'])) {
            $current = basename($_SERVER['SCRIPT_NAME']);
            if ($current !== 'change-password.php' && $current !== 'logout.php') {
                header('Location: /admin/change-password.php');
                exit;
            }
        }
    }

    /**
     * Exigir role admin — redireciona para dashboard se não for admin
     */
    public static function requireAdmin(): void
    {
        self::require();
        if (self::role() !== 'admin') {
            $_SESSION['flash_message'] = 'Acesso restrito a administradores.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /admin/dashboard.php');
            exit;
        }
    }

    /**
     * Tentar login
     * @return array{success: bool, message: string, needs_password?: bool}
     */
    public static function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));

        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user) {
            self::log(null, 'login_failed', "Email não encontrado: {$email}");
            return ['success' => false, 'message' => 'Credenciais inválidas.'];
        }

        // Verificar bloqueio
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            self::log($user['id'], 'login_blocked', "Conta bloqueada, {$remaining}min restantes");
            return [
                'success' => false,
                'message' => "Conta bloqueada. Tente novamente em {$remaining} minuto(s)."
            ];
        }

        // Verificar senha
        if (!password_verify($password, $user['password_hash'])) {
            $attempts = $user['failed_attempts'] + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60);
                Database::query(
                    "UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?",
                    [$attempts, $lockUntil, $user['id']]
                );
                self::log($user['id'], 'account_locked', "Bloqueada após {$attempts} tentativas");
                return [
                    'success' => false,
                    'message' => "Conta bloqueada por " . self::LOCKOUT_MINUTES . " minutos após {$attempts} tentativas."
                ];
            }

            Database::query(
                "UPDATE users SET failed_attempts = ? WHERE id = ?",
                [$attempts, $user['id']]
            );
            self::log($user['id'], 'login_failed', "Senha incorreta (tentativa {$attempts}/" . self::MAX_ATTEMPTS . ")");

            $remaining = self::MAX_ATTEMPTS - $attempts;
            return [
                'success' => false,
                'message' => "Credenciais inválidas. {$remaining} tentativa(s) restante(s)."
            ];
        }

        // Sucesso — resetar tentativas e iniciar sessão
        Database::query(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = datetime('now', 'localtime') WHERE id = ?",
            [$user['id']]
        );

        // Regenerar session ID contra fixation
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_needs_password'] = (bool) $user['needs_password'];
        $_SESSION['last_activity'] = time();

        self::log($user['id'], 'login_success');

        return ['success' => true, 'message' => 'Login realizado com sucesso.'];
    }

    /**
     * Encerrar sessão
     */
    public static function logout(): void
    {
        self::startSession();
        if (isset($_SESSION['admin_id'])) {
            self::log($_SESSION['admin_id'], 'logout');
        }
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    /**
     * Obter dados do admin logado
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'    => $_SESSION['admin_id'],
            'name'  => $_SESSION['admin_name'],
            'email' => $_SESSION['admin_email'],
            'role'  => $_SESSION['admin_role'] ?? 'editor',
        ];
    }

    /**
     * Obter role do admin logado
     */
    public static function role(): string
    {
        return $_SESSION['admin_role'] ?? 'editor';
    }

    /**
     * Verificar se é admin
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Verificar se precisa trocar senha (primeiro acesso)
     */
    public static function needsPassword(): bool
    {
        return !empty($_SESSION['admin_needs_password']);
    }

    /**
     * Marcar que a troca de senha foi concluída
     */
    public static function clearNeedsPassword(): void
    {
        $_SESSION['admin_needs_password'] = false;
    }

    /**
     * Registrar evento no log de auditoria
     */
    public static function log(?int $userId, string $action, ?string $details = null): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        Database::query(
            "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$userId, $action, $details, $ip]
        );
    }

    /**
     * Criar usuário (usado no setup/seeding)
     */
    public static function createUser(string $email, string $password, string $name, array $extra = []): int
    {
        $data = [
            'email'         => mb_strtolower(trim($email)),
            'password_hash' => $password !== '' ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]) : '',
            'name'          => trim($name),
        ];

        // Campos opcionais: role, needs_password, recovery_email1, recovery_email2
        foreach (['role', 'needs_password', 'recovery_email1', 'recovery_email2'] as $field) {
            if (isset($extra[$field])) {
                $data[$field] = $extra[$field];
            }
        }

        return Database::insert('users', $data);
    }

    /**
     * Definir senha de um usuário (primeiro acesso ou reset)
     */
    public static function setPassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            "UPDATE users SET password_hash = ?, needs_password = 0, updated_at = datetime('now', 'localtime') WHERE id = ?",
            [$hash, $userId]
        );
    }
}
