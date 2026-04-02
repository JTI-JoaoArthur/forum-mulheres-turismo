<?php
/**
 * CRUD — Palestrantes
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';
require_once __DIR__ . '/lib/Upload.php';

Auth::require();

$pageTitle = 'Palestrantes';
$action    = $_GET['action'] ?? 'list';
$id        = (int) ($_GET['id'] ?? 0);
$message   = $_SESSION['flash_message'] ?? null;
$msgType   = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ── Ações POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'position'      => trim($_POST['position'] ?? ''),
            'institution'   => trim($_POST['institution'] ?? ''),
            'linkedin'      => trim($_POST['linkedin'] ?? ''),
            'instagram'     => trim($_POST['instagram'] ?? ''),
            'website'       => trim($_POST['website'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        // Validação
        $errors = [];
        if (mb_strlen($data['name']) < 2) $errors[] = 'Nome é obrigatório (mín. 2 caracteres).';

        // Validar URLs contra javascript:/data: protocol injection
        foreach (['linkedin', 'instagram', 'website'] as $urlField) {
            $v = $data[$urlField];
            if ($v !== '' && !preg_match('#^(https?://|/[^/])#i', $v)) {
                $errors[] = ucfirst($urlField) . ' deve começar com http:// ou https://';
            }
        }

        // Upload de foto
        if (!empty($_FILES['photo']['name'])) {
            $upload = Upload::image($_FILES['photo'], 'speakers');
            if ($upload['success']) {
                $data['photo'] = $upload['path'];
                // Remover foto anterior se editando
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT photo FROM speakers WHERE id = ?", [$id]);
                    if ($old && $old['photo']) Upload::delete($old['photo']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }

        if (empty($errors)) {
            if ($id > 0) {
                Database::update('speakers', $data, 'id = ?', [$id]);
                Auth::log(Auth::user()['id'], 'speaker_updated', "Palestrante #{$id}: {$data['name']}");
                $_SESSION['flash_message'] = 'Palestrante atualizado(a) com sucesso.';
            } else {
                $id = Database::insert('speakers', $data);
                Auth::log(Auth::user()['id'], 'speaker_created', "Palestrante #{$id}: {$data['name']}");
                $_SESSION['flash_message'] = 'Palestrante cadastrado(a) com sucesso.';
            }

            // Salvar vínculos com programação
            Database::delete('schedule_speakers', 'speaker_id = ?', [$id]);
            $scheduleIds = $_POST['schedule_items'] ?? [];
            foreach ($scheduleIds as $scId) {
                $scId = (int) $scId;
                if ($scId > 0) {
                    Database::insert('schedule_speakers', [
                        'schedule_id' => $scId,
                        'speaker_id'  => $id,
                    ]);
                }
            }

            $_SESSION['flash_type'] = 'success';
            header('Location: /admin/speakers.php');
            exit;
        }

        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
        header("Location: /admin/speakers.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $speaker = Database::fetchOne("SELECT * FROM speakers WHERE id = ?", [$id]);
        if ($speaker) {
            if ($speaker['photo']) Upload::delete($speaker['photo']);
            Database::delete('speakers', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'speaker_deleted', "Palestrante #{$id}: {$speaker['name']}");
            $_SESSION['flash_message'] = 'Palestrante removido(a).';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: /admin/speakers.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $speaker = Database::fetchOne("SELECT is_visible, name FROM speakers WHERE id = ?", [$id]);
        if ($speaker) {
            $newVal = $speaker['is_visible'] ? 0 : 1;
            Database::query("UPDATE speakers SET is_visible = ? WHERE id = ?", [$newVal, $id]);
            $status = $newVal ? 'visível' : 'oculto(a)';
            Auth::log(Auth::user()['id'], 'speaker_toggled', "Palestrante #{$id}: {$speaker['name']} → {$status}");
        }
        header('Location: /admin/speakers.php');
        exit;
    }
}

require __DIR__ . '/templates/header.php';

// ── Flash message ───────────────────────────────────────────────────
if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif;

// ── Formulário (criar/editar) ───────────────────────────────────────
if ($action === 'form'):
    $speaker = $id ? Database::fetchOne("SELECT * FROM speakers WHERE id = ?", [$id]) : null;
    $dayLabels = [1 => '3 de Junho', 2 => '4 de Junho'];
    $allSchedule = Database::fetchAll("SELECT * FROM schedule WHERE is_visible = 1 ORDER BY day ASC, start_time ASC");
    $linkedScheduleIds = [];
    if ($id) {
        $links = Database::fetchAll("SELECT schedule_id FROM schedule_speakers WHERE speaker_id = ?", [$id]);
        foreach ($links as $l) $linkedScheduleIds[] = (int) $l['schedule_id'];
    }
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $speaker ? 'Editar' : 'Novo' ?> Palestrante</h2>
        <a href="/admin/speakers.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" action="/admin/speakers.php<?= $id ? "?id={$id}" : '' ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="save">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name">Nome *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($speaker['name'] ?? '') ?>" required minlength="2" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="position">Cargo</label>
                            <input type="text" class="form-control" id="position" name="position"
                                   value="<?= htmlspecialchars($speaker['position'] ?? '') ?>" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="institution">Instituição</label>
                            <input type="text" class="form-control" id="institution" name="institution"
                                   value="<?= htmlspecialchars($speaker['institution'] ?? '') ?>" maxlength="100">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="linkedin">LinkedIn</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin"
                                   value="<?= htmlspecialchars($speaker['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="instagram">Instagram</label>
                            <input type="url" class="form-control" id="instagram" name="instagram"
                                   value="<?= htmlspecialchars($speaker['instagram'] ?? '') ?>" placeholder="https://instagram.com/...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="website">Site</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?= htmlspecialchars($speaker['website'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="photo">Foto</label>
                            <input type="file" class="form-control-file" id="photo" name="photo"
                                   accept="image/jpeg,image/png,image/webp" data-preview="photoPreview">
                            <small class="form-text text-muted">Recomendado: 400x400 (1:1, quadrada). JPG, PNG ou WebP. Máx. 15 MB.</small>
                            <?php if (!empty($speaker['photo'])): ?>
                                <img src="/<?= htmlspecialchars($speaker['photo']) ?>" id="photoPreview" class="upload-preview mt-2">
                            <?php else: ?>
                                <img id="photoPreview" class="upload-preview mt-2" style="display:none">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="display_order">Ordem de exibição</label>
                            <input type="number" class="form-control" id="display_order" name="display_order"
                                   value="<?= htmlspecialchars($speaker['display_order'] ?? '0') ?>" min="0">
                            <small class="form-text text-muted">Menor número = aparece primeiro.</small>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1"
                                   <?= ($speaker['is_visible'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">Visível no site</label>
                        </div>
                    </div>
                </div>

                <?php if (!empty($allSchedule)): ?>
                <div class="form-group mb-3">
                    <label><i class="fas fa-calendar-alt"></i> Programação vinculada</label>
                    <div class="row">
                        <?php
                        $currentDay = 0;
                        foreach ($allSchedule as $sc):
                            if ($sc['day'] !== $currentDay):
                                $currentDay = $sc['day'];
                        ?>
                        <div class="col-12 mt-2 mb-1"><small class="text-muted font-weight-bold"><?= $dayLabels[$currentDay] ?? 'Dia ' . $currentDay ?></small></div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="schedule_items[]"
                                       value="<?= $sc['id'] ?>" id="sc_<?= $sc['id'] ?>"
                                       <?= in_array($sc['id'], $linkedScheduleIds) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sc_<?= $sc['id'] ?>">
                                    <strong><?= htmlspecialchars($sc['start_time']) ?>–<?= htmlspecialchars($sc['end_time']) ?></strong>
                                    <?= htmlspecialchars($sc['title']) ?>
                                    <?php if ($sc['location']): ?>
                                    <small class="text-muted"> — <?= htmlspecialchars($sc['location']) ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $speaker ? 'Salvar Alterações' : 'Cadastrar Palestrante' ?>
                </button>
            </form>
        </div>
    </div>

<?php
// ── Listagem ────────────────────────────────────────────────────────
else:
    $speakers = Database::fetchAll("SELECT * FROM speakers ORDER BY display_order ASC, name ASC");
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Palestrantes <small class="text-muted">(<?= count($speakers) ?>)</small></h2>
        <a href="/admin/speakers.php?action=form" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Novo Palestrante
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($speakers)): ?>
                <p class="text-muted p-4">Nenhum palestrante cadastrado. <a href="/admin/speakers.php?action=form">Cadastre o primeiro</a>.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px">Foto</th>
                            <th>Nome</th>
                            <th>Cargo / Instituição</th>
                            <th>Ordem</th>
                            <th>Status</th>
                            <th class="actions-col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($speakers as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['photo']): ?>
                                    <img src="/<?= htmlspecialchars($s['photo']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-user-circle fa-2x"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td><?= htmlspecialchars(($s['position'] ? $s['position'] : '') . ($s['institution'] ? ' — ' . $s['institution'] : '')) ?></td>
                            <td><?= $s['display_order'] ?></td>
                            <td>
                                <span class="badge <?= $s['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>">
                                    <?= $s['is_visible'] ? 'Visível' : 'Oculto' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a href="/admin/speakers.php?action=form&id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="post" action="/admin/speakers.php?id=<?= $s['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-outline-secondary btn-action" title="<?= $s['is_visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                        <i class="fas fa-<?= $s['is_visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="/admin/speakers.php?id=<?= $s['id'] ?>" class="d-inline">
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

<?php endif;

require __DIR__ . '/templates/footer.php';
