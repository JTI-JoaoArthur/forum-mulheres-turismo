<?php
require_once __DIR__ . '/includes/site.php';

$slug = trim($_GET['slug'] ?? '');
$news = null;
$gallery = [];

if ($slug && dbReady()) {
    $news = Database::fetchOne("SELECT * FROM news WHERE slug = ? AND is_visible = 1", [$slug]);
    if ($news) {
        $gallery = Database::fetchAll("SELECT * FROM news_gallery WHERE news_id = ? ORDER BY display_order ASC", [$news['id']]);
    }
}

if (!$news) {
    http_response_code(404);
}

$title = $news ? htmlspecialchars($news['title']) : 'Notícia';
$summary = $news ? htmlspecialchars($news['summary']) : '';
$mesesPt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$pubDateFmt = '';
if ($news) {
    $ts = strtotime($news['published_at']);
    $pubDateFmt = date('d', $ts) . ' de ' . $mesesPt[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts) . ' às ' . date('H:i:s', $ts);
}
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= $title ?> — <?= site('site_title') ?></title>
    <meta name="description" content="<?= $summary ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="<?= $title ?> — <?= site('site_title') ?>">
    <meta property="og:description" content="<?= $summary ?>">
    <meta property="og:type" content="article">
    <?php if ($news && $news['featured_image']): ?>
    <meta property="og:image" content="/<?= htmlspecialchars($news['featured_image']) ?>">
    <?php else: ?>
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <?php endif; ?>
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/noticia.php?slug=<?= htmlspecialchars($slug) ?>">
    <link rel="canonical" href="https://forumdeturismo.gov.br/noticia.php?slug=<?= htmlspecialchars($slug) ?>">
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
                     <div class="hero-cap hero-cap2 text-center">
                        <h2>Notícia</h2>
                        <nav aria-label="breadcrumb">
                           <ol class="breadcrumb justify-content-center flex-wrap" style="background:transparent;">
                              <li class="breadcrumb-item"><a href="noticias.php" style="color:#fff;">Notícias</a></li>
                              <li class="breadcrumb-item active" style="color:#ddd;">Detalhes</li>
                           </ol>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <section class="blog_area single-post-area section-padding">
         <div class="container">
            <div class="row">
               <div class="col-lg-10 col-xl-8 mx-auto posts-list">
                  <?php if (!$news): ?>
                  <div class="single-post">
                     <div class="blog_details">
                        <h2 style="color: #64428c;">Notícia não encontrada</h2>
                        <p>A notícia que você procura não existe ou foi removida.</p>
                        <a href="noticias.php" class="btn mt-30">Ver todas as notícias</a>
                     </div>
                  </div>
                  <?php else: ?>
                  <div class="single-post">
                     <?php if ($news['featured_image']): ?>
                     <div class="feature-img">
                        <img class="img-fluid" src="/<?= htmlspecialchars($news['featured_image']) ?>" alt="<?= $title ?>">
                     </div>
                     <?php endif; ?>
                     <div class="blog_details">
                        <h2 style="color: #64428c;"><?= $title ?></h2>
                        <p class="text-muted mb-3">
                           <small><i class="fas fa-calendar-alt"></i> <?= $pubDateFmt ?></small>
                           <?php if (!empty($news['author'])): ?>
                           <small class="ml-3"><i class="fas fa-user"></i> <?= htmlspecialchars($news['author']) ?></small>
                           <?php endif; ?>
                        </p>
                        <p class="excert"><em><?= htmlspecialchars($news['summary']) ?></em></p>

                        <?= sanitizeHtml($news['body'] ?? '') ?>

                        <?php
                        // Montar carrossel de mídia da notícia (vídeos + galeria)
                        $mediaItems = [];

                        // Vídeo enviado (arquivo)
                        if (!empty($news['video_path'])) {
                            $mediaItems[] = ['type' => 'video_file', 'src' => '/' . htmlspecialchars($news['video_path'])];
                        }

                        // Vídeo externo
                        if (!empty($news['video_url'])) {
                            $videoEmbed = '';
                            $videoThumb = '';
                            if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $news['video_url'], $m)) {
                                $videoEmbed = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0';
                                $videoThumb = 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg';
                            } elseif (preg_match('#vimeo\.com/(\d+)#', $news['video_url'], $m)) {
                                $videoEmbed = 'https://player.vimeo.com/video/' . $m[1];
                            }
                            if ($videoEmbed) {
                                $mediaItems[] = ['type' => 'video_embed', 'src' => $videoEmbed, 'thumb' => $videoThumb];
                            }
                        }

                        // Galeria de imagens
                        foreach ($gallery as $gi) {
                            $mediaItems[] = ['type' => 'image', 'src' => '/' . htmlspecialchars($gi['image'])];
                        }

                        if (!empty($mediaItems)):
                        ?>
                        <div class="news-media-carousel mt-4 mb-4">
                           <?php if (count($mediaItems) > 1): ?>
                           <div class="swiper newsMediaSwiper">
                              <div class="swiper-wrapper">
                                 <?php foreach ($mediaItems as $mi): ?>
                                 <div class="swiper-slide">
                                    <?php if ($mi['type'] === 'video_file'): ?>
                                    <video controls preload="metadata" class="news-video-player">
                                       <source src="<?= $mi['src'] ?>" type="video/<?= pathinfo($mi['src'], PATHINFO_EXTENSION) ?>">
                                    </video>
                                    <?php elseif ($mi['type'] === 'video_embed'): ?>
                                    <div style="position:relative;padding-bottom:56.25%;height:0;">
                                       <iframe src="<?= htmlspecialchars($mi['src']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="encrypted-media; picture-in-picture" allowfullscreen title="Vídeo — <?= $title ?>"></iframe>
                                    </div>
                                    <?php else: ?>
                                    <a href="<?= $mi['src'] ?>" class="popup-gallery"><img class="img-fluid rounded" src="<?= $mi['src'] ?>" alt="Foto da galeria — <?= $title ?>" style="width:100%"></a>
                                    <?php endif; ?>
                                 </div>
                                 <?php endforeach; ?>
                              </div>
                              <div class="swiper-button-next" style="color:#64428c" aria-label="Próxima mídia"></div>
                              <div class="swiper-button-prev" style="color:#64428c" aria-label="Mídia anterior"></div>
                              <div class="swiper-pagination"></div>
                           </div>
                           <?php else:
                              $mi = $mediaItems[0];
                              if ($mi['type'] === 'video_file'): ?>
                           <video controls preload="metadata" class="news-video-player" style="border-radius:4px;">
                              <source src="<?= $mi['src'] ?>" type="video/<?= pathinfo($mi['src'], PATHINFO_EXTENSION) ?>">
                           </video>
                           <?php elseif ($mi['type'] === 'video_embed'): ?>
                           <div style="position:relative;padding-bottom:56.25%;height:0;">
                              <iframe src="<?= htmlspecialchars($mi['src']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
                           </div>
                           <?php else: ?>
                           <a href="<?= $mi['src'] ?>" class="popup-gallery"><img class="img-fluid rounded" src="<?= $mi['src'] ?>" alt="Foto da galeria — <?= $title ?>"></a>
                           <?php endif;
                           endif; ?>
                        </div>
                        <?php endif; ?>
                     </div>
                  </div>
                  <div class="navigation-top">
                     <div class="d-sm-flex justify-content-between text-center">
                        <ul class="social-icons">
                           <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no Facebook" aria-label="Compartilhar no Facebook"><i class="fab fa-facebook-f"></i></a></li>
                           <li><a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>&text=<?= urlencode($news['title']) ?>" target="_blank" rel="noopener" title="Compartilhar no X" aria-label="Compartilhar no X"><i class="icon-x"></i></a></li>
                           <li><a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no LinkedIn" aria-label="Compartilhar no LinkedIn"><i class="fab fa-linkedin"></i></a></li>
                           <li><a href="https://api.whatsapp.com/send?text=<?= urlencode($news['title'] . ' - https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no WhatsApp" aria-label="Compartilhar no WhatsApp"><i class="fab fa-whatsapp"></i></a></li>
                        </ul>
                     </div>
                  </div>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </section>
   </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
   <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" integrity="sha384-2UI1PfnXFjVMQ7/ZDEF70CR943oH3v6uZrFQGGqJYlvhh4g6z6uVktxYbOlAczav" crossorigin="anonymous"></script>
   <script>
      $('.popup-gallery').magnificPopup({
         type: 'image',
         gallery: { enabled: true },
         zoom: { enabled: true, duration: 300 }
      });
      if (document.querySelector('.newsMediaSwiper')) {
         new Swiper('.newsMediaSwiper', {
            slidesPerView: 1,
            spaceBetween: 10,
            loop: false,
            navigation: { nextEl: '.newsMediaSwiper .swiper-button-next', prevEl: '.newsMediaSwiper .swiper-button-prev' },
            pagination: { el: '.newsMediaSwiper .swiper-pagination', clickable: true }
         });
      }
   </script>
</body>
</html>
