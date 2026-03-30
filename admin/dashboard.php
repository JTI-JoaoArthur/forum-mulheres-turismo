<?php
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
Auth::require();

$pageTitle = 'Dashboard';

// Contadores para o dashboard
$counts = [
    'speakers' => Database::fetchOne("SELECT COUNT(*) as n FROM speakers")['n'],
    'schedule' => Database::fetchOne("SELECT COUNT(*) as n FROM schedule")['n'],
    'news'     => Database::fetchOne("SELECT COUNT(*) as n FROM news")['n'],
    'carousel' => Database::fetchOne("SELECT COUNT(*) as n FROM carousel")['n'],
    'gallery'  => Database::fetchOne("SELECT COUNT(*) as n FROM gallery")['n'],
    'sponsors' => Database::fetchOne("SELECT COUNT(*) as n FROM sponsors")['n'],
];

// Últimas ações (somente admin)
$recentLogs = [];
if (Auth::isAdmin()) {
    $recentLogs = Database::fetchAll(
        "SELECT al.*, u.name as user_name
         FROM audit_log al LEFT JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC LIMIT 10"
    );
}

require __DIR__ . '/templates/header.php';
?>

<h2 class="mb-4">Dashboard</h2>

<div class="row mb-4">
    <?php
    $cards = [
        ['Palestrantes', $counts['speakers'], 'fas fa-users',       'speakers.php',  '#64428c'],
        ['Programação',  $counts['schedule'],  'fas fa-calendar',    'schedule.php',  '#2196F3'],
        ['Notícias',     $counts['news'],      'fas fa-newspaper',   'news.php',      '#4CAF50'],
        ['Carrossel',    $counts['carousel'],  'fas fa-images',      'carousel.php',  '#FF9800'],
        ['Galeria',      $counts['gallery'],   'fas fa-photo-video', 'gallery.php',   '#00BCD4'],
        ['Apoio',        $counts['sponsors'],  'fas fa-handshake',   'sponsors.php',  '#9C27B0'],
    ];
    foreach ($cards as [$title, $count, $icon, $link, $color]): ?>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <a href="/admin/<?= $link ?>" class="card dashboard-card text-decoration-none">
            <div class="card-body text-center">
                <i class="<?= $icon ?> fa-2x mb-2" style="color: <?= $color ?>"></i>
                <h5 class="card-title mb-0"><?= $count ?></h5>
                <small class="text-muted"><?= $title ?></small>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <?php if (Auth::isAdmin()): ?>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Atividade Recente</h6></div>
            <div class="card-body p-0">
                <?php if (empty($recentLogs)): ?>
                    <p class="text-muted p-3">Nenhuma atividade registrada.</p>
                <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ação</th>
                                <th>Usuário</th>
                                <th>Detalhes</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></td>
                                <td class="text-truncate" style="max-width: 300px"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($log['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="<?= Auth::isAdmin() ? 'col-lg-4' : 'col-lg-12' ?>">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Links Rápidos</h6></div>
            <div class="card-body">
                <a href="/admin/settings.php" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                    <i class="fas fa-cog"></i> Configurações do Site
                </a>
                <a href="/" target="_blank" class="btn btn-outline-primary btn-sm btn-block mb-2">
                    <i class="fas fa-external-link-alt"></i> Ver Site
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
