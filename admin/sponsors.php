<?php
/**
 * CRUD — Apoio e Realização (logos de parceiros)
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Apoio e Realização';
$action    = $_GET['action'] ?? 'list';
$id        = (int) ($_GET['id'] ?? 0);
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'website'       => trim($_POST['website'] ?? ''),
            'category'      => ($_POST['category'] ?? '') === 'realizacao' ? 'realizacao' : 'apoio',
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        $errors = [];
        if (mb_strlen($data['name']) < 2) $errors[] = 'Nome é obrigatório (mín. 2 caracteres).';
        if (!$id && empty($_FILES['logo']['name'])) $errors[] = 'Logo é obrigatória para novo registro.';

        if (!empty($_FILES['logo']['name'])) {
            $upload = Upload::image($_FILES['logo'], 'sponsors');
            if ($upload['success']) {
                $data['logo'] = $upload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT logo FROM sponsors WHERE id = ?", [$id]);
                    if ($old && $old['logo']) Upload::delete($old['logo']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }

        if (empty($errors)) {
            if ($id > 0) {
                Database::update('sponsors', $data, 'id = ?', [$id]);
                Auth::log(Auth::user()['id'], 'sponsor_updated', "Parceiro #{$id}: {$data['name']}");
                $_SESSION['flash_message'] = 'Parceiro atualizado com sucesso.';
            } else {
                $newId = Database::insert('sponsors', $data);
                Auth::log(Auth::user()['id'], 'sponsor_created', "Parceiro #{$newId}: {$data['name']}");
                $_SESSION['flash_message'] = 'Parceiro cadastrado com sucesso.';
            }
            $_SESSION['flash_type'] = 'success';
            header('Location: /admin/sponsors.php');
            exit;
        }

        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
        header("Location: /admin/sponsors.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $sponsor = Database::fetchOne("SELECT * FROM sponsors WHERE id = ?", [$id]);
        if ($sponsor) {
            Upload::delete($sponsor['logo']);
            Database::delete('sponsors', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'sponsor_deleted', "Parceiro #{$id}: {$sponsor['name']}");
            $_SESSION['flash_message'] = 'Parceiro removido.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: /admin/sponsors.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $sponsor = Database::fetchOne("SELECT is_visible, name FROM sponsors WHERE id = ?", [$id]);
        if ($sponsor) {
            $newVal = $sponsor['is_visible'] ? 0 : 1;
            Database::query("UPDATE sponsors SET is_visible = ? WHERE id = ?", [$newVal, $id]);
            Auth::log(Auth::user()['id'], 'sponsor_toggled', "Parceiro #{$id}: {$sponsor['name']}");
        }
        header('Location: /admin/sponsors.php');
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
    $sponsor = $id ? Database::fetchOne("SELECT * FROM sponsors WHERE id = ?", [$id]) : null;
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $sponsor ? 'Editar' : 'Novo' ?> Parceiro</h2>
        <a href="/admin/sponsors.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" action="/admin/sponsors.php<?= $id ? "?id={$id}" : '' ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="save">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name">Nome da instituição *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($sponsor['name'] ?? '') ?>" required minlength="2" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="category">Categoria *</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="apoio" <?= ($sponsor['category'] ?? 'apoio') === 'apoio' ? 'selected' : '' ?>>Apoio</option>
                                <option value="realizacao" <?= ($sponsor['category'] ?? '') === 'realizacao' ? 'selected' : '' ?>>Realização</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="display_order">Ordem</label>
                            <input type="number" class="form-control" id="display_order" name="display_order"
                                   value="<?= $sponsor['display_order'] ?? 0 ?>" min="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="website">Site</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?= htmlspecialchars($sponsor['website'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Logo <?= $sponsor ? '' : '*' ?></label>
                            <input type="file" class="form-control-file" name="logo"
                                   accept="image/jpeg,image/png,image/webp" data-preview="logoPreview">
                            <small class="form-text text-muted">PNG com fundo transparente recomendado. Máx. 5 MB.</small>
                            <?php if (!empty($sponsor['logo'])): ?>
                                <img src="/<?= htmlspecialchars($sponsor['logo']) ?>" id="logoPreview" class="upload-preview mt-2" style="max-height:80px;background:#f8f9fa;padding:8px;border-radius:4px">
                            <?php else: ?>
                                <img id="logoPreview" class="upload-preview mt-2" style="display:none;max-height:80px">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1"
                           <?= ($sponsor['is_visible'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_visible">Visível no site</label>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $sponsor ? 'Salvar Alterações' : 'Cadastrar Parceiro' ?>
                </button>
            </form>
        </div>
    </div>

<?php
// ── Listagem ────────────────────────────────────────────────────────
else:
    $apoio = Database::fetchAll("SELECT * FROM sponsors WHERE category = 'apoio' ORDER BY display_order ASC, name ASC");
    $realizacao = Database::fetchAll("SELECT * FROM sponsors WHERE category = 'realizacao' ORDER BY display_order ASC, name ASC");
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Apoio e Realização <small class="text-muted">(<?= count($apoio) + count($realizacao) ?>)</small></h2>
        <a href="/admin/sponsors.php?action=form" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Novo Parceiro
        </a>
    </div>

    <?php foreach (['realizacao' => ['Realização', $realizacao], 'apoio' => ['Apoio', $apoio]] as $cat => [$label, $items]): ?>
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="fas fa-<?= $cat === 'realizacao' ? 'award' : 'handshake' ?>"></i> <?= $label ?> (<?= count($items) ?>)</h6></div>
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
                <p class="text-muted p-3">Nenhum parceiro nesta categoria.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">Logo</th>
                            <th>Nome</th>
                            <th>Site</th>
                            <th>Ordem</th>
                            <th>Status</th>
                            <th class="actions-col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $s): ?>
                        <tr>
                            <td>
                                <img src="/<?= htmlspecialchars($s['logo']) ?>" style="max-width:50px;max-height:30px;object-fit:contain;background:#f8f9fa;padding:2px;border-radius:3px">
                            </td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td>
                                <?php if ($s['website']): ?>
                                    <a href="<?= htmlspecialchars($s['website']) ?>" target="_blank" rel="noopener"><small><?= htmlspecialchars(parse_url($s['website'], PHP_URL_HOST) ?: $s['website']) ?></small></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['display_order'] ?></td>
                            <td>
                                <span class="badge <?= $s['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>">
                                    <?= $s['is_visible'] ? 'Visível' : 'Oculto' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a href="/admin/sponsors.php?action=form&id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="post" action="/admin/sponsors.php?id=<?= $s['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-outline-secondary btn-action" title="<?= $s['is_visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                        <i class="fas fa-<?= $s['is_visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="/admin/sponsors.php?id=<?= $s['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-outline-danger btn-action" title="Excluir"
                                            data-confirm="Excluir <?= htmlspecialchars($s['name']) ?>?">
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
    <?php endforeach; ?>

<?php endif;

require __DIR__ . '/templates/footer.php';
