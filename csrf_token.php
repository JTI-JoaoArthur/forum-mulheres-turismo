<?php
/**
 * Gera e retorna um token CSRF para o formulário de contato.
 * Incluído via AJAX antes do envio do formulário.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode(['token' => $_SESSION['csrf_token']]);
