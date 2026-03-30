<?php
/**
 * CRUD — Carrossel de Destaques (slides manuais)
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Carrossel';
$action    = $_GET['action'] ?? 'list';
$id        = (int) ($_GET['id'] ?? 0);
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $rawLink = trim($_POST['link'] ?? '');
        $data = [
            'link'          => $rawLink,
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_pinned'     => isset($_POST['is_pinned']) ? 1 : 0,
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        $errors = [];
        // Validar URL do link contra javascript:/data: protocol
        if ($rawLink !== '' && !preg_match('#^(https?://|/[^/])#i', $rawLink)) {
            $errors[] = 'Link deve começar com http://, https:// ou /';
        }
        if (!$id && empty($_FILES['image']['name'])) {
            $errors[] = 'Imagem é obrigatória para novo slide.';
        }

        if (!empty($_FILES['image']['name'])) {
            $upload = Upload::image($_FILES['image'], 'carousel');
            if ($upload['success']) {
                $data['image'] = $upload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT image FROM carousel WHERE id = ?", [$id]);
                    if ($old && $old['image']) Upload::delete($old['image']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }

        if (empty($errors)) {
            if ($id > 0) {
                Database::update('carousel', $data, 'id = ?', [$id]);
                Auth::log(Auth::user()['id'], 'carousel_updated', "Slide #{$id}");
            } else {
                Database::insert('carousel', $data);
                Auth::log(Auth::user()['id'], 'carousel_created', "Novo slide");
            }
            $_SESSION['flash_message'] = 'Slide salvo com sucesso.';
            header('Location: /admin/carousel.php');
            exit;
        }

        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
        header("Location: /admin/carousel.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $slide = Database::fetchOne("SELECT image FROM carousel WHERE id = ?", [$id]);
        if ($slide) {
            Upload::delete($slide['image']);
            Database::delete('carousel', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'carousel_deleted', "Slide #{$id}");
            $_SESSION['flash_message'] = 'Slide removido.';
        }
        header('Location: /admin/carousel.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $slide = Database::fetchOne("SELECT is_visible FROM carousel WHERE id = ?", [$id]);
        if ($slide) {
            Database::query("UPDATE carousel SET is_visible = ? WHERE id = ?", [$slide['is_visible'] ? 0 : 1, $id]);
        }
        header('Location: /admin/carousel.php');
        exit;
    }
}

require __DIR__ . '/templates/header.php';

if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif;

if ($action === 'form'):
    $slide = $id ? Database::fetchOne("SELECT * FROM carousel WHERE id = ?", [$id]) : null;
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $slide ? 'Editar' : 'Novo' ?> Slide</h2>
        <a href="/admin/carousel.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data" action="/admin/carousel.php<?= $id ? "?id={$id}" : '' ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="save">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Imagem <?= $slide ? '' : '*' ?></label>
                        <input type="file" class="form-control-file" name="image" accept="image/jpeg,image/png,image/webp" data-preview="prevSlide">
                        <small class="form-text text-muted">Recomendado: 1920x1080 (16:9). Máx. 5 MB.</small>
                        <?php if ($slide && $slide['image']): ?>
                            <img src="/<?= htmlspecialchars($slide['image']) ?>" id="prevSlide" class="upload-preview mt-2" style="max-width:400px">
                        <?php else: ?>
                            <img id="prevSlide" class="upload-preview mt-2" style="display:none;max-width:400px">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Link (ao clicar no slide)</label>
                        <input type="url" class="form-control" name="link" value="<?= htmlspecialchars($slide['link'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <div class="form-group mb-3">
                        <label>Ordem</label>
                        <input type="number" class="form-control" name="display_order" value="<?= $slide['display_order'] ?? 0 ?>" min="0">
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="is_pinned" value="1" <?= ($slide['is_pinned'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label">Fixar posição</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_visible" value="1" <?= ($slide['is_visible'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label">Visível no site</label>
                    </div>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </form>
    </div></div>

<?php else:
    $slides = Database::fetchAll("SELECT * FROM carousel ORDER BY display_order ASC");
    $featuredNews = Database::fetchAll("SELECT id, title, featured_image FROM news WHERE is_featured = 1 AND is_visible = 1 ORDER BY published_at DESC");
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Carrossel de Destaques</h2>
        <a href="/admin/carousel.php?action=form" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Novo Slide</a>
    </div>

    <?php if (!empty($featuredNews)): ?>
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Slides automáticos (notícias em destaque)</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                <?php foreach ($featuredNews as $n): ?>
                    <tr>
                        <td style="width:60px">
                            <?php if ($n['featured_image']): ?>
                                <img src="/<?= htmlspecialchars($n['featured_image']) ?>" style="width:50px;height:28px;object-fit:cover;border-radius:3px">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($n['title']) ?></td>
                        <td class="text-muted"><small>Automático (notícia em destaque)</small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">Slides manuais (<?= count($slides) ?>)</h6></div>
        <div class="card-body p-0">
            <?php if (empty($slides)): ?>
                <p class="text-muted p-3">Nenhum slide manual. <a href="/admin/carousel.php?action=form">Adicionar</a>.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Preview</th><th>Link</th><th>Ordem</th><th>Status</th><th class="actions-col">Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($slides as $s): ?>
                        <tr>
                            <td><img src="/<?= htmlspecialchars($s['image']) ?>" style="width:80px;height:45px;object-fit:cover;border-radius:3px"></td>
                            <td><?= $s['link'] ? htmlspecialchars($s['link']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $s['display_order'] ?><?= $s['is_pinned'] ? ' <i class="fas fa-thumbtack text-muted" title="Fixado"></i>' : '' ?></td>
                            <td><span class="badge <?= $s['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>"><?= $s['is_visible'] ? 'Visível' : 'Oculto' ?></span></td>
                            <td class="actions-col">
                                <a href="/admin/carousel.php?action=form&id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-action"><i class="fas fa-edit"></i></a>
                                <form method="post" action="/admin/carousel.php?id=<?= $s['id'] ?>" class="d-inline"><?= CSRF::field() ?><input type="hidden" name="action" value="toggle"><button type="submit" class="btn btn-outline-secondary btn-action"><i class="fas fa-<?= $s['is_visible'] ? 'eye-slash' : 'eye' ?>"></i></button></form>
                                <form method="post" action="/admin/carousel.php?id=<?= $s['id'] ?>" class="d-inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete"><button type="submit" class="btn btn-outline-danger btn-action" data-confirm="Excluir este slide?"><i class="fas fa-trash"></i></button></form>
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
