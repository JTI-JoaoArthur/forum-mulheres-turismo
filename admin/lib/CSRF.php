<?php
/**
 * CSRF — Proteção contra Cross-Site Request Forgery
 *
 * Token por sessão (não single-use).
 * Regenera apenas quando a sessão é nova ou o token não existe.
 */

class CSRF
{
    private const TOKEN_LIFETIME = 7200; // 2 horas

    /**
     * Obter token da sessão (gera se não existir).
     */
    public static function generate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Regenerar se não existe ou expirou
        if (
            empty($_SESSION['csrf_token']) ||
            empty($_SESSION['csrf_time']) ||
            (time() - $_SESSION['csrf_time']) > self::TOKEN_LIFETIME
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validar token recebido
     */
    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        // Verificar expiração
        if (isset($_SESSION['csrf_time']) && (time() - $_SESSION['csrf_time']) > self::TOKEN_LIFETIME) {
            // Regenerar token expirado para o próximo form
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_time'] = time();
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
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
                $_SESSION['flash_message'] = 'Token de segurança expirado. Tente novamente.';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/'));
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
