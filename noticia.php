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
                     <div class="hero-cap hero-cap2 text-center">
                        <h2>Notícia</h2>
                        <nav aria-label="breadcrumb">
                           <ol class="breadcrumb justify-content-center" style="background:transparent;">
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
               <div class="col-lg-8 posts-list">
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

                        <?php if (!empty($news['video_url'])):
                            $videoEmbed = '';
                            if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $news['video_url'], $m)) {
                                $videoEmbed = 'https://www.youtube.com/embed/' . $m[1];
                            } elseif (preg_match('#vimeo\.com/(\d+)#', $news['video_url'], $m)) {
                                $videoEmbed = 'https://player.vimeo.com/video/' . $m[1];
                            }
                            if ($videoEmbed):
                        ?>
                        <div class="video-container mt-4 mb-4">
                           <div class="embed-responsive embed-responsive-16by9">
                              <iframe class="embed-responsive-item" src="<?= htmlspecialchars($videoEmbed) ?>" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                           </div>
                        </div>
                        <?php endif; endif; ?>

                        <?php if (!empty($gallery)): ?>
                        <div class="blog-gallery mt-4 mb-4">
                           <div class="row">
                              <?php foreach ($gallery as $gi): ?>
                              <div class="col-md-6 mb-3">
                                 <a href="/<?= htmlspecialchars($gi['image']) ?>" class="popup-gallery" title="Galeria">
                                    <img class="img-fluid rounded" src="/<?= htmlspecialchars($gi['image']) ?>" alt="Galeria">
                                 </a>
                              </div>
                              <?php endforeach; ?>
                           </div>
                        </div>
                        <?php endif; ?>
                     </div>
                  </div>
                  <div class="navigation-top">
                     <div class="d-sm-flex justify-content-between text-center">
                        <ul class="social-icons">
                           <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no Facebook"><i class="fab fa-facebook-f"></i></a></li>
                           <li><a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>&text=<?= urlencode($news['title']) ?>" target="_blank" rel="noopener" title="Compartilhar no X"><i class="icon-x"></i></a></li>
                           <li><a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no LinkedIn"><i class="fab fa-linkedin"></i></a></li>
                           <li><a href="https://api.whatsapp.com/send?text=<?= urlencode($news['title'] . ' - https://forumdeturismo.gov.br/noticia.php?slug=' . $slug) ?>" target="_blank" rel="noopener" title="Compartilhar no WhatsApp"><i class="fab fa-whatsapp"></i></a></li>
                        </ul>
                     </div>
                  </div>
                  <?php endif; ?>
               </div>
               <div class="col-lg-4">
                  <div class="blog_right_sidebar">
                     <aside class="single_sidebar_widget instagram_feeds">
                        <h4 class="widget_title" style="color: #64428c;">Instagram</h4>
                        <p class="text-center mb-3">
                           <?php if (siteRaw('social_instagram')): ?>
                           <a href="<?= site('social_instagram') ?>" target="_blank" style="color: #64428c; font-weight: 600;">
                              <i class="fab fa-instagram"></i> Siga @mturismo
                           </a>
                           <?php endif; ?>
                        </p>
                     </aside>
                  </div>
               </div>
            </div>
         </div>
      </section>
   </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
   <script>
      $('.popup-gallery').magnificPopup({
         type: 'image',
         gallery: { enabled: true },
         zoom: { enabled: true, duration: 300 }
      });
   </script>
</body>
</html>
