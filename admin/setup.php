<?php
/**
 * Setup inicial — Semeia os 2 usuários predefinidos
 *
 * ASCOM (editor): sem senha, needs_password=1, 2 e-mails de recuperação
 * CGMK  (admin):  senha predefinida $SETUP_ADMIN_PASSWORD
 *
 * Este arquivo se auto-desabilita após execução (verifica se já há usuários).
 * Acesse apenas uma vez: /admin/setup.php
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';

Auth::startSession();

// Se já existem usuários, bloquear acesso
$userCount = Database::fetchOne("SELECT COUNT(*) as total FROM users")['total'];
if ($userCount > 0) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Acesso Negado</title></head><body>';
    echo '<h1>Setup já realizado</h1><p>Os usuários já foram criados. <a href="/admin/">Ir para o login</a></p>';
    echo '</body></html>';
    exit;
}

// ── Semear os 2 usuários ───────────────────────────────────────────

// 1) CGMK — Administrador com senha predefinida
Auth::createUser(
    'cgmk@turismo.gov.br',
    '$SETUP_ADMIN_PASSWORD',
    'CGMK',
    [
        'role' => 'admin',
    ]
);

// 2) ASCOM — Editor sem senha (definirá no primeiro acesso)
Auth::createUser(
    'ascom@turismo.gov.br',
    '', // sem senha
    'ASCOM',
    [
        'role'            => 'editor',
        'needs_password'  => 1,
        'recovery_email1' => 'ascom1@turismo.gov.br',
        'recovery_email2' => 'ascom2@turismo.gov.br',
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
                            <td><code>ascom@turismo.gov.br</code></td>
                            <td><span class="badge badge-secondary">Editor</span></td>
                            <td>A definir no 1º acesso</td>
                        </tr>
                    </tbody>
                </table>
                <hr>
                <p class="text-muted small mb-0">
                    A ASCOM deverá acessar <a href="/admin/set-password.php">/admin/set-password.php</a>
                    para definir sua senha usando um dos e-mails de recuperação cadastrados.
                </p>
                <a href="/admin/" class="btn btn-primary btn-block w-100 mt-3">Ir para o Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
