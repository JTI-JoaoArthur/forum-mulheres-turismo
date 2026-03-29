<?php
/**
 * CRUD — Galeria de Fotos (uploads manuais)
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Galeria';
$action    = $_GET['action'] ?? 'list';
$id        = (int) ($_GET['id'] ?? 0);
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'upload') {
        $uploaded = 0;
        $errors   = [];

        if (!empty($_FILES['images']['name'][0])) {
            $maxOrder = Database::fetchOne("SELECT MAX(display_order) as m FROM gallery");
            $order = ($maxOrder['m'] ?? 0) + 1;

            foreach ($_FILES['images']['name'] as $i => $name) {
                if (empty($name)) continue;
                $file = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                ];
                $upload = Upload::image($file, 'gallery');
                if ($upload['success']) {
                    Database::insert('gallery', [
                        'image'         => $upload['path'],
                        'caption'       => '',
                        'display_order' => $order++,
                    ]);
                    $uploaded++;
                } else {
                    $errors[] = "{$name}: {$upload['error']}";
                }
            }
        }

        if ($uploaded > 0) {
            Auth::log(Auth::user()['id'], 'gallery_uploaded', "{$uploaded} foto(s)");
            $_SESSION['flash_message'] = "{$uploaded} foto(s) adicionada(s) com sucesso.";
            $_SESSION['flash_type'] = 'success';
        }
        if (!empty($errors)) {
            $_SESSION['flash_message'] = ($uploaded > 0 ? "{$uploaded} foto(s) enviada(s). " : '') . 'Erros: ' . implode('; ', $errors);
            $_SESSION['flash_type'] = $uploaded > 0 ? 'warning' : 'danger';
        }
        if ($uploaded === 0 && empty($errors)) {
            $_SESSION['flash_message'] = 'Nenhuma imagem selecionada.';
            $_SESSION['flash_type'] = 'warning';
        }

        header('Location: /admin/gallery.php');
        exit;
    }

    if ($postAction === 'save' && $id > 0) {
        $caption = trim($_POST['caption'] ?? '');
        $order   = (int) ($_POST['display_order'] ?? 0);
        Database::update('gallery', [
            'caption'       => $caption,
            'display_order' => $order,
        ], 'id = ?', [$id]);
        Auth::log(Auth::user()['id'], 'gallery_updated', "Foto #{$id}");
        $_SESSION['flash_message'] = 'Foto atualizada.';
        header('Location: /admin/gallery.php');
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $photo = Database::fetchOne("SELECT image FROM gallery WHERE id = ?", [$id]);
        if ($photo) {
            Upload::delete($photo['image']);
            Database::delete('gallery', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'gallery_deleted', "Foto #{$id}");
            $_SESSION['flash_message'] = 'Foto removida.';
        }
        header('Location: /admin/gallery.php');
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

$photos = Database::fetchAll("SELECT * FROM gallery ORDER BY display_order ASC");
$newsPhotos = Database::fetchAll(
    "SELECT n.title, n.featured_image FROM news n WHERE n.is_in_gallery = 1 AND n.is_visible = 1 AND n.featured_image IS NOT NULL AND n.featured_image != '' ORDER BY n.published_at DESC"
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Galeria de Fotos</h2>
</div>

<!-- Upload de novas fotos -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-upload"></i> Enviar Fotos</h6></div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="upload">
            <div class="form-group mb-3">
                <input type="file" class="form-control-file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                <small class="form-text text-muted">Selecione múltiplas imagens. JPG, PNG ou WebP. Máx. 5 MB cada.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> Enviar</button>
        </form>
    </div>
</div>

<!-- Fotos automáticas (de notícias) -->
<?php if (!empty($newsPhotos)): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-newspaper"></i> Fotos de notícias no álbum (<?= count($newsPhotos) ?>)</h6></div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($newsPhotos as $np): ?>
                <div class="col-md-2 col-sm-3 col-4 mb-3 text-center">
                    <img src="/<?= htmlspecialchars($np['featured_image']) ?>" class="img-thumbnail" style="height:80px;object-fit:cover;width:100%">
                    <small class="d-block text-muted mt-1"><?= htmlspecialchars(mb_substr($np['title'], 0, 25)) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <small class="text-muted">Gerenciadas na seção Notícias (flag "Exibir no álbum").</small>
    </div>
</div>
<?php endif; ?>

<!-- Fotos manuais -->
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-images"></i> Fotos manuais (<?= count($photos) ?>)</h6></div>
    <div class="card-body">
        <?php if (empty($photos)): ?>
            <p class="text-muted">Nenhuma foto manual no álbum.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($photos as $p): ?>
                    <div class="col-md-3 col-sm-4 col-6 mb-4">
                        <div class="card h-100">
                            <img src="/<?= htmlspecialchars($p['image']) ?>" class="card-img-top" style="height:150px;object-fit:cover">
                            <div class="card-body p-2">
                                <form method="post" action="/admin/gallery.php?id=<?= $p['id'] ?>">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="save">
                                    <input type="text" class="form-control form-control-sm mb-1" name="caption"
                                           value="<?= htmlspecialchars($p['caption'] ?? '') ?>" placeholder="Legenda (opcional)">
                                    <input type="number" class="form-control form-control-sm mb-1" name="display_order"
                                           value="<?= $p['display_order'] ?>" min="0" title="Ordem">
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-save"></i></button>
                                </form>
                                <form method="post" action="/admin/gallery.php?id=<?= $p['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Excluir esta foto?"><i class="fas fa-trash"></i></button>
                                </form>
                                    </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
