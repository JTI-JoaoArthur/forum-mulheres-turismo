<?php
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/album.php';

$aboutTitle = siteRaw('about_title') ?: 'Fórum de Mulheres no Turismo';
$aboutBody  = siteRaw('about_body');
$aboutImg1  = siteRaw('about_image1') ?: 'assets/img/galeria/about1.svg';
$aboutImg2  = siteRaw('about_image2') ?: 'assets/img/galeria/about2.svg';
$albumData = getAlbumPhotos();
$albumPhotos = $albumData['photos'];
$albumRealCount = $albumData['realCount'];
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Sobre o Evento — <?= site('site_title') ?></title>
    <meta name="description" content="Saiba mais sobre o <?= site('site_title') ?>, uma iniciativa do Ministério do Turismo e ONU Turismo em João Pessoa.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="Sobre o Evento — <?= site('site_title') ?>">
    <meta property="og:description" content="Conheça o <?= site('site_title') ?> — uma iniciativa do Ministério do Turismo e ONU Turismo em João Pessoa.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/sobre.php">
    <link rel="canonical" href="https://forumdeturismo.gov.br/sobre.php">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" integrity="sha384-gAPqlBuTCdtVcYt9ocMOYWrnBZ4XSL6q+4eXqwNycOr4iFczhNKtnYhF3NEXJM51" crossorigin="anonymous">
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
                            <h2>Sobre o Evento</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="about-low-area section-padding2">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-12">
                    <div class="about-caption mb-50">
                        <div class="section-tittle mb-35">
                            <h2><?= htmlspecialchars($aboutTitle) ?></h2>
                        </div>
                        <?php if ($aboutBody): ?>
                            <?= sanitizeHtml($aboutBody) ?>
                        <?php else: ?>
                            <p>O Fórum de Mulheres no Turismo é uma iniciativa conjunta do Ministério do Turismo e da ONU Turismo, com o objetivo de promover a igualdade de gênero e o empoderamento feminino no setor turístico. O evento reunirá lideranças, especialistas e profissionais para debater políticas públicas, boas práticas e oportunidades para mulheres no turismo.</p>
                            <p>Durante dois dias de programação, participantes terão acesso a painéis, palestras e rodas de conversa sobre temas como liderança feminina, empreendedorismo, sustentabilidade e inclusão no turismo. O evento é de acesso gratuito.</p>
                        <?php endif; ?>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <a href="contato.php#mapa" class="single-caption mb-20" style="text-decoration:none;color:inherit;">
                                <div class="caption-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div class="caption">
                                    <h5>Local</h5>
                                    <p><?= site('contact_venue') ?></p>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="single-caption mb-20">
                                <div class="caption-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="caption">
                                    <h5>Data</h5>
                                    <p><?= site('footer_date') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="programacao.php" class="btn mt-50">Ver Programação</a>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="about-img">
                        <div class="about-font-img d-none d-lg-block">
                            <img class="img-fluid" src="<?= htmlspecialchars($aboutImg2) ?>" alt="Participantes do <?= site('site_title') ?>">
                        </div>
                        <div class="about-back-img">
                            <img class="img-fluid" src="<?= htmlspecialchars($aboutImg1) ?>" alt="Plenário do <?= site('site_title') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Galeria de Fotos -->
    <?php if (!empty($albumPhotos)): ?>
    <section style="background: #f8f6fb; padding: 30px 0 0;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8">
                    <div class="section-tittle text-center mb-50">
                        <h2>Galeria de Fotos</h2>
                    </div>
                </div>
            </div>
            <div class="gallery-carousel">
                <!-- Carrossel principal -->
                <div class="swiper galleryMain">
                    <div class="swiper-wrapper">
                        <?php foreach ($albumPhotos as $i => $img): ?>
                        <div class="swiper-slide">
                            <div class="gallery-main-slide">
                                <img src="<?= htmlspecialchars($img) ?>" alt="Foto <?= $i + 1 ?> do <?= site('site_title') ?>" loading="lazy">
                                <span class="gallery-counter"><?= $i + 1 ?> / <?= count($albumPhotos) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($albumPhotos) > 1): ?>
                    <div class="swiper-button-next gallery-nav" aria-label="Próxima foto"></div>
                    <div class="swiper-button-prev gallery-nav" aria-label="Foto anterior"></div>
                    <?php endif; ?>
                </div>
                <?php if (count($albumPhotos) > 1): ?>
                <!-- Thumbnails -->
                <div class="swiper galleryThumbs mt-3">
                    <div class="swiper-wrapper">
                        <?php foreach ($albumPhotos as $i => $img): ?>
                        <div class="swiper-slide">
                            <img src="<?= htmlspecialchars($img) ?>" alt="" class="gallery-thumb-img" loading="lazy">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" integrity="sha384-2UI1PfnXFjVMQ7/ZDEF70CR943oH3v6uZrFQGGqJYlvhh4g6z6uVktxYbOlAczav" crossorigin="anonymous"></script>
    <script>
    (function() {
        var mainEl = document.querySelector('.galleryMain');
        if (!mainEl) return;

        var thumbsEl = document.querySelector('.galleryThumbs');
        var thumbsSwiper = null;
        var mainOpts = {
            spaceBetween: 0,
        };

        if (thumbsEl) {
            thumbsSwiper = new Swiper('.galleryThumbs', {
                spaceBetween: 8,
                slidesPerView: 'auto',
                freeMode: true,
                watchSlidesProgress: true,
            });
            mainOpts.loop = true;
            mainOpts.autoplay = { delay: 5000, disableOnInteraction: true };
            mainOpts.navigation = {
                nextEl: '.galleryMain .swiper-button-next',
                prevEl: '.galleryMain .swiper-button-prev',
            };
            mainOpts.thumbs = { swiper: thumbsSwiper };
        }

        var mainSwiper = new Swiper('.galleryMain', mainOpts);

        if (thumbsEl) {
            mainEl.addEventListener('mouseenter', function() { mainSwiper.autoplay.stop(); });
            mainEl.addEventListener('mouseleave', function() { mainSwiper.autoplay.start(); });
        }

        // Fullscreen ao clicar na imagem
        mainEl.addEventListener('click', function(e) {
            if (e.target.closest('.swiper-button-next, .swiper-button-prev')) return;
            var img = e.target.closest('.gallery-main-slide img');
            if (!img) return;
            $.magnificPopup.open({
                items: <?= json_encode(array_map(fn($p) => ['src' => $p, 'type' => 'image'], $albumPhotos)) ?>,
                type: 'image',
                gallery: { enabled: <?= count($albumPhotos) > 1 ? 'true' : 'false' ?>, tCounter: '%curr% de %total%' },
                image: { titleSrc: function() { return 'Galeria — <?= site('site_title') ?>'; } }
            }, mainSwiper.realIndex || 0);
        });
    })();
    </script>
</body>
</html>
