<?php
require_once __DIR__ . '/includes/site.php';

$speakers = [];
if (dbReady()) {
    $speakers = Database::fetchAll("SELECT * FROM speakers WHERE is_visible = 1 ORDER BY display_order ASC, name ASC");
}
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Palestrantes — <?= site('site_title') ?></title>
    <meta name="description" content="Conheça as palestrantes confirmadas para o <?= site('site_title') ?> — <?= site('footer_date') ?>.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="Palestrantes — <?= site('site_title') ?>">
    <meta property="og:description" content="Conheça as lideranças femininas confirmadas para o <?= site('site_title') ?> — <?= site('footer_date') ?>.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/palestrantes.php">
    <link rel="canonical" href="https://forumdeturismo.gov.br/palestrantes.php">
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
                            <h2>Palestrantes</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="team-area pt-180 pb-100">
        <div class="container">
            <div class="row justify-content-center mb-60">
                <div class="col-lg-8 text-center">
                    <div class="section-tittle">
                        <h2>Palestrantes Confirmados</h2>
                        <p>Conheça as especialistas que irão compartilhar suas experiências e visões sobre o papel da mulher no turismo.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php if (empty($speakers)): ?>
                <div class="col-lg-4 col-md-4 col-sm-4 palestrante-item palestrante-default text-center">
                    <div class="single-team mb-30">
                        <div class="team-img">
                            <img class="img-fluid d-block mx-auto w-100" src="assets/img/galeria/team1.png" alt="Palestrante">
                            <ul class="team-social">
                                <li data-social="linkedin"><a href="#"><i class="fab fa-linkedin"></i></a></li>
                                <li data-social="instagram"><a href="#"><i class="fab fa-instagram"></i></a></li>
                                <li data-social="site"><a href="#"><i class="fas fa-globe"></i></a></li>
                            </ul>
                        </div>
                        <div class="team-caption team-caption2">
                            <h3><a href="#">Nome da Palestrante</a></h3>
                            <p>Cargo / Instituição</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($speakers as $sp): ?>
                    <div class="col-lg-4 col-md-4 col-sm-4 text-center">
                        <div class="single-team mb-30">
                            <div class="team-img">
                                <?php if ($sp['photo']): ?>
                                    <img class="img-fluid d-block mx-auto w-100" src="/<?= htmlspecialchars($sp['photo']) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                                <?php else: ?>
                                    <img class="img-fluid d-block mx-auto w-100" src="assets/img/galeria/team1.png" alt="<?= htmlspecialchars($sp['name']) ?>">
                                <?php endif; ?>
                                <ul class="team-social">
                                    <?php if ($sp['linkedin']): ?><li data-social="linkedin"><a href="<?= htmlspecialchars($sp['linkedin']) ?>"><i class="fab fa-linkedin"></i></a></li><?php endif; ?>
                                    <?php if ($sp['instagram']): ?><li data-social="instagram"><a href="<?= htmlspecialchars($sp['instagram']) ?>"><i class="fab fa-instagram"></i></a></li><?php endif; ?>
                                    <?php if ($sp['website']): ?><li data-social="site"><a href="<?= htmlspecialchars($sp['website']) ?>"><i class="fas fa-globe"></i></a></li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="team-caption team-caption2">
                                <h3><a href="#"><?= htmlspecialchars($sp['name']) ?></a></h3>
                                <p><?= htmlspecialchars(($sp['position'] ?: '') . ($sp['institution'] ? ' / ' . $sp['institution'] : '')) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
    <script>
        document.querySelectorAll(".team-social li[data-social]").forEach(function(li) {
            var link = li.querySelector("a");
            if (!link || !link.getAttribute("href") || link.getAttribute("href") === "#") {
                li.style.display = "none";
            }
        });
    </script>
</body>
</html>
