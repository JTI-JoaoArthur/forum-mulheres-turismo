<?php
/**
 * Recuperar senha — Página informativa
 *
 * A recuperação automática via código por e-mail está implementada mas
 * desabilitada até que o servidor de produção tenha SMTP configurado.
 * Código completo preservado comentado abaixo para ativação futura.
 *
 * Enquanto isso, orienta o usuário a contatar o admin (CGMK).
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::startSession();

if (Auth::check()) {
    header('Location: /admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar Senha — Painel Administrativo</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="admin-login">
<div class="container">
    <div class="login-card">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center brand-color mb-1">Recuperar Senha</h3>
                <p class="text-center text-muted mb-4">Painel Administrativo</p>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    Para redefinir sua senha, entre em contato com o suporte administrativo (<strong>CGMK</strong>).
                </div>

                <p class="text-muted">
                    O administrador pode redefinir sua senha pelo painel na seção <strong>Usuários</strong>.
                </p>
                <p class="text-muted small">
                    Contato: <code>cgmk@turismo.gov.br</code>
                </p>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <small><a href="/admin/" class="text-muted">Voltar ao login</a></small>
        </p>
    </div>
</div>
</body>
</html>
<?php
/*
 * =====================================================================
 * RECUPERAÇÃO AUTOMÁTICA VIA CÓDIGO POR E-MAIL
 * =====================================================================
 * Ativar quando o servidor tiver Sendmail/SMTP configurado.
 * Substituir todo o conteúdo acima por este bloco.
 *
 * Dependências:
 * - Colunas recovery_token_hash e recovery_token_expires na tabela users (já no schema)
 * - E-mails de recuperação cadastrados por usuário (recovery_email1/2)
 * - Função mail() do PHP funcional (Sendmail ou SMTP)
 * - SPF/DKIM configurados no DNS de turismo.gov.br
 *
 * Fluxo:
 * 1. Usuário informa e-mail de acesso
 * 2. Sistema envia código de 6 dígitos para o e-mail de recuperação
 * 3. Usuário digita o código (prova acesso à caixa de entrada)
 * 4. Usuário define nova senha
 *
 * Código de 6 dígitos, hash SHA-256 no banco, validade 15 min,
 * verificação via hash_equals. E-mail mascarado na tela (im***@dominio).
 *
 * Para ativar: remover o HTML estático acima e descomentar o código abaixo.
 * =====================================================================

$error   = '';
$info    = '';
$success = false;
$step    = $_POST['step'] ?? 'identify';
$TOKEN_TTL = 15 * 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();

    if ($step === 'identify') {
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user) {
            $error = 'E-mail não encontrado.';
        } elseif (empty($user['recovery_email1']) && empty($user['recovery_email2'])) {
            $info = 'Esta conta não possui e-mails de recuperação cadastrados. Entre em contato com o suporte administrativo (CGMK) para redefinir sua senha.';
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $code);
            $expires = date('Y-m-d H:i:s', time() + $TOKEN_TTL);

            Database::query(
                "UPDATE users SET recovery_token_hash = ?, recovery_token_expires = ?, updated_at = datetime('now', 'localtime') WHERE id = ?",
                [$hash, $expires, $user['id']]
            );

            $recoveryTo = $user['recovery_email1'] ?: $user['recovery_email2'];
            $sent = sendRecoveryEmail($recoveryTo, $user['name'], $code);

            if ($sent) {
                $_SESSION['recover_user_id'] = $user['id'];
                $_SESSION['recover_masked_email'] = maskEmail($recoveryTo);
                Auth::log($user['id'], 'recovery_code_sent', "Código enviado para " . maskEmail($recoveryTo));
                $step = 'verify_code';
            } else {
                $error = 'Erro ao enviar o e-mail. Entre em contato com o suporte administrativo (CGMK).';
                Auth::log($user['id'], 'recovery_email_failed', "Falha no envio para {$recoveryTo}");
            }
        }

    } elseif ($step === 'verify_code') {
        $userId = $_SESSION['recover_user_id'] ?? null;
        if (!$userId) {
            $error = 'Sessão expirada. Reinicie o processo.';
            $step = 'identify';
        } else {
            $code = trim($_POST['code'] ?? '');
            $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

            if (!$user || empty($user['recovery_token_hash'])) {
                $error = 'Código inválido. Reinicie o processo.';
                $step = 'identify';
            } elseif (strtotime($user['recovery_token_expires']) < time()) {
                Database::query(
                    "UPDATE users SET recovery_token_hash = NULL, recovery_token_expires = NULL WHERE id = ?",
                    [$userId]
                );
                $error = 'Código expirado (validade de 15 minutos). Reinicie o processo.';
                Auth::log($userId, 'recovery_code_expired');
                $step = 'identify';
            } elseif (!hash_equals($user['recovery_token_hash'], hash('sha256', $code))) {
                $error = 'Código incorreto. Verifique sua caixa de entrada e tente novamente.';
                Auth::log($userId, 'recovery_code_invalid', 'Código incorreto informado');
            } else {
                Database::query(
                    "UPDATE users SET recovery_token_hash = NULL, recovery_token_expires = NULL WHERE id = ?",
                    [$userId]
                );
                $_SESSION['recover_verified'] = true;
                $step = 'new_password';
            }
        }

    } elseif ($step === 'new_password') {
        $userId = $_SESSION['recover_user_id'] ?? null;
        $verified = $_SESSION['recover_verified'] ?? false;

        if (!$userId || !$verified) {
            $error = 'Sessão inválida. Reinicie o processo.';
            $step = 'identify';
        } else {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            if (mb_strlen($password) < 12) {
                $error = 'Senha deve ter pelo menos 12 caracteres.';
                $step = 'new_password';
            } elseif ($password !== $confirm) {
                $error = 'As senhas não coincidem.';
                $step = 'new_password';
            } else {
                Auth::setPassword($userId, $password);
                Auth::log($userId, 'password_recovered', 'Senha redefinida via código de verificação por e-mail');
                unset($_SESSION['recover_user_id'], $_SESSION['recover_masked_email'], $_SESSION['recover_verified']);
                $success = true;
            }
        }
    }
}

function sendRecoveryEmail(string $to, string $userName, string $code): bool
{
    $subject = "Código de recuperação — Painel Administrativo";
    $from = "noreply@turismo.gov.br";
    try {
        $sender = Database::getSetting('form_sender');
        if ($sender) $from = $sender;
    } catch (Exception $e) {}

    $headers  = "From: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body = "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'></head><body>";
    $body .= "<table style='width:100%;max-width:500px;font-family:Arial,sans-serif;'>";
    $body .= "<tr><td style='padding:16px;background:#64428c;color:#fff;font-size:16px;text-align:center;'>";
    $body .= "Recuperação de Senha</td></tr>";
    $body .= "<tr><td style='padding:20px;'>";
    $body .= "<p>Olá, <strong>" . htmlspecialchars($userName) . "</strong>.</p>";
    $body .= "<p>Você solicitou a redefinição de senha do Painel Administrativo do Fórum de Mulheres no Turismo.</p>";
    $body .= "<p style='text-align:center;margin:24px 0;'>";
    $body .= "<span style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#64428c;'>{$code}</span></p>";
    $body .= "<p>Informe este código na página de recuperação. Ele é válido por <strong>15 minutos</strong>.</p>";
    $body .= "<p style='color:#888;font-size:12px;'>Se você não solicitou esta recuperação, ignore este e-mail.</p>";
    $body .= "</td></tr></table></body></html>";

    return mail($to, $subject, $body, $headers);
}

function maskEmail(string $email): string
{
    [$local, $domain] = explode('@', $email);
    $visible = mb_substr($local, 0, 2);
    return $visible . str_repeat('*', max(3, mb_strlen($local) - 2)) . '@' . $domain;
}

// HTML para o fluxo completo (etapas identify → verify_code → new_password → success)
// ... (copiar o HTML das etapas da versão anterior)

*/
