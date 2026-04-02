<?php
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/instagram.php';

$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allNews = [];
$total = 0;
if (dbReady()) {
    $total = (int) Database::fetchOne("SELECT COUNT(*) as n FROM news WHERE is_visible = 1")['n'];
    $allNews = Database::fetchAll(
        "SELECT * FROM news WHERE is_visible = 1 ORDER BY published_at DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );
}
$totalPages = max(1, (int) ceil($total / $perPage));

$mesesPt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$instagramPosts = getInstagramFeed(6);
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Notícias — <?= site('site_title') ?></title>
    <meta name="description" content="Notícias e artigos sobre o <?= site('site_title') ?> — turismo, igualdade de gênero e sustentabilidade.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="Notícias — <?= site('site_title') ?>">
    <meta property="og:description" content="Acompanhe as últimas notícias sobre o <?= site('site_title') ?>.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/noticias.php">
    <link rel="canonical" href="https://forumdeturismo.gov.br/noticias.php">
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
                            <div class="hero-cap hero-cap2 text-center">
                                <h2>Notícias</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="blog_area section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mb-5 mb-lg-0">
                        <div class="blog_left_sidebar">
                            <?php if (empty($allNews)): ?>
                            <article class="blog_item blog-item blog-default">
                                <div class="blog_item_img">
                                    <img class="card-img rounded-0 img-fluid" src="assets/img/galeria/news-placeholder.svg" alt="Imagem de destaque da notícia">
                                    <a href="#" class="blog_item_date"><h3>00</h3><p>Mês</p></a>
                                </div>
                                <div class="blog_details">
                                    <a class="d-inline-block" href="#"><h2 class="blog-head" style="color: #64428c;">Título da Notícia</h2></a>
                                    <p>Resumo da notícia será exibido aqui quando o conteúdo for cadastrado no CMS.</p>
                                </div>
                            </article>
                            <?php else: ?>
                                <?php foreach ($allNews as $n):
                                    $pubDate = strtotime($n['published_at']);
                                    $dia = date('d', $pubDate);
                                    $mes = $mesesPt[(int)date('n', $pubDate) - 1];
                                ?>
                                <article class="blog_item">
                                    <div class="blog_item_img">
                                        <?php if ($n['featured_image']): ?>
                                            <img class="card-img rounded-0 img-fluid" src="/<?= htmlspecialchars($n['featured_image']) ?>" alt="<?= htmlspecialchars($n['title']) ?>">
                                        <?php else: ?>
                                            <img class="card-img rounded-0 img-fluid" src="assets/img/galeria/news-placeholder.svg" alt="<?= htmlspecialchars($n['title']) ?>">
                                        <?php endif; ?>
                                        <a href="noticia.php?slug=<?= htmlspecialchars($n['slug']) ?>" class="blog_item_date">
                                            <h3><?= $dia ?></h3>
                                            <p><?= $mes ?></p>
                                        </a>
                                    </div>
                                    <div class="blog_details">
                                        <a class="d-inline-block" href="noticia.php?slug=<?= htmlspecialchars($n['slug']) ?>">
                                            <h2 class="blog-head" style="color: #64428c;"><?= htmlspecialchars($n['title']) ?></h2>
                                        </a>
                                        <p><?= htmlspecialchars($n['summary']) ?></p>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($totalPages > 1): ?>
                            <nav class="blog-pagination justify-content-center d-flex">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a href="noticias.php?p=<?= $page - 1 ?>" class="page-link" aria-label="Anterior"><i class="ti-angle-left"></i></a>
                                    </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                                        <a href="noticias.php?p=<?= $i ?>" class="page-link"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a href="noticias.php?p=<?= $page + 1 ?>" class="page-link" aria-label="Próxima"><i class="ti-angle-right"></i></a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="blog_right_sidebar">
                            <aside class="single_sidebar_widget instagram_feeds">
                                <h4 class="widget_title" style="color: #64428c;">Instagram</h4>
                                <?php if (!empty($instagramPosts)): ?>
                                <ul class="instagram_imgs">
                                    <?php foreach ($instagramPosts as $post):
                                        $imgUrl = ($post['media_type'] === 'VIDEO' && !empty($post['thumbnail_url']))
                                            ? $post['thumbnail_url']
                                            : $post['media_url'];
                                    ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($post['permalink']) ?>" target="_blank" rel="noopener">
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Post do Instagram" loading="lazy">
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                <?php if (siteRaw('social_instagram')): ?>
                                <p class="text-center mt-3">
                                    <a href="<?= site('social_instagram') ?>" target="_blank" rel="noopener" style="color: #64428c; font-weight: 600;">
                                        <i class="fab fa-instagram"></i> Siga no Instagram
                                    </a>
                                </p>
                                <?php endif; ?>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
