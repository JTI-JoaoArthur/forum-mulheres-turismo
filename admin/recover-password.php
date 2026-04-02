<?php
/**
 * Recuperar senha — Orientação ao usuário
 * Fluxo: usuário contacta CGMK → CGMK reseta a senha → usuário loga e é instruído a trocar
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';

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
    <link rel="stylesheet" href="/assets/css/fontawesome-all.min.css">
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
                    Para redefinir sua senha, entre em contato com o suporte administrativo.
                </div>

                <div class="text-center mb-3">
                    <p class="mb-2"><strong><i class="fas fa-building"></i> CGMK — Coordenação-Geral de Modernização e Informática</strong></p>
                    <p class="mb-1"><i class="fas fa-envelope"></i> <a href="mailto:cgmk@turismo.gov.br" style="color:#64428c;font-weight:600;">cgmk@turismo.gov.br</a></p>
                </div>

                <hr>

                <div class="text-muted small">
                    <p class="mb-1"><strong>Como funciona:</strong></p>
                    <ol class="pl-3">
                        <li>Solicite o reset de senha à CGMK informando seu nome e e-mail de acesso</li>
                        <li>A CGMK irá redefinir sua senha para uma senha temporária</li>
                        <li>Ao fazer login com a senha temporária, você será instruído a criar uma nova senha pessoal</li>
                    </ol>
                </div>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <small><a href="/admin/" class="text-muted"><i class="fas fa-arrow-left"></i> Voltar ao login</a></small>
        </p>
    </div>
</div>
</body>
</html>
