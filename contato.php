<?php
require_once __DIR__ . '/includes/site.php';
?>
<!doctype html>
<html class="no-js" lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Contato — <?= site('site_title') ?></title>
    <meta name="description" content="Entre em contato com a organização do <?= site('site_title') ?> — Ministério do Turismo e ONU Turismo.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="Contato — <?= site('site_title') ?>">
    <meta property="og:description" content="Entre em contato com a organização do <?= site('site_title') ?>.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/img/destaque/save-the-date-forum.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="https://forumdeturismo.gov.br/contato.php">
    <link rel="canonical" href="https://forumdeturismo.gov.br/contato.php">
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
                                <h2>Contato</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="contact-section">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="contact-title">Entre em Contato</h2>
                    </div>
                    <div class="col-lg-8">
                        <form class="form-contact contact_form" action="contact_process.php" method="post" id="contactForm" novalidate="novalidate">
                            <input type="hidden" name="_token" id="csrfToken" value="">
                            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="message" class="sr-only">Sua mensagem</label>
                                        <textarea class="form-control w-100" name="message" id="message" cols="30" rows="9" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Sua mensagem'" placeholder=" Sua mensagem" maxlength="5000" aria-required="true"></textarea>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="name" class="sr-only">Seu nome</label>
                                        <input class="form-control valid" name="name" id="name" type="text" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Seu nome'" placeholder="Seu nome" maxlength="100" aria-required="true">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="email" class="sr-only">Seu e-mail</label>
                                        <input class="form-control valid" name="email" id="email" type="email" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Seu e-mail'" placeholder="Seu e-mail" maxlength="254" aria-required="true">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="subject" class="sr-only">Assunto</label>
                                        <input class="form-control" name="subject" id="subject" type="text" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Assunto'" placeholder="Assunto" maxlength="200" aria-required="true">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <button type="submit" class="button button-contactForm boxed-btn">Enviar</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-3 offset-lg-1">
                        <div class="media contact-info">
                            <span class="contact-info__icon"><i class="ti-home"></i></span>
                            <div class="media-body">
                                <h3><?= site('contact_city') ?></h3>
                                <p><?= site('contact_venue') ?></p>
                            </div>
                        </div>
                        <div class="media contact-info">
                            <span class="contact-info__icon"><i class="ti-tablet"></i></span>
                            <div class="media-body">
                                <h3><?= site('contact_phone') ?></h3>
                                <p><?= site('contact_hours') ?></p>
                            </div>
                        </div>
                        <div class="media contact-info">
                            <span class="contact-info__icon"><i class="ti-email"></i></span>
                            <div class="media-body">
                                <h3><?= site('contact_email') ?></h3>
                                <p><?= site('contact_email_desc') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="mapa" class="mt-5 pt-4">
                    <iframe
                        src="https://www.google.com/maps?q=<?= site('maps_query') ?>&output=embed"
                        class="w-100"
                        height="480"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa do <?= site('contact_venue') ?>">
                    </iframe>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
