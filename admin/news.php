<?php
/**
 * CRUD — Notícias
 */

@ini_set('upload_max_filesize', '120M');
@ini_set('post_max_size', '125M');
@ini_set('max_execution_time', '300');

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Notícias';
$action    = $_GET['action'] ?? 'list';
$id        = (int) ($_GET['id'] ?? 0);
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

/**
 * Gera slug a partir do título (pt-BR safe).
 */
function generateSlug(string $title): string {
    $slug = mb_strtolower($title, 'UTF-8');
    $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
    $slug = preg_replace('/[èéêë]/u', 'e', $slug);
    $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
    $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
    $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
    $slug = preg_replace('/[ç]/u', 'c', $slug);
    $slug = preg_replace('/[ñ]/u', 'n', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', trim($slug));
    return $slug;
}

// ── Ações POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $featuredType = $_POST['featured_type'] ?? 'image';

        $data = [
            'title'        => $title,
            'slug'         => generateSlug($title),
            'summary'      => trim($_POST['summary'] ?? ''),
            'body'         => trim($_POST['body'] ?? ''),
            'author'       => trim($_POST['author'] ?? ''),
            'published_at' => Auth::isAdmin() && !empty($_POST['published_at'])
                ? trim($_POST['published_at'])
                : ($id > 0 ? (Database::fetchOne("SELECT published_at FROM news WHERE id = ?", [$id])['published_at'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s')),
            'is_featured'   => isset($_POST['is_featured']) ? 1 : 0,
            'is_in_gallery' => isset($_POST['is_in_gallery']) ? 1 : 0,
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        // Limpar campos conflitantes conforme tipo de mídia selecionado
        if ($featuredType === 'image') {
            $data['video_url'] = '';
            if ($id > 0) {
                $old = Database::fetchOne("SELECT video_path FROM news WHERE id = ?", [$id]);
                if ($old && $old['video_path']) Upload::delete($old['video_path']);
            }
            $data['video_path'] = '';
        } elseif ($featuredType === 'video_url') {
            $data['video_url'] = trim($_POST['video_url'] ?? '');
            if ($id > 0) {
                $old = Database::fetchOne("SELECT video_path FROM news WHERE id = ?", [$id]);
                if ($old && $old['video_path']) Upload::delete($old['video_path']);
            }
            $data['video_path'] = '';
        } elseif ($featuredType === 'video_file') {
            $data['video_url'] = '';
        }

        $errors = [];
        if (mb_strlen($data['title']) < 3) $errors[] = 'Título é obrigatório (mín. 3 caracteres).';
        if (mb_strlen($data['summary']) < 10) $errors[] = 'Resumo é obrigatório (mín. 10 caracteres).';
        if ($featuredType === 'video_url' && !empty($data['video_url']) && !preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/#i', $data['video_url'])) {
            $errors[] = 'URL de vídeo deve ser do YouTube ou Vimeo.';
        }

        // Verificar slug duplicado
        $existing = Database::fetchOne(
            "SELECT id FROM news WHERE slug = ? AND id != ?",
            [$data['slug'], $id]
        );
        if ($existing) {
            $data['slug'] .= '-' . time();
        }

        // Upload de imagem de destaque (só no modo imagem)
        if ($featuredType === 'image' && !empty($_FILES['featured_image']['name'])) {
            $upload = Upload::image($_FILES['featured_image'], 'news');
            if ($upload['success']) {
                $data['featured_image'] = $upload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT featured_image FROM news WHERE id = ?", [$id]);
                    if ($old && $old['featured_image']) Upload::delete($old['featured_image']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }

        // Limpar imagem de destaque quando tipo é vídeo
        if ($featuredType !== 'image' && $id > 0) {
            $old = Database::fetchOne("SELECT featured_image FROM news WHERE id = ?", [$id]);
            if ($old && $old['featured_image']) Upload::delete($old['featured_image']);
            $data['featured_image'] = '';
        }

        // Upload de vídeo (só no modo video_file)
        if ($featuredType === 'video_file' && !empty($_FILES['video_file']['name'])) {
            $vupload = Upload::video($_FILES['video_file'], 'news');
            if ($vupload['success']) {
                $data['video_path'] = $vupload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT video_path FROM news WHERE id = ?", [$id]);
                    if ($old && $old['video_path']) Upload::delete($old['video_path']);
                }
            } else {
                $errors[] = $vupload['error'];
            }
        }

        if (empty($errors)) {
            // Destaque: fixar no slot escolhido
            if ($data['is_featured']) {
                $slot = (int) ($_POST['carousel_slot'] ?? 0);
                if ($slot >= 1 && $slot <= 5) {
                    // Liberar o slot
                    Database::query("UPDATE carousel SET is_pinned = 0, display_order = 0 WHERE display_order = ? AND is_pinned = 1", [$slot]);
                    Database::query("UPDATE news SET is_pinned = 0, carousel_order = 0 WHERE carousel_order = ? AND is_pinned = 1 AND id != ?", [$slot, $id]);
                    $data['is_pinned'] = 1;
                    $data['carousel_order'] = $slot;
                }
            } else {
                // Desmarcou destaque: desfixar
                $data['is_pinned'] = 0;
                $data['carousel_order'] = 0;
            }

            if ($id > 0) {
                Database::update('news', $data, 'id = ?', [$id]);
                Auth::log(Auth::user()['id'], 'news_updated', "Notícia #{$id}: {$data['title']}");
                $_SESSION['flash_message'] = 'Notícia atualizada com sucesso.';
            } else {
                $newId = Database::insert('news', $data);
                $id = $newId;
                Auth::log(Auth::user()['id'], 'news_created', "Notícia #{$newId}: {$data['title']}");
                $_SESSION['flash_message'] = 'Notícia criada com sucesso.';
            }
            $_SESSION['flash_type'] = 'success';

            // Upload de imagens da galeria
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $maxOrder = Database::fetchOne(
                    "SELECT MAX(display_order) as m FROM news_gallery WHERE news_id = ?",
                    [$id]
                );
                $order = ($maxOrder['m'] ?? 0) + 1;
                $featuredPick = isset($_POST['gallery_featured_index']) ? (int)$_POST['gallery_featured_index'] : -1;
                $uploadedIdx = 0;

                foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                    if (empty($name)) continue;
                    $file = [
                        'name'     => $_FILES['gallery_images']['name'][$i],
                        'type'     => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'error'    => $_FILES['gallery_images']['error'][$i],
                        'size'     => $_FILES['gallery_images']['size'][$i],
                    ];
                    $upload = Upload::image($file, 'news');
                    if ($upload['success']) {
                        // Se este arquivo foi marcado como destaque
                        if ($uploadedIdx === $featuredPick && empty($data['featured_image'])) {
                            $current = Database::fetchOne("SELECT featured_image FROM news WHERE id = ?", [$id]);
                            if (empty($current['featured_image'])) {
                                Database::query("UPDATE news SET featured_image = ? WHERE id = ?", [$upload['path'], $id]);
                            } else {
                                Database::insert('news_gallery', [
                                    'news_id'       => $id,
                                    'image'         => $upload['path'],
                                    'display_order' => $order++,
                                ]);
                            }
                        } else {
                            Database::insert('news_gallery', [
                                'news_id'       => $id,
                                'image'         => $upload['path'],
                                'display_order' => $order++,
                            ]);
                        }
                    }
                    $uploadedIdx++;
                }
            }

            // Upload de vídeo via galeria (se não há vídeo ainda)
            $currentNews = Database::fetchOne("SELECT video_url, video_path FROM news WHERE id = ?", [$id]);
            $hasVideo = !empty($currentNews['video_url']) || !empty($currentNews['video_path']);

            if (!$hasVideo && !empty($_FILES['gallery_video']['name'])) {
                $vupload = Upload::video($_FILES['gallery_video'], 'news');
                if ($vupload['success']) {
                    Database::query("UPDATE news SET video_path = ? WHERE id = ?", [$vupload['path'], $id]);
                }
            }

            if (!$hasVideo && !empty($_POST['gallery_video_url'])) {
                $vurl = trim($_POST['gallery_video_url']);
                if (preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/#i', $vurl)) {
                    Database::query("UPDATE news SET video_url = ? WHERE id = ?", [$vurl, $id]);
                }
            }

            header("Location: /admin/news.php?action=form&id={$id}");
            exit;
        }

        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
        header("Location: /admin/news.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $news = Database::fetchOne("SELECT * FROM news WHERE id = ?", [$id]);
        if ($news) {
            if ($news['featured_image']) Upload::delete($news['featured_image']);
            // Remover imagens da galeria
            $gallery = Database::fetchAll("SELECT image FROM news_gallery WHERE news_id = ?", [$id]);
            foreach ($gallery as $g) {
                Upload::delete($g['image']);
            }
            Database::delete('news_gallery', 'news_id = ?', [$id]);
            Database::delete('news', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'news_deleted', "Notícia #{$id}: {$news['title']}");
            $_SESSION['flash_message'] = 'Notícia removida.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: /admin/news.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $news = Database::fetchOne("SELECT is_visible, title FROM news WHERE id = ?", [$id]);
        if ($news) {
            $newVal = $news['is_visible'] ? 0 : 1;
            Database::query("UPDATE news SET is_visible = ? WHERE id = ?", [$newVal, $id]);
            Auth::log(Auth::user()['id'], 'news_toggled', "Notícia #{$id}: {$news['title']}");
        }
        header('Location: /admin/news.php');
        exit;
    }

    if ($postAction === 'save_instagram_token') {
        $newToken = trim($_POST['instagram_access_token'] ?? '');
        Database::setSetting('instagram_access_token', $newToken);
        if ($newToken !== '') {
            Database::setSetting('instagram_token_saved_at', date('Y-m-d H:i:s'));
            Database::setSetting('instagram_cache', null);
            Database::setSetting('instagram_cache_updated_at', null);
        } else {
            Database::setSetting('instagram_token_saved_at', null);
        }
        Auth::log(Auth::user()['id'], 'settings_updated', 'Instagram token');
        $_SESSION['flash_message'] = 'Token do Instagram salvo.';
        $_SESSION['flash_type'] = 'success';
        header('Location: /admin/news.php');
        exit;
    }

    if ($postAction === 'delete_gallery_image') {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        $img = Database::fetchOne("SELECT * FROM news_gallery WHERE id = ?", [$imgId]);
        if ($img) {
            Upload::delete($img['image']);
            Database::delete('news_gallery', 'id = ?', [$imgId]);
        }
        header("Location: /admin/news.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'promote_to_featured' && $id > 0) {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        $img = Database::fetchOne("SELECT * FROM news_gallery WHERE id = ?", [$imgId]);
        if ($img) {
            // Remover destaque anterior se houver
            $old = Database::fetchOne("SELECT featured_image FROM news WHERE id = ?", [$id]);
            if ($old && $old['featured_image']) Upload::delete($old['featured_image']);
            // Mover imagem da galeria para destaque
            Database::query("UPDATE news SET featured_image = ?, video_url = '', video_path = '' WHERE id = ?", [$img['image'], $id]);
            Database::delete('news_gallery', 'id = ?', [$imgId]);
            $_SESSION['flash_message'] = 'Imagem definida como destaque.';
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: /admin/news.php?action=form&id={$id}");
        exit;
    }
}

require __DIR__ . '/templates/header.php';

if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif;

// ── Formulário ─────────────────────────────────────────────────────
if ($action === 'form'):
    $news = $id ? Database::fetchOne("SELECT * FROM news WHERE id = ?", [$id]) : null;
    $galleryImages = $id ? Database::fetchAll("SELECT * FROM news_gallery WHERE news_id = ? ORDER BY display_order ASC", [$id]) : [];
    // Slots do carrossel (para picker de destaque)
    $carouselSlots = array_fill(1, 5, null);
    $pinnedManual = Database::fetchAll("SELECT display_order, image FROM carousel WHERE is_pinned = 1 AND display_order BETWEEN 1 AND 5 AND is_visible = 1");
    foreach ($pinnedManual as $pm) $carouselSlots[(int)$pm['display_order']] = 'Personalizado';
    $pinnedNews = Database::fetchAll("SELECT carousel_order, title, id FROM news WHERE is_pinned = 1 AND carousel_order BETWEEN 1 AND 5 AND is_featured = 1 AND is_visible = 1");
    foreach ($pinnedNews as $pn) {
        if ($id && $pn['id'] == $id) continue;
        $carouselSlots[(int)$pn['carousel_order']] = mb_substr($pn['title'], 0, 25);
    }
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $news ? 'Editar' : 'Nova' ?> Notícia</h2>
        <a href="/admin/news.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <form method="post" enctype="multipart/form-data" action="/admin/news.php<?= $id ? "?id={$id}" : '' ?>">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="save">

        <div class="row">
            <!-- Coluna principal -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="title">Título *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="<?= htmlspecialchars($news['title'] ?? '') ?>" required minlength="3" maxlength="200">
                        </div>
                        <div class="form-group mb-3">
                            <label for="summary">Subtítulo / Resumo *</label>
                            <textarea class="form-control" id="summary" name="summary" rows="2"
                                      required minlength="10" maxlength="300"><?= htmlspecialchars($news['summary'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Aparece na listagem de notícias. Máx. 300 caracteres.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="author">Autor <small class="text-muted">(opcional)</small></label>
                            <input type="text" class="form-control" id="author" name="author"
                                   value="<?= htmlspecialchars($news['author'] ?? '') ?>" maxlength="100"
                                   placeholder="Ex.: Assessoria de Comunicação">
                            <small class="form-text text-muted">Se preenchido, será exibido na notícia. Caso contrário, ficará oculto.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label>Conteúdo</label>
                            <div id="editor-toolbar">
                                <span class="ql-formats">
                                    <button class="ql-bold" title="Negrito"></button>
                                    <button class="ql-italic" title="Itálico"></button>
                                    <button class="ql-underline" title="Sublinhado"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-header" value="2" title="Título"></button>
                                    <button class="ql-header" value="3" title="Subtítulo"></button>
                                    <button class="ql-blockquote" title="Citação"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered" title="Lista numerada"></button>
                                    <button class="ql-list" value="bullet" title="Lista com marcadores"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-link" title="Inserir link"></button>
                                    <button class="ql-image" title="Inserir imagem (URL)"></button>
                                </span>
                                <span class="ql-formats">
                                    <select class="ql-align" title="Alinhamento"></select>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-clean" title="Limpar formatação"></button>
                                </span>
                            </div>
                            <div id="editor-container" style="min-height:250px;background:#fff;"><?= $news['body'] ?? '' ?></div>
                            <textarea class="d-none" id="body" name="body"><?= htmlspecialchars($news['body'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Galeria de mídia -->
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-photo-video"></i> Galeria de Mídia</h6></div>
                    <div class="card-body">
                        <?php $hasFeatured = !empty($news['featured_image']) || !empty($news['video_path']) || !empty($news['video_url']); ?>


                        <?php if (!empty($galleryImages)): ?>
                            <label class="small text-muted mb-2">Imagens da galeria (<?= count($galleryImages) ?>)</label>
                            <div class="row mb-3">
                                <?php foreach ($galleryImages as $gi): ?>
                                    <div class="col-md-3 col-sm-4 mb-3 text-center">
                                        <img src="/<?= htmlspecialchars($gi['image']) ?>" class="img-thumbnail" style="height:100px;object-fit:cover;width:100%">
                                        <div class="mt-1">
                                            <?php if (!$hasFeatured): ?>
                                            <form method="post" action="/admin/news.php?id=<?= $id ?>" class="d-inline">
                                                <?= CSRF::field() ?>
                                                <input type="hidden" name="action" value="promote_to_featured">
                                                <input type="hidden" name="image_id" value="<?= $gi['id'] ?>">
                                                <button type="submit" class="btn btn-outline-primary btn-sm" title="Usar como imagem de destaque da notícia">
                                                    <i class="fas fa-star"></i> Destaque
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="post" action="/admin/news.php?id=<?= $id ?>" class="d-inline">
                                                <?= CSRF::field() ?>
                                                <input type="hidden" name="action" value="delete_gallery_image">
                                                <input type="hidden" name="image_id" value="<?= $gi['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Remover esta imagem?" title="Remover">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label>Adicionar imagens</label>
                            <div class="custom-file-zone" id="gallery-drop-zone">
                                <input type="file" id="gallery-input-picker" accept="image/jpeg,image/png,image/webp" multiple style="display:none">
                                <input type="file" name="gallery_images[]" id="gallery-input-real" multiple style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">
                                <div id="gallery-preview" class="row"></div>
                                <div id="gallery-placeholder" class="text-center py-3" style="border:2px dashed #ccc;border-radius:8px;cursor:pointer;">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted">Clique para selecionar imagens ou arraste aqui</span>
                                    <br><small class="text-muted">960x540 (16:9). JPG, PNG ou WebP. Máx. 15 MB cada.</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <label class="small text-muted mb-2 d-block"><i class="fas fa-video"></i> Adicionar vídeo à galeria</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <input type="file" class="form-control-file" name="gallery_video" accept="video/mp4,video/webm">
                                    <small class="form-text text-muted">MP4 ou WebM. Máx. 100 MB.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <input type="url" class="form-control form-control-sm" name="gallery_video_url" placeholder="https://youtube.com/watch?v=..." value="">
                                    <small class="form-text text-muted">YouTube ou Vimeo.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra lateral -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0">Publicação</h6></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="published_at">Data de publicação</label>
                            <?php if (Auth::isAdmin()): ?>
                            <input type="datetime-local" class="form-control" id="published_at" name="published_at"
                                   value="<?= htmlspecialchars(str_replace(' ', 'T', substr($news['published_at'] ?? date('Y-m-d H:i:s'), 0, 16))) ?>">
                            <?php else: ?>
                            <input type="text" class="form-control" readonly
                                   value="<?= htmlspecialchars($news['published_at'] ?? date('Y-m-d H:i:s')) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1"
                                   <?= ($news['is_visible'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">Visível no site</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1"
                                   <?= ($news['is_featured'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">Destaque (aparece no carrossel)</label>
                        </div>
                        <div id="carousel-slot-picker" class="mb-3" style="padding-left:20px;<?= ($news['is_featured'] ?? 0) ? '' : 'display:none' ?>">
                            <label class="small" for="carousel_slot">Posição no carrossel</label>
                            <select class="form-control form-control-sm" id="carousel_slot" name="carousel_slot">
                                <?php for ($s = 1; $s <= 5; $s++):
                                    $occupant = $carouselSlots[$s];
                                    $isCurrent = ($news['carousel_order'] ?? 0) == $s;
                                ?>
                                <option value="<?= $s ?>"<?= $isCurrent ? ' selected' : '' ?>>
                                    Slot <?= $s ?><?= $occupant ? ' — ' . htmlspecialchars($occupant) . ($isCurrent ? '' : ' (trocar)') : ' — vazio' ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                            <small class="form-text text-muted">Ao selecionar um slot ocupado, o item anterior será removido.</small>
                        </div>
                        <script>
                        document.getElementById('is_featured').addEventListener('change', function() {
                            document.getElementById('carousel-slot-picker').style.display = this.checked ? '' : 'none';
                        });
                        </script>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_in_gallery" name="is_in_gallery" value="1"
                                   <?= ($news['is_in_gallery'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_in_gallery">Exibir no álbum de fotos</label>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> <?= $news ? 'Salvar Alterações' : 'Publicar Notícia' ?>
                        </button>
                    </div>
                </div>

                <?php
                    $featuredType = 'image';
                    if (!empty($news['video_path'])) $featuredType = 'video_file';
                    elseif (!empty($news['video_url'])) $featuredType = 'video_url';
                ?>
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-photo-video"></i> Mídia de Destaque</h6></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="featured_type" id="ft_image" value="image" <?= $featuredType === 'image' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ft_image"><i class="fas fa-image"></i> Imagem</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="featured_type" id="ft_video_file" value="video_file" <?= $featuredType === 'video_file' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ft_video_file"><i class="fas fa-film"></i> Vídeo (arquivo)</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="featured_type" id="ft_video_url" value="video_url" <?= $featuredType === 'video_url' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ft_video_url"><i class="fab fa-youtube"></i> Vídeo externo</label>
                            </div>
                        </div>

                        <div id="feat-image" class="feat-fields">
                            <input type="file" class="form-control-file" name="featured_image"
                                   accept="image/jpeg,image/png,image/webp" data-preview="featuredPreview">
                            <small class="form-text text-muted">Recomendado: 960x540 (16:9). Máx. 15 MB.</small>
                            <?php if (!empty($news['featured_image'])): ?>
                                <img src="/<?= htmlspecialchars($news['featured_image']) ?>" id="featuredPreview" class="upload-preview mt-2" style="width:100%">
                            <?php else: ?>
                                <img id="featuredPreview" class="upload-preview mt-2" style="display:none;width:100%">
                            <?php endif; ?>
                        </div>

                        <div id="feat-video_file" class="feat-fields" style="display:none">
                            <input type="file" class="form-control-file" name="video_file" accept="video/mp4,video/webm">
                            <small class="form-text text-muted">MP4 ou WebM. Máx. 100 MB.</small>
                            <?php if (!empty($news['video_path'])): ?>
                            <div class="mt-2">
                                <span class="badge badge-info"><i class="fas fa-film"></i> <?= htmlspecialchars(basename($news['video_path'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div id="feat-video_url" class="feat-fields" style="display:none">
                            <input type="url" class="form-control" name="video_url"
                                   value="<?= htmlspecialchars($news['video_url'] ?? '') ?>"
                                   placeholder="https://www.youtube.com/watch?v=...">
                            <small class="form-text text-muted">YouTube ou Vimeo.</small>
                        </div>
                    </div>
                </div>
                <script>
                (function() {
                    var radios = document.querySelectorAll('input[name="featured_type"]');
                    function toggle() {
                        document.querySelectorAll('.feat-fields').forEach(function(el) { el.style.display = 'none'; });
                        var checked = document.querySelector('input[name="featured_type"]:checked');
                        if (checked) document.getElementById('feat-' + checked.value).style.display = '';
                    }
                    radios.forEach(function(r) { r.addEventListener('change', toggle); });
                    toggle();
                })();
                </script>

                <?php if ($news): ?>
                <div class="card">
                    <div class="card-body text-muted">
                        <small>
                            <strong>Slug:</strong> <?= htmlspecialchars($news['slug']) ?><br>
                            <strong>Criada:</strong> <?= htmlspecialchars($news['created_at']) ?><br>
                            <strong>Atualizada:</strong> <?= htmlspecialchars($news['updated_at']) ?>
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>

<?php
// ── Listagem ────────────────────────────────────────────────────────
else:
    $allNews = Database::fetchAll("SELECT * FROM news ORDER BY published_at DESC");
?>
    <?php
    $igTokenSavedAt = Database::getSetting('instagram_token_saved_at');
    if ($igTokenSavedAt):
        $igDaysLeft = max(0, (int) ceil(((strtotime($igTokenSavedAt) + 60*86400) - time()) / 86400));
        if ($igDaysLeft <= 14):
            $igAlertClass = $igDaysLeft === 0 ? 'alert-danger' : 'alert-warning';
    ?>
    <div class="alert <?= $igAlertClass ?> alert-dismissible fade show mb-3">
        <i class="fab fa-instagram"></i>
        <?php if ($igDaysLeft === 0): ?>
            <strong>Token do Instagram expirado!</strong> O feed parou de atualizar.
        <?php else: ?>
            <strong>Token do Instagram expira em <?= $igDaysLeft ?> dia<?= $igDaysLeft !== 1 ? 's' : '' ?>.</strong>
        <?php endif; ?>
        <a href="/admin/settings.php" class="alert-link">Atualizar nas Configurações</a>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notícias <small class="text-muted">(<?= count($allNews) ?>)</small></h2>
        <a href="/admin/news.php?action=form" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nova Notícia
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($allNews)): ?>
                <p class="text-muted p-4">Nenhuma notícia cadastrada. <a href="/admin/news.php?action=form">Crie a primeira</a>.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">Img</th>
                            <th>Título</th>
                            <th>Data</th>
                            <th>Flags</th>
                            <th>Status</th>
                            <th class="actions-col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allNews as $n): ?>
                        <tr>
                            <td>
                                <?php if ($n['featured_image']): ?>
                                    <img src="/<?= htmlspecialchars($n['featured_image']) ?>" style="width:50px;height:28px;object-fit:cover;border-radius:3px">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($n['title']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($n['summary'], 0, 80)) ?>...</small>
                            </td>
                            <td><small><?= htmlspecialchars($n['published_at']) ?></small></td>
                            <td>
                                <?php if ($n['is_featured']): ?><span class="badge badge-warning" title="Destaque"><i class="fas fa-star"></i></span><?php endif; ?>
                                <?php if ($n['is_featured'] && $n['is_pinned']): ?><span class="badge badge-secondary" title="Fixado no slot <?= $n['carousel_order'] ?>"><i class="fas fa-thumbtack"></i> <?= $n['carousel_order'] ?></span><?php endif; ?>
                                <?php if ($n['is_in_gallery']): ?><span class="badge badge-info" title="Álbum"><i class="fas fa-images"></i></span><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $n['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>">
                                    <?= $n['is_visible'] ? 'Visível' : 'Oculto' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a href="/admin/news.php?action=form&id=<?= $n['id'] ?>" class="btn btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="post" action="/admin/news.php?id=<?= $n['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-outline-secondary btn-action" title="<?= $n['is_visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                        <i class="fas fa-<?= $n['is_visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="/admin/news.php?id=<?= $n['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-outline-danger btn-action" title="Excluir"
                                            data-confirm="Excluir &quot;<?= htmlspecialchars($n['title']) ?>&quot;?">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Instagram Feed -->
    <div class="card mt-4">
        <div class="card-header"><h6 class="mb-0"><i class="fab fa-instagram"></i> Instagram Feed</h6></div>
        <div class="card-body">
            <form method="post" action="/admin/news.php">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="save_instagram_token">
                <div class="form-group mb-3">
                    <label>Access Token (Instagram Graph API)</label>
                    <textarea class="form-control" name="instagram_access_token" rows="3" placeholder="Cole aqui o token gerado no Meta Developer Portal"><?= htmlspecialchars(Database::getSetting('instagram_access_token') ?? '') ?></textarea>
                    <small class="form-text text-muted">
                        Gere em: Meta Developer Portal &rarr; Apps &rarr; Instagram &rarr; API Setup &rarr; Generate Token.
                        O token expira em 60 dias.
                    </small>
                </div>
                <?php
                $igSaved = Database::getSetting('instagram_token_saved_at');
                if ($igSaved):
                    $igExp = strtotime($igSaved) + (60 * 86400);
                    $igLeft = max(0, (int) ceil(($igExp - time()) / 86400));
                    $igBadge = $igLeft > 14 ? 'badge-success' : ($igLeft > 7 ? 'badge-warning' : 'badge-danger');
                ?>
                <div class="mb-3">
                    <span class="badge <?= $igBadge ?>" style="font-size: 14px; padding: 6px 12px;">
                        <i class="fas fa-clock"></i>
                        <?= $igLeft === 0 ? 'Token expirado!' : $igLeft . ' dia' . ($igLeft !== 1 ? 's' : '') . ' restante' . ($igLeft !== 1 ? 's' : '') ?>
                    </span>
                    <small class="text-muted ml-2">
                        Salvo em <?= date('d/m/Y H:i', strtotime($igSaved)) ?> &mdash;
                        expira em <?= date('d/m/Y', $igExp) ?>
                    </small>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar Token</button>
            </form>
        </div>
    </div>

<?php endif;

require __DIR__ . '/templates/footer.php';
