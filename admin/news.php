<?php
/**
 * CRUD — Notícias
 */

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
        $data = [
            'title'        => $title,
            'slug'         => generateSlug($title),
            'summary'      => trim($_POST['summary'] ?? ''),
            'body'         => trim($_POST['body'] ?? ''),
            'published_at' => trim($_POST['published_at'] ?? date('Y-m-d')),
            'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
            'is_in_gallery' => isset($_POST['is_in_gallery']) ? 1 : 0,
            'is_visible'   => isset($_POST['is_visible']) ? 1 : 0,
        ];

        $errors = [];
        if (mb_strlen($data['title']) < 3) $errors[] = 'Título é obrigatório (mín. 3 caracteres).';
        if (mb_strlen($data['summary']) < 10) $errors[] = 'Resumo é obrigatório (mín. 10 caracteres).';

        // Verificar slug duplicado
        $existing = Database::fetchOne(
            "SELECT id FROM news WHERE slug = ? AND id != ?",
            [$data['slug'], $id]
        );
        if ($existing) {
            $data['slug'] .= '-' . time();
        }

        // Upload de imagem de destaque
        if (!empty($_FILES['featured_image']['name'])) {
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

        if (empty($errors)) {
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
                        Database::insert('news_gallery', [
                            'news_id'       => $id,
                            'image'         => $upload['path'],
                            'display_order' => $order++,
                        ]);
                    }
                }
            }

            header('Location: /admin/news.php');
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
                            <label for="summary">Resumo *</label>
                            <textarea class="form-control" id="summary" name="summary" rows="2"
                                      required minlength="10" maxlength="300"><?= htmlspecialchars($news['summary'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Aparece na listagem de notícias. Máx. 300 caracteres.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="body">Conteúdo</label>
                            <textarea class="form-control" id="body" name="body" rows="12"><?= htmlspecialchars($news['body'] ?? '') ?></textarea>
                            <small class="form-text text-muted">HTML permitido (parágrafos, listas, negrito, links, imagens).</small>
                        </div>
                    </div>
                </div>

                <!-- Galeria de imagens -->
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-images"></i> Galeria de Imagens</h6></div>
                    <div class="card-body">
                        <?php if (!empty($galleryImages)): ?>
                            <div class="row mb-3">
                                <?php foreach ($galleryImages as $gi): ?>
                                    <div class="col-md-3 col-sm-4 mb-3 text-center">
                                        <img src="/<?= htmlspecialchars($gi['image']) ?>" class="img-thumbnail" style="height:100px;object-fit:cover;width:100%">
                                        <form method="post" action="/admin/news.php?id=<?= $id ?>" class="mt-1">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="action" value="delete_gallery_image">
                                            <input type="hidden" name="image_id" value="<?= $gi['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Remover esta imagem?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Adicionar imagens</label>
                            <input type="file" class="form-control-file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <small class="form-text text-muted">Selecione múltiplas imagens de uma vez. JPG, PNG ou WebP. Máx. 5 MB cada.</small>
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
                            <input type="date" class="form-control" id="published_at" name="published_at"
                                   value="<?= htmlspecialchars($news['published_at'] ?? date('Y-m-d')) ?>">
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

                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0">Imagem de Destaque</h6></div>
                    <div class="card-body">
                        <input type="file" class="form-control-file" name="featured_image"
                               accept="image/jpeg,image/png,image/webp" data-preview="featuredPreview">
                        <small class="form-text text-muted">Recomendado: 800x450 (16:9). Máx. 5 MB.</small>
                        <?php if (!empty($news['featured_image'])): ?>
                            <img src="/<?= htmlspecialchars($news['featured_image']) ?>" id="featuredPreview" class="upload-preview mt-2" style="width:100%">
                        <?php else: ?>
                            <img id="featuredPreview" class="upload-preview mt-2" style="display:none;width:100%">
                        <?php endif; ?>
                    </div>
                </div>

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

<?php endif;

require __DIR__ . '/templates/footer.php';
