<?php
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/album.php';

// Dados dinâmicos
$aboutTitle = siteRaw('about_title') ?: 'Fórum de Mulheres no Turismo';
$aboutBody  = siteRaw('about_body');
$aboutImg1  = siteRaw('about_image1') ?: 'assets/img/galeria/about1.svg';
$aboutImg2  = siteRaw('about_image2') ?: 'assets/img/galeria/about2.svg';
$eventDate  = siteRaw('event_date') ?: '2026-06-03T09:00:00';

// Palestrantes
$speakers = [];
if (dbReady()) {
    $speakers = Database::fetchAll("SELECT * FROM speakers WHERE is_visible = 1 ORDER BY display_order ASC, name ASC LIMIT 20");
}

// Programação — com janela inteligente de 3h para o index
$day1All = $day2All = [];
if (dbReady()) {
    $day1All = Database::fetchAll("SELECT * FROM schedule WHERE day = 1 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
    $day2All = Database::fetchAll("SELECT * FROM schedule WHERE day = 2 AND is_visible = 1 ORDER BY display_order ASC, start_time ASC");
}

// Datas reais do evento
$eventDay1 = '2026-06-03';
$eventDay2 = '2026-06-04';
$now = time();
$nowDate = date('Y-m-d');
$nowTime = date('H:i');

// Determinar qual dia mostrar ativo e filtrar janela de 3h
$activeDay = 1; // padrão

// Se já passou o dia 3 inteiro (estamos no dia 4 ou depois), ativar dia 2
if ($nowDate >= $eventDay2) {
    $activeDay = 2;
} elseif ($nowDate === $eventDay1) {
    // Estamos no dia 1: verificar se todas as programações já passaram
    $lastEndDay1 = '';
    foreach ($day1All as $item) {
        if ($item['end_time'] > $lastEndDay1) $lastEndDay1 = $item['end_time'];
    }
    if ($lastEndDay1 && $nowTime > $lastEndDay1) {
        $activeDay = 2; // Dia 1 encerrado, mostrar dia 2
    }
}

// Filtrar blocos: próximas 3h a partir de agora (ou primeiras 3h se evento não começou)
function filterBlocksWindow(array $items, string $eventDate, int $windowHours = 3): array {
    $now = time();
    $nowDate = date('Y-m-d');
    $nowTime = date('H:i');

    // Se ainda não chegou a data do evento, mostrar as primeiras 3h
    if ($nowDate < $eventDate) {
        // Pegar o horário mais cedo
        $earliest = '23:59';
        foreach ($items as $item) {
            if ($item['start_time'] < $earliest) $earliest = $item['start_time'];
        }
        $cutoff = date('H:i', strtotime($earliest) + $windowHours * 3600);
        $filtered = [];
        foreach ($items as $item) {
            if ($item['start_time'] < $cutoff) $filtered[] = $item;
        }
        return $filtered;
    }

    // Se é o dia do evento, mostrar a partir de agora por 3h
    if ($nowDate === $eventDate) {
        $cutoff = date('H:i', strtotime($nowTime) + $windowHours * 3600);
        $filtered = [];
        foreach ($items as $item) {
            // Incluir se: ainda não terminou E começa antes do cutoff
            if ($item['end_time'] >= $nowTime && $item['start_time'] < $cutoff) {
                $filtered[] = $item;
            }
        }
        // Se nada restou (tudo passou), mostrar as últimas atividades
        if (empty($filtered)) {
            $filtered = array_slice($items, -3);
        }
        return $filtered;
    }

    // Se já passou o dia, mostrar tudo (retrospectivo)
    return $items;
}

$day1Filtered = filterBlocksWindow($day1All, $eventDay1);
$day2Filtered = filterBlocksWindow($day2All, $eventDay2);
$day1Blocks = groupByTimeSlot($day1Filtered);
$day2Blocks = groupByTimeSlot($day2Filtered);
// Blocos completos para a página de programação (usados apenas no programacao.php)
$day1BlocksFull = groupByTimeSlot($day1All);
$day2BlocksFull = groupByTimeSlot($day2All);

// Carrossel: exatamente 5 slots
$carouselSlides = [];
if (dbReady()) {
    $slots = array_fill(1, 5, null);
    // Slides personalizados fixados
    $pinnedManual = Database::fetchAll("SELECT image, link, video_url, video_path, display_order FROM carousel WHERE is_pinned = 1 AND display_order BETWEEN 1 AND 5 AND is_visible = 1");
    foreach ($pinnedManual as $item) $slots[(int)$item['display_order']] = $item;
    // Notícias destaque fixadas
    $pinnedNews = Database::fetchAll("SELECT featured_image as image, ('noticia.php?slug=' || slug) as link, video_url, video_path, carousel_order as display_order FROM news WHERE is_pinned = 1 AND carousel_order BETWEEN 1 AND 5 AND is_featured = 1 AND is_visible = 1 AND (featured_image IS NOT NULL AND featured_image != '' OR video_url IS NOT NULL AND video_url != '' OR video_path IS NOT NULL AND video_path != '')");
    foreach ($pinnedNews as $item) {
        $pos = (int)$item['display_order'];
        if ($slots[$pos] === null) $slots[$pos] = $item;
    }
    for ($i = 1; $i <= 5; $i++) {
        if ($slots[$i]) $carouselSlides[] = $slots[$i];
    }
}

// Álbum de fotos
$albumData = getAlbumPhotos();
$albumPhotos = $albumData['photos'];
$albumRealCount = $albumData['realCount'];

// Apoio e Realização
$sponsors = [];
if (dbReady()) {
    $sponsors = Database::fetchAll("SELECT * FROM sponsors WHERE is_visible = 1 ORDER BY display_order ASC, name ASC");
}

// Notícias recentes
$recentNews = [];
if (dbReady()) {
    $recentNews = Database::fetchAll("SELECT * FROM news WHERE is_visible = 1 ORDER BY published_at DESC LIMIT 6");
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
    <link rel="stylesheet" href="assets/css/custom.min.css?v=20260402c">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" integrity="sha384-gAPqlBuTCdtVcYt9ocMOYWrnBZ4XSL6q+4eXqwNycOr4iFczhNKtnYhF3NEXJM51" crossorigin="anonymous">
</head>
<body>
<?php require __DIR__ . '/includes/header.php'; ?>

    <main id="main-content">
    <!-- Carrossel de Destaques -->
    <div class="swiper mySwiper" id="carrossel-destaques">
        <div class="swiper-wrapper">
            <?php foreach ($carouselSlides as $slide):
                $videoEmbed = '';
                $videoThumb = '';
                $videoFile = '';

                // Video enviado (arquivo local) tem prioridade
                if (!empty($slide['video_path'])) {
                    $videoFile = '/' . htmlspecialchars($slide['video_path']);
                } elseif (!empty($slide['video_url'])) {
                    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $slide['video_url'], $m)) {
                        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&enablejsapi=1';
                        $videoThumb = 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg';
                    } elseif (preg_match('#vimeo\.com/(\d+)#', $slide['video_url'], $m)) {
                        $videoEmbed = 'https://player.vimeo.com/video/' . $m[1] . '?enablejsapi=1';
                    }
                }

                $hasVideo = $videoFile || $videoEmbed;
                $thumbSrc = !empty($slide['image']) ? '/' . htmlspecialchars($slide['image']) : ($videoThumb ?: '');
            ?>
            <div class="swiper-slide"<?= !empty($slide['link']) && !$hasVideo ? ' data-href="' . htmlspecialchars($slide['link']) . '"' : '' ?><?= $videoEmbed ? ' data-video="' . $videoEmbed . '"' : '' ?><?= $videoFile ? ' data-video-file="' . $videoFile . '"' : '' ?>>
                <?php if ($hasVideo && $thumbSrc): ?>
                <div class="slide-video-wrap">
                    <img src="<?= $thumbSrc ?>" alt="Vídeo em destaque" class="slide-thumb">
                    <button class="video-play-btn" type="button" aria-label="Reproduzir video"><i class="fas fa-play"></i></button>
                </div>
                <?php elseif ($videoFile): ?>
                <div class="slide-video-wrap">
                    <video class="slide-video-native" preload="metadata" style="width:100%;height:100%;object-fit:cover;">
                        <source src="<?= $videoFile ?>" type="video/<?= pathinfo($slide['video_path'], PATHINFO_EXTENSION) ?>">
                    </video>
                    <button class="video-play-btn" type="button" aria-label="Reproduzir video"><i class="fas fa-play"></i></button>
                </div>
                <?php elseif (!empty($slide['image'])): ?>
                <img src="/<?= htmlspecialchars($slide['image']) ?>" alt="Destaque do <?= site('site_title') ?>">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-button-next" style="color: #fff;" aria-label="Próximo slide"></div>
        <div class="swiper-button-prev" style="color: #fff;" aria-label="Slide anterior"></div>
        <div class="swiper-pagination"></div>
        <div class="carousel-countdown">
            <div id="contador" class="countdown-timer"></div>
        </div>
    </div>
    <?php if (empty($carouselSlides)): ?>
    <div class="hero-principal">
        <img src="assets/img/destaque/save-the-date-forum.jpg" alt="Save the Date — <?= site('site_title') ?>" class="hero-img">
        <div class="carousel-countdown">
            <div id="contador-fallback" class="countdown-timer"></div>
        </div>
    </div>
    <?php endif; ?>

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
                <div class="col-6 col-sm-4 palestrante-col palestrante-item palestrante-default text-center">
                    <div class="single-team mb-30">
                        <div class="team-img">
                            <img class="img-fluid d-block mx-auto w-100" src="assets/img/galeria/speaker-placeholder.svg" alt="Palestrante">
                            <ul class="team-social">
                                <li data-social="linkedin"><a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a></li>
                                <li data-social="instagram"><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
                                <li data-social="site"><a href="#" aria-label="Site"><i class="fas fa-globe"></i></a></li>
                            </ul>
                        </div>
                        <div class="team-caption">
                            <h3><a href="#">Nome da Palestrante</a></h3>
                            <p>Cargo / Instituição</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php
                    $dayLabels = [1 => '3 de Junho', 2 => '4 de Junho'];
                    foreach ($speakers as $sp):
                        $spSchedule = getScheduleForSpeaker($sp['id']);
                    ?>
                    <div class="col-6 col-sm-4 palestrante-col text-center">
                        <div class="single-team mb-30<?= !empty($spSchedule) ? ' has-schedule' : '' ?>" <?php if (!empty($spSchedule)): ?>role="button" tabindex="0" data-toggle-schedule="idx-<?= $sp['id'] ?>"<?php endif; ?>>
                            <div class="team-img">
                                <?php if ($sp['photo']): ?>
                                    <img class="img-fluid d-block mx-auto w-100" src="/<?= htmlspecialchars($sp['photo']) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                                <?php else: ?>
                                    <img class="img-fluid d-block mx-auto w-100" src="assets/img/galeria/speaker-placeholder.svg" alt="<?= htmlspecialchars($sp['name']) ?>">
                                <?php endif; ?>
                                <ul class="team-social">
                                    <?php if ($sp['linkedin']): ?><li data-social="linkedin"><a href="<?= htmlspecialchars($sp['linkedin']) ?>" aria-label="LinkedIn de <?= htmlspecialchars($sp['name']) ?>"><i class="fab fa-linkedin"></i></a></li><?php endif; ?>
                                    <?php if ($sp['instagram']): ?><li data-social="instagram"><a href="<?= htmlspecialchars($sp['instagram']) ?>" aria-label="Instagram de <?= htmlspecialchars($sp['name']) ?>"><i class="fab fa-instagram"></i></a></li><?php endif; ?>
                                    <?php if ($sp['website']): ?><li data-social="site"><a href="<?= htmlspecialchars($sp['website']) ?>" aria-label="Site de <?= htmlspecialchars($sp['name']) ?>"><i class="fas fa-globe"></i></a></li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="team-caption">
                                <h3><a href="palestrantes.php"><?= htmlspecialchars($sp['name']) ?></a></h3>
                                <p><?= htmlspecialchars(($sp['position'] ?: '') . ($sp['institution'] ? ' / ' . $sp['institution'] : '')) ?></p>
                                <?php if (!empty($spSchedule)): ?>
                                <small class="text-muted"><i class="fas fa-calendar-alt"></i> <?= count($spSchedule) ?> atividade<?= count($spSchedule) > 1 ? 's' : '' ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($spSchedule)): ?>
                        <div class="speaker-schedule-panel" id="schedule-idx-<?= $sp['id'] ?>" style="display:none;">
                            <div class="speaker-schedule-card">
                                <h6><i class="fas fa-calendar-alt"></i> Programação de <?= htmlspecialchars($sp['name']) ?></h6>
                                <?php foreach ($spSchedule as $sc): ?>
                                <div class="speaker-schedule-item">
                                    <span class="speaker-schedule-day"><?= $dayLabels[$sc['day']] ?? $sc['day'] ?></span>
                                    <span class="speaker-schedule-time"><?= htmlspecialchars($sc['start_time']) ?> — <?= htmlspecialchars($sc['end_time']) ?></span>
                                    <strong><?= htmlspecialchars($sc['title']) ?></strong>
                                    <?php if ($sc['location']): ?>
                                    <small class="text-muted d-block"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($sc['location']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Programação -->
    <section class="fix section-padding30">
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
                                    <div class="session-card"<?php if (!empty($sessionSpeakers)): ?> role="button" tabindex="0"<?php endif; ?>>
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
            <div class="text-center mt-50">
                <a href="programacao.php" class="btn">Ver Programação Completa</a>
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
                                <img src="assets/img/galeria/news-placeholder.svg" alt="Destaque de notícia">
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
                                        <img src="assets/img/galeria/news-placeholder.svg" alt="<?= htmlspecialchars($n['title']) ?>">
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

    <!-- Álbum de Fotos -->
    <div id="album-fotos">
        <?php
        $totalPhotos = count($albumPhotos);
        $slotCount = min($totalPhotos, 6);
        for ($i = 0; $i < $slotCount; $i++):
            $img = $albumPhotos[$i % $totalPhotos];
            $isReal = $i < $albumRealCount;
        ?>
        <?php if ($isReal): ?>
        <a href="<?= htmlspecialchars($img) ?>" class="album-popup album-cell" aria-label="Foto <?= $i + 1 ?> do álbum">
            <div class="gallery-img gallery-slot" data-slot="<?= $i ?>" style="background-image: url(<?= htmlspecialchars($img) ?>);" role="img" aria-label="Foto do evento"></div>
        </a>
        <?php else: ?>
        <div class="album-cell album-cell--default">
            <div class="gallery-img gallery-slot" data-slot="<?= $i ?>" style="background-image: url(<?= htmlspecialchars($img) ?>);" role="img" aria-label="Foto do evento"></div>
        </div>
        <?php endif; ?>
        <?php endfor; ?>
        <button id="album-fullscreen-btn" type="button" aria-label="Ver todas as fotos"><i class="fas fa-expand"></i></button>
    </div>

    <!-- Viewer fullscreen do álbum (mosaico) -->
    <div id="album-viewer" class="album-viewer" hidden>
        <div class="album-viewer__hud">
            <span class="album-viewer__counter" id="album-viewer-counter"><?= count($albumPhotos) ?> fotos</span>
            <button class="album-viewer__close" id="album-viewer-close" type="button" aria-label="Fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="album-viewer__mosaic" id="album-viewer-mosaic">
            <?php
            $mosaicCount = 10; // 2 tiles 2×2 (=8 cells) + 8 tiles = 16 cells = 4×4
            for ($i = 0; $i < $mosaicCount; $i++):
                $img = $albumPhotos[$i % count($albumPhotos)];
            ?>
            <div class="album-viewer__tile">
                <img src="<?= htmlspecialchars($img) ?>" alt="Foto <?= ($i % count($albumPhotos)) + 1 ?>" loading="lazy" draggable="false">
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Apoio e Realização -->
    <section class="work-company section-padding30" style="background: #64428c;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 col-md-8">
                    <div class="section-tittle section-tittle2 mb-50">
                        <h2>Apoio e Realização</h2>
                        <p>O <?= site('site_title') ?> é uma realização do Ministério do Turismo, com o apoio da ONU Turismo.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="logo-area">
                        <div class="apoio-grid">
                            <?php if (empty($sponsors)): ?>
                            <div class="apoio-cell apoio-onu" style="grid-row:1;grid-column:4;">
                                <div class="single-logo"><a href="https://www.untourism.int/" target="_blank" rel="noopener"><img src="assets/img/logos/ONU-Turismo.png" alt="ONU Turismo"></a></div>
                            </div>
                            <div class="apoio-cell apoio-mtur" style="grid-row:1;grid-column:5;">
                                <div class="single-logo"><a href="https://www.gov.br/turismo/" target="_blank" rel="noopener"><img src="assets/img/logos/vertical-principal.png" alt="Ministério do Turismo"></a></div>
                            </div>
                            <?php else: ?>
                                <?php foreach ($sponsors as $sp):
                                    $row = $sp['grid_row'] ?? 1;
                                    $col = $sp['grid_col'] ?? 1;
                                    $isMtur = stripos($sp['name'], 'minist') !== false || stripos($sp['name'], 'mtur') !== false;
                                ?>
                                <div class="apoio-cell<?= $isMtur ? ' apoio-mtur' : '' ?>" style="grid-row:<?= $row ?>;grid-column:<?= $col ?>;">
                                    <div class="single-logo">
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
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" integrity="sha384-2UI1PfnXFjVMQ7/ZDEF70CR943oH3v6uZrFQGGqJYlvhh4g6z6uVktxYbOlAczav" crossorigin="anonymous"></script>
    <script>
        var dataDoEvento = new Date("<?= htmlspecialchars($eventDate) ?>").getTime();
        var albumFotos = <?= json_encode(array_values($albumPhotos)) ?>;
    </script>
    <script src="./assets/js/home.js"></script>
    <script>
    // Toggle painel de programação dos palestrantes (index)
    document.querySelectorAll("[data-toggle-schedule]").forEach(function(el) {
        el.addEventListener("click", function(e) {
            if (e.target.closest(".team-social a")) return;
            var id = this.getAttribute("data-toggle-schedule");
            var panel = document.getElementById("schedule-" + id);
            if (!panel) return;
            document.querySelectorAll(".speaker-schedule-panel").forEach(function(p) {
                if (p !== panel) p.style.display = "none";
            });
            if (panel.style.display === "none") {
                panel.style.display = "";
                panel.scrollIntoView({ behavior: "smooth", block: "nearest" });
            } else {
                panel.style.display = "none";
            }
        });
    });
    // Esconder redes sociais sem link
    document.querySelectorAll(".team-social li[data-social]").forEach(function(li) {
        var link = li.querySelector("a");
        if (!link || !link.getAttribute("href") || link.getAttribute("href") === "#") {
            li.style.display = "none";
        }
    });
    </script>
</body>
</html>
