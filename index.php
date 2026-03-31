<?php
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/album.php';

// Dados dinâmicos
$aboutTitle = siteRaw('about_title') ?: 'Fórum de Mulheres no Turismo';
$aboutBody  = siteRaw('about_body');
$aboutImg1  = siteRaw('about_image1') ?: 'assets/img/galeria/about1.png';
$aboutImg2  = siteRaw('about_image2') ?: 'assets/img/galeria/about2.png';
$eventDate  = siteRaw('event_date') ?: 'June 3, 2026 09:00:00';

// Palestrantes
$speakers = [];
if (dbReady()) {
    $speakers = Database::fetchAll("SELECT * FROM speakers WHERE is_visible = 1 ORDER BY display_order ASC, name ASC LIMIT 6");
}

// Programação
$day1 = $day2 = [];
if (dbReady()) {
    $day1 = Database::fetchAll("SELECT * FROM schedule WHERE day = 1 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
    $day2 = Database::fetchAll("SELECT * FROM schedule WHERE day = 2 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
}

// Carrossel: slides automáticos (notícias destaque) + manuais
$carouselSlides = [];
if (dbReady()) {
    $featured = Database::fetchAll("SELECT featured_image as image, ('noticia.php?slug=' || slug) as link, video_url, is_pinned, 0 as display_order FROM news WHERE is_featured = 1 AND is_visible = 1 AND (featured_image IS NOT NULL AND featured_image != '' OR video_url IS NOT NULL AND video_url != '') ORDER BY published_at DESC");
    $manual = Database::fetchAll("SELECT image, link, video_url, is_pinned, display_order FROM carousel WHERE is_visible = 1 ORDER BY display_order ASC");
    // Slides fixados primeiro, depois automáticos intercalados com manuais não-fixados
    $pinned = array_filter($manual, fn($s) => $s['is_pinned']);
    $unpinned = array_filter($manual, fn($s) => !$s['is_pinned']);
    $carouselSlides = array_merge($pinned, $featured, $unpinned);
}

// Álbum de fotos
$albumPhotos = getAlbumPhotos();

// Apoio e Realização
$sponsors = [];
if (dbReady()) {
    $sponsors = Database::fetchAll("SELECT * FROM sponsors WHERE is_visible = 1 ORDER BY display_order ASC, name ASC");
}

// Notícias recentes
$recentNews = [];
if (dbReady()) {
    $recentNews = Database::fetchAll("SELECT * FROM news WHERE is_visible = 1 ORDER BY published_at DESC LIMIT 4");
}

$mesesPt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= site('site_title') ?> — Ministério do Turismo | ONU Turismo</title>
    <meta name="description" content="<?= site('site_title') ?> — <?= site('footer_date') ?> — <?= site('footer_location') ?>. Uma realização do Ministério do Turismo e ONU Turismo.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="<?= site('site_title') ?> — Ministério do Turismo | ONU Turismo">
    <meta property="og:description" content="<?= site('footer_date') ?> — <?= site('footer_location') ?>. Lideranças femininas debatendo o futuro do turismo.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/">
    <link rel="canonical" href="https://forumdeturismo.gov.br/">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicons/favicon-32x32.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/favicons/favicon-48x48.png">
    <link rel="manifest" href="site.webmanifest">
    <meta name="theme-color" content="#64428c">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Event",
        "name": "<?= site('site_title') ?>",
        "description": "<?= site('site_title') ?> — lideranças femininas debatendo o futuro do turismo. Uma realização do Ministério do Turismo e ONU Turismo.",
        "startDate": "2026-06-03T09:00:00-03:00",
        "endDate": "2026-06-04T18:00:00-03:00",
        "eventStatus": "https://schema.org/EventScheduled",
        "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
        "location": {
            "@type": "Place",
            "name": "<?= site('contact_venue') ?>",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "João Pessoa",
                "addressRegion": "PB",
                "addressCountry": "BR"
            }
        },
        "image": "https://forumdeturismo.gov.br/assets/img/destaque/save-the-date-forum.jpg",
        "organizer": [
            {"@type": "Organization", "name": "Ministério do Turismo", "url": "https://www.gov.br/turismo"},
            {"@type": "Organization", "name": "ONU Turismo", "url": "https://www.unwto.org"}
        ],
        "inLanguage": "pt-BR"
    }
    </script>

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" integrity="sha384-gAPqlBuTCdtVcYt9ocMOYWrnBZ4XSL6q+4eXqwNycOr4iFczhNKtnYhF3NEXJM51" crossorigin="anonymous">
</head>
<body>
<?php require __DIR__ . '/includes/header.php'; ?>

    <main id="main-content">
    <!-- Hero Principal -->
    <div class="hero-principal">
        <img src="assets/img/destaque/save-the-date-forum.jpg" alt="Save the Date — <?= site('site_title') ?>" class="hero-img">
        <div class="hero-content hero-content--no-title">
            <div id="contador" class="countdown-timer"></div>
        </div>
    </div>

    <!-- Carrossel de Destaques -->
    <div class="swiper mySwiper" id="carrossel-destaques">
        <div class="swiper-wrapper">
            <?php foreach ($carouselSlides as $slide):
                $videoEmbed = '';
                $videoThumb = '';
                if (!empty($slide['video_url'])) {
                    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $slide['video_url'], $m)) {
                        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0';
                        $videoThumb = 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg';
                    } elseif (preg_match('#vimeo\.com/(\d+)#', $slide['video_url'], $m)) {
                        $videoEmbed = 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
                    }
                }
                $thumbSrc = !empty($slide['image']) ? '/' . htmlspecialchars($slide['image']) : ($videoThumb ?: '');
            ?>
            <div class="swiper-slide"<?= !empty($slide['link']) && !$videoEmbed ? ' data-href="' . htmlspecialchars($slide['link']) . '"' : '' ?>>
                <?php if ($videoEmbed && $thumbSrc): ?>
                <a href="<?= htmlspecialchars($videoEmbed) ?>" class="video-popup" style="display:block;width:100%;position:relative;">
                    <img src="<?= $thumbSrc ?>" alt="Vídeo" style="width:100%;height:auto;display:block;">
                    <span class="video-play-btn"><i class="fas fa-play"></i></span>
                </a>
                <?php elseif (!empty($slide['image'])): ?>
                <img src="/<?= htmlspecialchars($slide['image']) ?>" alt="Slide do carrossel" style="width:100%;height:auto;display:block;">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-button-next" style="color: #fff;"></div>
        <div class="swiper-button-prev" style="color: #fff;"></div>
        <div class="swiper-pagination"></div>
    </div>

    <!-- Sobre o Evento -->
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
                            <p>O Fórum de Mulheres no Turismo é uma iniciativa conjunta do Ministério do Turismo e da ONU Turismo, reunindo lideranças femininas para debater o papel da mulher no setor turístico brasileiro e internacional.</p>
                            <p>Dois dias de painéis, palestras e networking no <?= site('contact_venue') ?>.</p>
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
                <div class="col-lg-6 col-md-12 text-center">
                    <div class="about-img">
                        <div class="about-font-img d-none d-lg-block">
                            <img class="img-fluid d-block mx-auto" src="<?= htmlspecialchars($aboutImg2) ?>" alt="Participantes do <?= site('site_title') ?>">
                        </div>
                        <div class="about-back-img">
                            <img class="img-fluid d-block mx-auto" src="<?= htmlspecialchars($aboutImg1) ?>" alt="Plenário do <?= site('site_title') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Palestrantes -->
    <section class="team-area pt-180 pb-100 section-bg" data-background="assets/img/galeria/section_bg02.png">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-tittle section-tittle2 mb-50">
                        <h2>Palestrantes Confirmados</h2>
                        <p>Conheça as lideranças que participarão do <?= site('site_title') ?>.</p>
                        <a href="palestrantes.php" class="btn white-btn mt-30">Ver Todos</a>
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
                        <div class="team-caption">
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
                            <div class="team-caption">
                                <h3><a href="palestrantes.php"><?= htmlspecialchars($sp['name']) ?></a></h3>
                                <p><?= htmlspecialchars(($sp['position'] ?: '') . ($sp['institution'] ? ' / ' . $sp['institution'] : '')) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Programação -->
    <section class="accordion fix section-padding30">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-6">
                    <div class="section-tittle text-center mb-80">
                        <h2>Programação do Evento</h2>
                        <p>Confira a programação dos dois dias do <?= site('site_title') ?>.</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
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
                   <div class="row justify-content-center">
                        <div class="col-lg-11">
                            <div class="accordion-wrapper">
                                <div class="accordion" id="accordionDay<?= $dayNum ?>">
                                    <?php if (empty($items)): ?>
                                    <div class="card programacao-item programacao-default">
                                        <div class="card-header" id="headingDefault<?= $dayNum ?>">
                                            <h2 class="mb-0">
                                                <a href="#" class="btn-link" data-toggle="collapse" data-target="#collapseDefault<?= $dayNum ?>" aria-expanded="true">
                                                    <span>00:00 - 00:00</span>
                                                    <p>Nome da Atividade</p>
                                                </a>
                                            </h2>
                                        </div>
                                        <div id="collapseDefault<?= $dayNum ?>" class="collapse show" data-parent="#accordionDay<?= $dayNum ?>">
                                            <div class="card-body">
                                                <strong>Local:</strong> A definir<br>
                                                Descrição da atividade será inserida aqui pelo CMS.
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($items as $idx => $item): ?>
                                        <div class="card">
                                            <div class="card-header" id="heading<?= $dayNum ?>_<?= $item['id'] ?>">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link<?= $idx > 0 ? ' collapsed' : '' ?>" data-toggle="collapse" data-target="#collapse<?= $dayNum ?>_<?= $item['id'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                                        <span><?= htmlspecialchars($item['start_time']) ?> - <?= htmlspecialchars($item['end_time']) ?></span>
                                                        <p><?= htmlspecialchars($item['title']) ?></p>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapse<?= $dayNum ?>_<?= $item['id'] ?>" class="collapse<?= $idx === 0 ? ' show' : '' ?>" data-parent="#accordionDay<?= $dayNum ?>">
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

    <!-- Álbum de Fotos -->
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

    <!-- Apoio e Realização -->
    <section class="work-company section-padding30" style="background: #64428c;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 col-md-8">
                    <div class="section-tittle section-tittle2 mb-50">
                        <h2>Apoio e Realização</h2>
                        <p>O <?= site('site_title') ?> é uma realização do Ministério do Turismo e ONU Turismo.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="logo-area">
                        <div class="row apoio-logos">
                            <?php if (empty($sponsors)): ?>
                            <div class="col-lg-4 col-md-4 col-sm-6 apoio-item">
                                <div class="single-logo mb-30"><img src="assets/img/logos/ONU-Turismo.png" alt="ONU Turismo"></div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6 apoio-item">
                                <div class="single-logo mb-30"><img src="assets/img/logos/vertical-principal.png" alt="Ministério do Turismo"></div>
                            </div>
                            <?php else: ?>
                                <?php foreach ($sponsors as $sp): ?>
                                <div class="col-lg-4 col-md-4 col-sm-6 apoio-item">
                                    <div class="single-logo mb-30">
                                        <?php if ($sp['website']): ?><a href="<?= htmlspecialchars($sp['website']) ?>" target="_blank" rel="noopener"><?php endif; ?>
                                        <img src="/<?= htmlspecialchars($sp['logo']) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                                        <?php if ($sp['website']): ?></a><?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Notícias -->
    <section class="home-blog-area section-padding30">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8">
                    <div class="section-tittle text-center mb-50">
                        <h2>Notícias do Fórum</h2>
                        <p>Acompanhe as últimas novidades sobre o <?= site('site_title') ?>.</p>
                    </div>
                </div>
            </div>
            <div class="row noticias-grid">
                <?php if (empty($recentNews)): ?>
                <div class="col-6 col-lg-6 blog-item blog-default">
                    <div class="home-blog-single mb-30">
                        <div class="blog-img-cap">
                            <div class="blog-img">
                                <img src="assets/img/galeria/home-blog1.png" alt="Destaque de notícia">
                                <div class="blog-date text-center"><span>00</span><p>Mês</p></div>
                            </div>
                            <div class="blog-cap">
                                <h3><a href="#">Título da Notícia</a></h3>
                                <a href="#" class="more-btn">Leia mais »</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($recentNews as $n):
                        $pubDate = strtotime($n['published_at']);
                        $dia = date('d', $pubDate);
                        $mes = $mesesPt[(int)date('n', $pubDate) - 1];
                    ?>
                    <div class="col-6 col-lg-6 blog-item">
                        <div class="home-blog-single mb-30">
                            <div class="blog-img-cap">
                                <div class="blog-img">
                                    <?php if ($n['featured_image']): ?>
                                        <img src="/<?= htmlspecialchars($n['featured_image']) ?>" alt="<?= htmlspecialchars($n['title']) ?>">
                                    <?php else: ?>
                                        <img src="assets/img/galeria/home-blog1.png" alt="<?= htmlspecialchars($n['title']) ?>">
                                    <?php endif; ?>
                                    <div class="blog-date text-center"><span><?= $dia ?></span><p><?= $mes ?></p></div>
                                </div>
                                <div class="blog-cap">
                                    <h3><a href="noticia.php?slug=<?= htmlspecialchars($n['slug']) ?>"><?= htmlspecialchars($n['title']) ?></a></h3>
                                    <a href="noticia.php?slug=<?= htmlspecialchars($n['slug']) ?>" class="more-btn">Leia mais »</a>
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" integrity="sha384-2UI1PfnXFjVMQ7/ZDEF70CR943oH3v6uZrFQGGqJYlvhh4g6z6uVktxYbOlAczav" crossorigin="anonymous"></script>
    <script>
        var dataDoEvento = new Date("<?= htmlspecialchars($eventDate) ?>").getTime();
        var albumFotos = <?= json_encode(array_values($albumPhotos)) ?>;
    </script>
    <script src="./assets/js/home.js"></script>
</body>
</html>
