<footer>
    <div class="footer-area footer-padding">
        <div class="container">
            <div class="row d-flex justify-content-between">
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="single-footer-caption mb-50">
                        <div class="single-footer-caption mb-30">
                            <div class="footer-tittle">
                                <h4>Sobre o Evento</h4>
                                <div class="footer-pera">
                                    <p><?= site('footer_about') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="single-footer-caption mb-50">
                        <div class="footer-tittle">
                            <h4>Informações</h4>
                            <ul>
                                <li><a href="contato.php#mapa" style="color: inherit;"><p><i class="fas fa-map-marker-alt footer-info-icon"></i><?= site('footer_location') ?></p></a></li>
                                <li><p><i class="fas fa-calendar-alt footer-info-icon"></i><?= site('footer_date') ?></p></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="single-footer-caption mb-50">
                        <div class="footer-tittle">
                            <h4>Links</h4>
                            <ul>
                                <li><a href="sobre.php">Sobre o Evento</a></li>
                                <li><a href="palestrantes.php">Palestrantes</a></li>
                                <li><a href="programacao.php">Programação</a></li>
                                <li><a href="contato.php">Contato</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="single-footer-caption mb-50">
                        <div class="footer-tittle">
                            <h4>Siga-nos</h4>
                            <div class="footer-pera footer-pera2">
                                <p>Acompanhe as novidades do evento nas redes sociais.</p>
                            </div>
                            <div class="footer-social mt-15">
                                <?php if (siteRaw('social_instagram')): ?><a href="<?= site('social_instagram') ?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
                                <?php if (siteRaw('social_facebook')): ?><a href="<?= site('social_facebook') ?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                                <?php if (siteRaw('social_twitter')): ?><a href="<?= site('social_twitter') ?>" target="_blank"><i class="icon-x"></i></a><?php endif; ?>
                                <?php if (siteRaw('social_youtube')): ?><a href="<?= site('social_youtube') ?>" target="_blank"><i class="fab fa-youtube"></i></a><?php endif; ?>
                                <?php if (siteRaw('social_linkedin')): ?><a href="<?= site('social_linkedin') ?>" target="_blank"><i class="fab fa-linkedin"></i></a><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom-area footer-bg">
        <div class="container">
            <div class="footer-border">
                <div class="row d-flex justify-content-between align-items-center">
                    <div class="col-xl-10 col-lg-8">
                        <div class="footer-copy-right">
                            <p>Copyright &copy;<?= date('Y') ?> Ministério do Turismo | ONU Turismo. Todos os direitos reservados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<div id="back-top">
    <a title="Voltar ao Topo" href="#"><i class="fas fa-level-up-alt"></i></a>
</div>

<script src="./assets/js/vendor/modernizr-3.5.0.min.js"></script>
<script src="./assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="./assets/js/popper.min.js"></script>
<script src="./assets/js/bootstrap.min.js"></script>
<script src="./assets/js/jquery.slicknav.min.js"></script>
<script src="./assets/js/slick.min.js"></script>
<script src="./assets/js/wow.min.js"></script>
<script src="./assets/js/jquery.magnific-popup.js"></script>
<script src="./assets/js/jquery.nice-select.min.js"></script>
<script src="./assets/js/jquery.sticky.js"></script>
<script src="./assets/js/jquery.counterup.min.js"></script>
<script src="./assets/js/waypoints.min.js"></script>
<script src="./assets/js/jquery.countdown.min.js"></script>
<script src="./assets/js/contact.js"></script>
<script src="./assets/js/jquery.form.js"></script>
<script src="./assets/js/jquery.validate.min.js"></script>
<script src="./assets/js/mail-script.js"></script>
<script src="./assets/js/jquery.ajaxchimp.min.js"></script>
<script src="./assets/js/plugins.js"></script>
<script src="./assets/js/main.js"></script>
