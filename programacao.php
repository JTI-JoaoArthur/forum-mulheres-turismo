<?php
require_once __DIR__ . '/includes/site.php';

$day1 = $day2 = [];
if (dbReady()) {
    $day1 = Database::fetchAll("SELECT * FROM schedule WHERE day = 1 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
    $day2 = Database::fetchAll("SELECT * FROM schedule WHERE day = 2 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
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
    <link rel="stylesheet" href="assets/css/custom.min.css">
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

    <section class="accordion fix section-padding30">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <h2>Programação do Evento</h2>
                    <p><?= site('contact_venue') ?> — <?= site('footer_date') ?></p>
                </div>
            </div>
            <div class="row">
               <div class="col-lg-11">
                    <div class="properties__button mb-40">
                        <nav aria-label="Abas de programação">
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Dia 1 — 3 de Junho</a>
                                <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Dia 2 — 4 de Junho</a>
                            </div>
                        </nav>
                    </div>
               </div>
            </div>
        </div>
        <div class="container">
            <div class="tab-content" id="nav-tabContent">
                <?php foreach ([1 => $day1, 2 => $day2] as $dayNum => $items): ?>
                <div class="tab-pane fade<?= $dayNum === 1 ? ' show active' : '' ?>" id="<?= $dayNum === 1 ? 'nav-home' : 'nav-profile' ?>" role="tabpanel">
                   <div class="row">
                        <div class="col-lg-11">
                            <div class="accordion-wrapper">
                                <div class="accordion" id="accordionDia<?= $dayNum ?>">
                                    <?php if (empty($items)): ?>
                                    <div class="card programacao-item programacao-default">
                                        <div class="card-header" id="headingDefaultS<?= $dayNum ?>">
                                            <h2 class="mb-0">
                                                <a href="#" class="btn-link" data-toggle="collapse" data-target="#collapseDefaultS<?= $dayNum ?>" aria-expanded="true">
                                                    <span>00:00 - 00:00</span>
                                                    <p>Nome da Atividade</p>
                                                </a>
                                            </h2>
                                        </div>
                                        <div id="collapseDefaultS<?= $dayNum ?>" class="collapse show" data-parent="#accordionDia<?= $dayNum ?>">
                                            <div class="card-body">
                                                <strong>Local:</strong> A definir<br>
                                                Descrição da atividade será inserida aqui pelo CMS.
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($items as $idx => $item): ?>
                                        <div class="card">
                                            <div class="card-header" id="headingS<?= $dayNum ?>_<?= $item['id'] ?>">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link<?= $idx > 0 ? ' collapsed' : '' ?>" data-toggle="collapse" data-target="#collapseS<?= $dayNum ?>_<?= $item['id'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                                        <span><?= htmlspecialchars($item['start_time']) ?> - <?= htmlspecialchars($item['end_time']) ?></span>
                                                        <p><?= htmlspecialchars($item['title']) ?></p>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapseS<?= $dayNum ?>_<?= $item['id'] ?>" class="collapse<?= $idx === 0 ? ' show' : '' ?>" data-parent="#accordionDia<?= $dayNum ?>">
                                                <div class="card-body">
                                                    <?php if ($item['location']): ?><strong>Local:</strong> <?= htmlspecialchars($item['location']) ?><br><?php endif; ?>
                                                    <?= htmlspecialchars($item['description'] ?: '') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
