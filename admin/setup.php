<?php
/**
 * Setup inicial — Semeia os 2 usuários predefinidos
 *
 * As senhas são lidas de variáveis de ambiente (nunca hardcoded):
 *   SETUP_ADMIN_PASSWORD  — senha do CGMK (admin)
 *   SETUP_EDITOR_PASSWORD — senha da ASCOM (editor)
 *
 * Defina antes de rodar: copie .env.example para .env e preencha.
 * Este arquivo se auto-desabilita após execução (verifica se já há usuários).
 * Acesse apenas uma vez: /admin/setup.php
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';

Auth::startSession();

// Carregar .env se existir (para ambientes sem variáveis de ambiente nativas)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

// Se já existem usuários, bloquear acesso
$userCount = Database::fetchOne("SELECT COUNT(*) as total FROM users")['total'];
if ($userCount > 0) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Acesso Negado</title></head><body>';
    echo '<h1>Setup já realizado</h1><p>Os usuários já foram criados. <a href="/admin/">Ir para o login</a></p>';
    echo '</body></html>';
    exit;
}

// Ler senhas de variáveis de ambiente
$adminPassword  = $_ENV['SETUP_ADMIN_PASSWORD']  ?? getenv('SETUP_ADMIN_PASSWORD')  ?: '';
$editorPassword = $_ENV['SETUP_EDITOR_PASSWORD'] ?? getenv('SETUP_EDITOR_PASSWORD') ?: '';

if (mb_strlen($adminPassword) < 12 || mb_strlen($editorPassword) < 12) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Setup — Erro</title></head><body>';
    echo '<h1>Variáveis de ambiente não configuradas</h1>';
    echo '<p>Defina <code>SETUP_ADMIN_PASSWORD</code> e <code>SETUP_EDITOR_PASSWORD</code> (mín. 12 caracteres) no arquivo <code>.env</code> antes de rodar o setup.</p>';
    echo '<p>Consulte <code>.env.example</code> para referência.</p>';
    echo '</body></html>';
    exit;
}

// ── Semear os 2 usuários ───────────────────────────────────────────

// 1) CGMK — Administrador
Auth::createUser(
    'cgmk@turismo.gov.br',
    $adminPassword,
    'CGMK',
    [
        'role'            => 'admin',
        'recovery_email1' => 'cgmk@turismo.gov.br',
    ]
);

// 2) ASCOM — Editor (troca de senha sugerida no 1º acesso)
Auth::createUser(
    'imprensa@turismo.gov.br',
    $editorPassword,
    'ASCOM',
    [
        'role'            => 'editor',
        'needs_password'  => 1,
        'recovery_email1' => 'imprensa@turismo.gov.br',
    ]
);

Auth::log(null, 'setup_completed', 'Usuários CGMK (admin) e ASCOM (editor) criados via setup');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Setup Concluído — Painel Administrativo</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <style>
        body { background: #f4f2f7; }
        .setup-card { max-width: 540px; margin: 80px auto; }
        .brand-color { color: #64428c; }
    </style>
</head>
<body>
<div class="container">
    <div class="setup-card">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center brand-color mb-4">Setup Concluído</h3>
                <div class="alert alert-success">
                    <strong>Usuários criados com sucesso!</strong>
                </div>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Conta</th><th>E-mail</th><th>Perfil</th><th>Senha</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>CGMK</strong></td>
                            <td><code>cgmk@turismo.gov.br</code></td>
                            <td><span class="badge badge-dark">Admin</span></td>
                            <td>Predefinida</td>
                        </tr>
                        <tr>
                            <td><strong>ASCOM</strong></td>
                            <td><code>imprensa@turismo.gov.br</code></td>
                            <td><span class="badge badge-secondary">Editor</span></td>
                            <td>Predefinida (troca sugerida no 1º acesso)</td>
                        </tr>
                    </tbody>
                </table>
                <hr>
                <p class="text-muted small mb-0">
                    No primeiro login, a ASCOM será convidada a alterar sua senha padrão.
                    Para recuperação de senha, o admin deve cadastrar e-mails de recuperação em Usuários.
                </p>
                <a href="/admin/" class="btn btn-primary btn-block w-100 mt-3">Ir para o Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
