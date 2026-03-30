<?php
/**
 * Bootstrap do site público.
 * Carrega Database e configurações do CMS.
 * Se o banco não existir, usa valores padrão (site funciona sem admin).
 */

require_once __DIR__ . '/../admin/lib/Database.php';

$_site = [];
$_dbReady = false;

try {
    $dbPath = __DIR__ . '/../admin/data/cms.sqlite';
    if (file_exists($dbPath)) {
        $_dbReady = true;
        $rows = Database::fetchAll("SELECT key, value FROM settings");
        foreach ($rows as $row) {
            $_site[$row['key']] = $row['value'];
        }
    }
} catch (Exception $e) {
    $_dbReady = false;
}

// Defaults para quando o banco não existir
$_defaults = [
    'site_title'       => 'Fórum de Mulheres no Turismo',
    'about_title'      => 'Fórum de Mulheres no Turismo',
    'about_body'       => '',
    'about_image1'     => '',
    'about_image2'     => '',
    'contact_city'     => 'João Pessoa, Paraíba',
    'contact_venue'    => 'Centro de Convenções de João Pessoa',
    'contact_phone'    => 'Telefone',
    'contact_hours'    => 'Seg. a Sex. das 9h às 18h',
    'contact_email'    => 'contato@turismo.gov.br',
    'contact_email_desc' => 'Entre em contato conosco!',
    'footer_about'     => 'Fórum de Mulheres no Turismo — uma iniciativa do Ministério do Turismo e ONU Turismo.',
    'footer_location'  => 'Centro de Convenções de João Pessoa',
    'footer_date'      => '3 e 4 de Junho de 2026',
    'social_instagram' => 'https://www.instagram.com/mturismo/',
    'social_facebook'  => 'https://www.facebook.com/MinisterioDoTurismo',
    'social_twitter'   => 'https://x.com/MTurismo',
    'social_youtube'   => 'https://www.youtube.com/c/MinisteriodoTurismo',
    'social_linkedin'  => 'https://www.linkedin.com/company/mturismo/',
    'form_recipient'   => 'default@turismo.gov.br',
    'form_sender'      => 'noreply@turismo.gov.br',
    'event_date'       => 'June 3, 2026 09:00:00',
    'maps_query'       => 'Centro+de+Convenções+de+João+Pessoa,+PB,+Brasil',
];

/**
 * Retorna configuração escapada para HTML.
 */
function site(string $key): string {
    global $_site, $_defaults;
    $val = $_site[$key] ?? $_defaults[$key] ?? '';
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna configuração raw (sem escape).
 */
function siteRaw(string $key): string {
    global $_site, $_defaults;
    return $_site[$key] ?? $_defaults[$key] ?? '';
}

/**
 * Verifica se o banco está pronto.
 */
function dbReady(): bool {
    global $_dbReady;
    return $_dbReady;
}

/**
 * Sanitiza HTML permitindo apenas tags e atributos seguros.
 * Previne Stored XSS em campos que aceitam HTML (news body, about body).
 */
function sanitizeHtml(string $html): string {
    if (trim($html) === '') return '';

    // Tags permitidas
    $allowedTags = '<p><br><strong><b><em><i><u><s><ol><ul><li><a><img><h2><h3><h4><h5><h6><blockquote><hr><table><thead><tbody><tr><th><td><figure><figcaption><div><span>';
    $html = strip_tags($html, $allowedTags);

    // Remover atributos perigosos via regex
    // Remove: on* (onclick, onerror, etc.), javascript:, data:, expression()
    $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*\S+/i', '', $html);
    $html = preg_replace('/javascript\s*:/i', '', $html);
    $html = preg_replace('/data\s*:[^image]/i', 'blocked:', $html);
    $html = preg_replace('/expression\s*\(/i', 'blocked(', $html);
    $html = preg_replace('/vbscript\s*:/i', '', $html);

    return $html;
}

/**
 * Valida que uma URL usa protocolo seguro.
 * Retorna a URL limpa ou string vazia se insegura.
 */
function sanitizeUrl(string $url): string {
    $url = trim($url);
    if ($url === '' || $url === '#') return $url;
    if (preg_match('#^(https?://|/[^/])#i', $url)) return $url;
    return '';
}
