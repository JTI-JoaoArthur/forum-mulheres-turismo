<?php
require_once __DIR__ . '/includes/site.php';

$day1 = $day2 = [];
if (dbReady()) {
    $day1 = Database::fetchAll("SELECT * FROM schedule WHERE day = 1 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
    $day2 = Database::fetchAll("SELECT * FROM schedule WHERE day = 2 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
}
$day1Blocks = groupByTimeSlot($day1);
$day2Blocks = groupByTimeSlot($day2);

// Dia ativo: dia 2 se já passou o dia 3 ou todas as atividades do dia 1
$eventDay1 = '2026-06-03';
$eventDay2 = '2026-06-04';
$nowDate = date('Y-m-d');
$nowTime = date('H:i');
$activeDay = 1;
if ($nowDate >= $eventDay2) {
    $activeDay = 2;
} elseif ($nowDate === $eventDay1) {
    $lastEnd = '';
    foreach ($day1 as $item) {
        if ($item['end_time'] > $lastEnd) $lastEnd = $item['end_time'];
    }
    if ($lastEnd && $nowTime > $lastEnd) $activeDay = 2;
}
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Programação — <?= site('site_title') ?></title>
    <meta name="description" content="Programação completa do <?= site('site_title') ?> — <?= site('footer_date') ?>, <?= site('footer_location') ?>.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="Programação — <?= site('site_title') ?>">
    <meta property="og:description" content="Programação completa do <?= site('site_title') ?> — <?= site('footer_date') ?>, <?= site('footer_location') ?>.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/programacao.php">
    <link rel="canonical" href="https://forumdeturismo.gov.br/programacao.php">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicons/favicon-32x32.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/favicons/favicon-48x48.png">
    <link rel="manifest" href="site.webmanifest">
    <meta name="theme-color" content="#64428c">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/slicknav.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/themify-icons.css">
    <link rel="stylesheet" href="assets/css/slick.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=20260401c">
</head>
<body>
<?php require __DIR__ . '/includes/header.php'; ?>
<main id="main-content">
    <div class="slider-area2">
        <div class="slider-height2 d-flex align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="hero-cap text-center">
                            <h2>Programação</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="fix section-padding30">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <h2>Programação do Evento</h2>
                    <p><?= site('contact_venue') ?> — <?= site('footer_date') ?></p>
                </div>
            </div>
            <div class="row justify-content-center">
               <div class="col-lg-11">
                    <div class="properties__button mb-40">
                        <nav aria-label="Abas de programação">
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <a class="nav-item nav-link<?= $activeDay === 1 ? ' active' : '' ?>" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="<?= $activeDay === 1 ? 'true' : 'false' ?>">3 de Junho</a>
                                <a class="nav-item nav-link<?= $activeDay === 2 ? ' active' : '' ?>" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="<?= $activeDay === 2 ? 'true' : 'false' ?>">4 de Junho</a>
                            </div>
                        </nav>
                    </div>
               </div>
            </div>
            <div class="tab-content" id="nav-tabContent">
                <?php foreach ([1 => $day1Blocks, 2 => $day2Blocks] as $dayNum => $blocks): ?>
                <div class="tab-pane fade<?= $dayNum === $activeDay ? ' show active' : '' ?>" id="<?= $dayNum === 1 ? 'nav-home' : 'nav-profile' ?>" role="tabpanel">
                    <div class="schedule-timeline">
                        <?php if (empty($blocks)): ?>
                        <div class="time-block">
                            <div class="time-block__time">
                                <span class="time-block__start">00:00</span>
                                <span class="time-block__separator">—</span>
                                <span class="time-block__end">00:00</span>
                            </div>
                            <div class="time-block__sessions single-track">
                                <div class="session-card programacao-default">
                                    <span class="session-card__location"><i class="fas fa-map-marker-alt"></i> A definir</span>
                                    <h4 class="session-card__title">Nome da Atividade</h4>
                                    <p class="session-card__description">Descrição da atividade será inserida aqui pelo CMS.</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach ($blocks as $block): ?>
                            <div class="time-block">
                                <div class="time-block__time">
                                    <span class="time-block__start"><?= htmlspecialchars($block['start']) ?></span>
                                    <span class="time-block__separator">—</span>
                                    <span class="time-block__end"><?= htmlspecialchars($block['end']) ?></span>
                                </div>
                                <div class="time-block__sessions<?= count($block['items']) === 1 ? ' single-track' : '' ?>">
                                    <?php foreach ($block['items'] as $item):
                                        $sessionSpeakers = getSpeakersForSchedule($item['id']);
                                    ?>
                                    <div class="session-card" <?php if (!empty($sessionSpeakers)): ?>data-speakers='<?= htmlspecialchars(json_encode(array_map(function($sp) {
                                        return ['name' => $sp['name'], 'position' => $sp['position'], 'institution' => $sp['institution'], 'photo' => $sp['photo']];
                                    }, $sessionSpeakers))) ?>' role="button" tabindex="0"<?php endif; ?>>
                                        <?php if ($item['location']): ?>
                                        <span class="session-card__location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?></span>
                                        <?php endif; ?>
                                        <h4 class="session-card__title"><?= htmlspecialchars($item['title']) ?></h4>
                                        <?php if ($item['description']): ?>
                                        <p class="session-card__description"><?= htmlspecialchars($item['description']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($sessionSpeakers)): ?>
                                        <div class="session-card__speakers">
                                            <?php foreach ($sessionSpeakers as $sp): ?>
                                            <span class="session-speaker" title="<?= htmlspecialchars($sp['name']) ?>">
                                                <?php if ($sp['photo']): ?>
                                                <img src="/<?= htmlspecialchars($sp['photo']) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                                                <?php else: ?>
                                                <i class="fas fa-user-circle"></i>
                                                <?php endif; ?>
                                                <span class="session-speaker__name"><?= htmlspecialchars($sp['name']) ?></span>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
