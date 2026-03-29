<?php
/**
 * Configurações — Sobre o Evento, Contato, Rodapé, Redes Sociais, Formulário
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Configurações';
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $section = $_POST['section'] ?? '';

    $settingsMap = [
        'about' => ['about_title', 'about_body'],
        'contact' => ['contact_city', 'contact_venue', 'contact_phone', 'contact_hours', 'contact_email', 'contact_email_desc', 'maps_query'],
        'footer' => ['footer_about', 'footer_location', 'footer_date'],
        'social' => ['social_instagram', 'social_facebook', 'social_twitter', 'social_youtube', 'social_linkedin'],
        'form' => ['form_recipient', 'form_sender'],
        'event' => ['site_title', 'event_date'],
    ];

    if (isset($settingsMap[$section])) {
        foreach ($settingsMap[$section] as $key) {
            if (isset($_POST[$key])) {
                Database::setSetting($key, trim($_POST[$key]));
            }
        }

        // Upload de imagens do Sobre
        if ($section === 'about') {
            foreach (['about_image1', 'about_image2'] as $field) {
                if (!empty($_FILES[$field]['name'])) {
                    $upload = Upload::image($_FILES[$field], 'about');
                    if ($upload['success']) {
                        $old = Database::getSetting($field);
                        if ($old) Upload::delete($old);
                        Database::setSetting($field, $upload['path']);
                    }
                }
            }
        }

        Auth::log(Auth::user()['id'], 'settings_updated', "Seção: {$section}");
        $_SESSION['flash_message'] = 'Configurações salvas com sucesso.';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: /admin/settings.php');
    exit;
}

// Carregar todas as configurações
$settings = [];
$rows = Database::fetchAll("SELECT key, value FROM settings");
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}
$s = fn(string $key) => htmlspecialchars($settings[$key] ?? '');

require __DIR__ . '/templates/header.php';

if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<h2 class="mb-4">Configurações do Site</h2>

<!-- Sobre o Evento -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle"></i> Sobre o Evento</h6></div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="about">
            <div class="form-group mb-3">
                <label for="about_title">Título</label>
                <input type="text" class="form-control" name="about_title" value="<?= $s('about_title') ?>">
            </div>
            <div class="form-group mb-3">
                <label for="about_body">Texto descritivo</label>
                <textarea class="form-control" name="about_body" rows="6"><?= $s('about_body') ?></textarea>
                <small class="form-text text-muted">HTML permitido (parágrafos, listas, negrito, links).</small>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Imagem principal (vertical ~470x630)</label>
                        <input type="file" class="form-control-file" name="about_image1" accept="image/jpeg,image/png,image/webp" data-preview="prevAbout1">
                        <?php if ($settings['about_image1'] ?? ''): ?>
                            <img src="/<?= $s('about_image1') ?>" id="prevAbout1" class="upload-preview mt-2">
                        <?php else: ?>
                            <img id="prevAbout1" class="upload-preview mt-2" style="display:none">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Imagem secundária (horizontal ~335x320)</label>
                        <input type="file" class="form-control-file" name="about_image2" accept="image/jpeg,image/png,image/webp" data-preview="prevAbout2">
                        <?php if ($settings['about_image2'] ?? ''): ?>
                            <img src="/<?= $s('about_image2') ?>" id="prevAbout2" class="upload-preview mt-2">
                        <?php else: ?>
                            <img id="prevAbout2" class="upload-preview mt-2" style="display:none">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar Sobre</button>
        </form>
    </div>
</div>

<!-- Evento e Título -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-calendar"></i> Evento</h6></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="event">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Título do site</label>
                        <input type="text" class="form-control" name="site_title" value="<?= $s('site_title') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Data do evento (para countdown)</label>
                        <input type="text" class="form-control" name="event_date" value="<?= $s('event_date') ?>"
                               placeholder="June 3, 2026 09:00:00">
                        <small class="form-text text-muted">Formato: "June 3, 2026 09:00:00"</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar</button>
        </form>
    </div>
</div>

<!-- Contato -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-phone"></i> Informações de Contato</h6></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="contact">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Cidade / Estado</label>
                        <input type="text" class="form-control" name="contact_city" value="<?= $s('contact_city') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Local / Venue</label>
                        <input type="text" class="form-control" name="contact_venue" value="<?= $s('contact_venue') ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-3">
                        <label>Telefone</label>
                        <input type="text" class="form-control" name="contact_phone" value="<?= $s('contact_phone') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-3">
                        <label>Horário de atendimento</label>
                        <input type="text" class="form-control" name="contact_hours" value="<?= $s('contact_hours') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-3">
                        <label>E-mail exibido</label>
                        <input type="email" class="form-control" name="contact_email" value="<?= $s('contact_email') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-3">
                        <label>Descrição do e-mail</label>
                        <input type="text" class="form-control" name="contact_email_desc" value="<?= $s('contact_email_desc') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group mb-3">
                <label>Busca do Google Maps</label>
                <input type="text" class="form-control" name="maps_query" value="<?= $s('maps_query') ?>"
                       placeholder="Centro+de+Convenções+de+João+Pessoa,+PB,+Brasil">
                <small class="form-text text-muted">Texto de busca no embed do mapa (use + no lugar de espaços).</small>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar Contato</button>
        </form>
    </div>
</div>

<!-- Redes Sociais -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-share-alt"></i> Redes Sociais</h6></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="social">
            <div class="row">
                <?php foreach (['instagram', 'facebook', 'twitter', 'youtube', 'linkedin'] as $net): ?>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label><?= ucfirst($net === 'twitter' ? 'X (Twitter)' : $net) ?></label>
                        <input type="url" class="form-control" name="social_<?= $net ?>" value="<?= $s("social_{$net}") ?>" placeholder="https://...">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar Redes Sociais</button>
        </form>
    </div>
</div>

<!-- Rodapé -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-columns"></i> Rodapé</h6></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="footer">
            <div class="form-group mb-3">
                <label>Texto sobre o evento (rodapé)</label>
                <textarea class="form-control" name="footer_about" rows="2"><?= $s('footer_about') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Local (rodapé)</label>
                        <input type="text" class="form-control" name="footer_location" value="<?= $s('footer_location') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Data (rodapé)</label>
                        <input type="text" class="form-control" name="footer_date" value="<?= $s('footer_date') ?>">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar Rodapé</button>
        </form>
    </div>
</div>

<!-- Formulário de Contato -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-envelope"></i> E-mails do Formulário de Contato</h6></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="section" value="form">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>E-mail destinatário</label>
                        <input type="email" class="form-control" name="form_recipient" value="<?= $s('form_recipient') ?>">
                        <small class="form-text text-muted">Quem recebe as mensagens do formulário.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>E-mail remetente (noreply)</label>
                        <input type="email" class="form-control" name="form_sender" value="<?= $s('form_sender') ?>">
                        <small class="form-text text-muted">Aparece como "De:" no e-mail. Deve ser do mesmo domínio do servidor.</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
