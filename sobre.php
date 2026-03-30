<?php
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/album.php';

$aboutTitle = siteRaw('about_title') ?: 'Fórum de Mulheres no Turismo';
$aboutBody  = siteRaw('about_body');
$aboutImg1  = siteRaw('about_image1') ?: 'assets/img/galeria/about1.png';
$aboutImg2  = siteRaw('about_image2') ?: 'assets/img/galeria/about2.png';
$albumPhotos = getAlbumPhotos();
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
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                            <div class="single-caption mb-20">
                                <div class="caption-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div class="caption">
                                    <h5>Local</h5>
                                    <p><?= site('contact_venue') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
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
                            <img src="<?= htmlspecialchars($aboutImg2) ?>" alt="Participantes do <?= site('site_title') ?>">
                        </div>
                        <div class="about-back-img">
                            <img src="<?= htmlspecialchars($aboutImg1) ?>" alt="Plenário do <?= site('site_title') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="gallery-area fix" id="album-fotos">
        <div class="container-fluid p-0">
            <div class="row no-gutters">
                <?php
                $slotSizes = ['col-lg-3 col-md-3 col-sm-6','col-lg-3 col-md-3 col-sm-6','col-lg-6 col-md-6 col-sm-6','col-lg-6 col-md-6 col-sm-6','col-lg-3 col-md-3 col-sm-6','col-lg-3 col-md-3 col-sm-6'];
                for ($i = 0; $i < 6; $i++):
                    $img = $albumPhotos[$i % count($albumPhotos)];
                ?>
                <div class="<?= $slotSizes[$i] ?>">
                    <div class="gallery-box">
                        <div class="single-gallery">
                            <a href="<?= htmlspecialchars($img) ?>" class="album-popup">
                                <div class="gallery-img gallery-slot" data-slot="<?= $i ?>" style="background-image: url(<?= htmlspecialchars($img) ?>);"></div>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
    <script>
        var albumFotos = <?= json_encode(array_values($albumPhotos)) ?>;
        (function initAlbumSequencial() {
            var slots = document.querySelectorAll('.gallery-slot');
            if (!slots.length || albumFotos.length < 2) return;
            var INTERVALO = 3000, slotAtual = 0, indices = [];
            for (var i = 0; i < slots.length; i++) indices.push(i % albumFotos.length);
            function trocarProximo() {
                indices[slotAtual] = (indices[slotAtual] + 1) % albumFotos.length;
                var novaImg = albumFotos[indices[slotAtual]];
                slots[slotAtual].style.backgroundImage = 'url(' + novaImg + ')';
                var link = slots[slotAtual].closest('.album-popup');
                if (link) link.setAttribute('href', novaImg);
                slotAtual = (slotAtual + 1) % slots.length;
            }
            setInterval(trocarProximo, INTERVALO);
        })();
        $(document).ready(function() {
            $('.album-popup').magnificPopup({
                type: 'image',
                gallery: { enabled: true, tCounter: '%curr% de %total%' },
                image: { titleSrc: function() { return 'Álbum de Fotos — <?= site('site_title') ?>'; } }
            });
        });
    </script>
</body>
</html>
