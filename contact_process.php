<?php
/**
 * Processamento do formulário de contato
 * Fórum de Mulheres no Turismo — Ministério do Turismo / ONU Turismo
 *
 * CONFIGURAÇÃO: altere $to para o e-mail definitivo da gestão.
 */

// ── Configuração (CMS) ──────────────────────────────────────────────
// Tenta ler do banco; se indisponível, usa valores padrão.
$to = "default@turismo.gov.br";
$from_address = "noreply@turismo.gov.br";

try {
    require_once __DIR__ . '/admin/lib/Database.php';
    $dbPath = __DIR__ . '/admin/data/cms.sqlite';
    if (file_exists($dbPath)) {
        $recipient = Database::getSetting('form_recipient');
        $sender    = Database::getSetting('form_sender');
        if ($recipient) $to = $recipient;
        if ($sender) $from_address = $sender;
    }
} catch (Exception $e) {
    // Mantém defaults
}

// ── Apenas POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Método não permitido.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ── CSRF: validar token da sessão ───────────────────────────────────
session_start();
$token = $_POST['_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Token de segurança inválido. Recarregue a página.']);
    exit;
}
// Regenerar token após uso (single-use)
unset($_SESSION['csrf_token']);

// ── Honeypot: campo invisível que bots preenchem ────────────────────
if (!empty($_POST['website'])) {
    // Finge sucesso para não alertar o bot
    echo json_encode(['status' => 'ok', 'mensagem' => 'Mensagem enviada com sucesso!']);
    exit;
}

// ── Coletar e sanitizar campos ──────────────────────────────────────
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// ── Validação ───────────────────────────────────────────────────────
$erros = [];

// Nome: 2–100 caracteres, apenas letras, espaços e acentos
if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $erros[] = 'Nome deve ter entre 2 e 100 caracteres.';
} elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $name)) {
    $erros[] = 'Nome contém caracteres inválidos.';
}

// Email: formato válido, máximo 254 caracteres (RFC 5321)
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
    $erros[] = 'Informe um e-mail válido.';
}
// Bloquear quebras de linha (prevenção de injeção de header)
if (preg_match('/[\r\n]/', $email)) {
    $erros[] = 'E-mail contém caracteres inválidos.';
}

// Assunto: 4–200 caracteres
if ($subject === '' || mb_strlen($subject) < 4 || mb_strlen($subject) > 200) {
    $erros[] = 'Assunto deve ter entre 4 e 200 caracteres.';
}

// Mensagem: 20–5000 caracteres
if ($message === '' || mb_strlen($message) < 20 || mb_strlen($message) > 5000) {
    $erros[] = 'Mensagem deve ter entre 20 e 5.000 caracteres.';
}

if (!empty($erros)) {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'mensagem' => implode(' ', $erros)]);
    exit;
}

// ── Escapar dados para HTML do e-mail (previne XSS) ────────────────
$safeName    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$safeEmail   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

// ── Montar e-mail ───────────────────────────────────────────────────
$mailSubject = "Contato pelo site: " . $subject;

$headers  = "From: " . $from_address . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$body  = "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'></head><body>";
$body .= "<table style='width:100%;max-width:600px;font-family:Arial,sans-serif;'>";
$body .= "<tr><td style='padding:12px;background:#64428c;color:#fff;font-size:18px;' colspan='2'>";
$body .= "Nova mensagem — Fórum de Mulheres no Turismo</td></tr>";
$body .= "<tr><td style='padding:8px;'><strong>Nome:</strong> {$safeName}</td>";
$body .= "<td style='padding:8px;'><strong>E-mail:</strong> {$safeEmail}</td></tr>";
$body .= "<tr><td style='padding:8px;' colspan='2'><strong>Assunto:</strong> {$safeSubject}</td></tr>";
$body .= "<tr><td style='padding:12px;border-top:1px solid #ddd;' colspan='2'>{$safeMessage}</td></tr>";
$body .= "</table></body></html>";

// ── Enviar ──────────────────────────────────────────────────────────
$enviado = mail($to, $mailSubject, $body, $headers);

if ($enviado) {
    echo json_encode(['status' => 'ok', 'mensagem' => 'Mensagem enviada com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao enviar. Tente novamente mais tarde.']);
}
