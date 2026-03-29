<?php
/**
 * CSRF — Proteção contra Cross-Site Request Forgery
 *
 * Gera e valida tokens por formulário.
 * Mesmo padrão já usado no contact_process.php.
 */

class CSRF
{
    /**
     * Gerar token e armazenar na sessão
     */
    public static function generate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_time'] = time();

        return $token;
    }

    /**
     * Validar token recebido (single-use, expira em 1h)
     */
    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        // Expiração: 1 hora
        if (isset($_SESSION['csrf_time']) && (time() - $_SESSION['csrf_time']) > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_time']);
            return false;
        }

        $valid = hash_equals($_SESSION['csrf_token'], $token);

        // Single-use: invalidar após verificação
        unset($_SESSION['csrf_token'], $_SESSION['csrf_time']);

        return $valid;
    }

    /**
     * Gerar campo hidden HTML
     */
    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Exigir token válido ou abortar com 403
     */
    public static function require(): void
    {
        $token = $_POST['_token'] ?? '';
        if (!self::validate($token)) {
            http_response_code(403);
            if (self::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Token de segurança inválido. Recarregue a página.']);
            } else {
                echo 'Token de segurança inválido. Recarregue a página.';
            }
            exit;
        }
    }

    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
