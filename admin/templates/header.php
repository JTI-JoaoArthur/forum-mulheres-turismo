<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle ?? 'Painel') ?> — Administração</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
</head>
<body class="admin-panel">
<nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="/admin/dashboard.php">
            <i class="fas fa-cog"></i> Painel Admin
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/speakers.php">Palestrantes</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/schedule.php">Programação</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/news.php">Notícias</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/carousel.php">Carrossel</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/gallery.php">Galeria</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/sponsors.php">Apoio</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/settings.php">Configurações</a></li>
                <?php if (Auth::isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="/admin/users.php">Usuários</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link text-light"><i class="fas fa-user"></i> <?= htmlspecialchars(Auth::user()['name'] ?? '') ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid admin-content">
    <div class="row">
        <main class="col-12 py-4">
