<?php
/**
 * CRUD — Programação
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRF.php';

Auth::require();

$pageTitle = 'Programação';
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
            'day'           => (int) ($_POST['day'] ?? 1),
            'start_time'    => trim($_POST['start_time'] ?? ''),
            'end_time'      => trim($_POST['end_time'] ?? ''),
            'title'         => trim($_POST['title'] ?? ''),
            'location'      => trim($_POST['location'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        $errors = [];
        if (mb_strlen($data['title']) < 2) $errors[] = 'Título é obrigatório.';
        if (empty($data['start_time'])) $errors[] = 'Horário de início é obrigatório.';
        if (empty($data['end_time'])) $errors[] = 'Horário de fim é obrigatório.';
        if (!in_array($data['day'], [1, 2])) $errors[] = 'Dia inválido.';

        if (empty($errors)) {
            if ($id > 0) {
                Database::update('schedule', $data, 'id = ?', [$id]);
                Auth::log(Auth::user()['id'], 'schedule_updated', "Item #{$id}: {$data['title']}");
            } else {
                $newId = Database::insert('schedule', $data);
                Auth::log(Auth::user()['id'], 'schedule_created', "Item #{$newId}: {$data['title']}");
            }
            $_SESSION['flash_message'] = 'Item da programação salvo com sucesso.';
            $_SESSION['flash_type'] = 'success';
            header('Location: /admin/schedule.php');
            exit;
        }

        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
        header("Location: /admin/schedule.php?action=form&id={$id}");
        exit;
    }

    if ($postAction === 'delete' && $id > 0) {
        $item = Database::fetchOne("SELECT title FROM schedule WHERE id = ?", [$id]);
        if ($item) {
            Database::delete('schedule', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'schedule_deleted', "Item #{$id}: {$item['title']}");
            $_SESSION['flash_message'] = 'Item removido.';
        }
        header('Location: /admin/schedule.php');
        exit;
    }

    if ($postAction === 'toggle' && $id > 0) {
        $item = Database::fetchOne("SELECT is_visible FROM schedule WHERE id = ?", [$id]);
        if ($item) {
            Database::query("UPDATE schedule SET is_visible = ? WHERE id = ?", [$item['is_visible'] ? 0 : 1, $id]);
        }
        header('Location: /admin/schedule.php');
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

if ($action === 'form'):
    $item = $id ? Database::fetchOne("SELECT * FROM schedule WHERE id = ?", [$id]) : null;
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $item ? 'Editar' : 'Novo' ?> Item da Programação</h2>
        <a href="/admin/schedule.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="/admin/schedule.php<?= $id ? "?id={$id}" : '' ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="save">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="title">Título *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="<?= htmlspecialchars($item['title'] ?? '') ?>" required maxlength="80">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="day">Dia *</label>
                            <select class="form-control" id="day" name="day" required>
                                <option value="1" <?= ($item['day'] ?? 1) == 1 ? 'selected' : '' ?>>Dia 1 — 3 de Junho</option>
                                <option value="2" <?= ($item['day'] ?? '') == 2 ? 'selected' : '' ?>>Dia 2 — 4 de Junho</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="location">Local</label>
                            <input type="text" class="form-control" id="location" name="location"
                                   value="<?= htmlspecialchars($item['location'] ?? '') ?>" maxlength="60" placeholder="Auditório, Sala, etc.">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="start_time">Início *</label>
                            <input type="time" class="form-control" id="start_time" name="start_time"
                                   value="<?= htmlspecialchars($item['start_time'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="end_time">Fim *</label>
                            <input type="time" class="form-control" id="end_time" name="end_time"
                                   value="<?= htmlspecialchars($item['end_time'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="display_order">Ordem</label>
                            <input type="number" class="form-control" id="display_order" name="display_order"
                                   value="<?= $item['display_order'] ?? 0 ?>" min="0">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1"
                                   <?= ($item['is_visible'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">Visível no site</label>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="description">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              maxlength="200"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
            </form>
        </div>
    </div>

<?php else:
    $day1 = Database::fetchAll("SELECT * FROM schedule WHERE day = 1 ORDER BY display_order ASC, start_time ASC");
    $day2 = Database::fetchAll("SELECT * FROM schedule WHERE day = 2 ORDER BY display_order ASC, start_time ASC");
    $total = count($day1) + count($day2);
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Programação <small class="text-muted">(<?= $total ?>)</small></h2>
        <a href="/admin/schedule.php?action=form" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Novo Item
        </a>
    </div>

    <?php foreach ([1 => $day1, 2 => $day2] as $dayNum => $items): ?>
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Dia <?= $dayNum ?> — <?= $dayNum === 1 ? '3' : '4' ?> de Junho</h6></div>
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
                <p class="text-muted p-3">Nenhum item para este dia.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Horário</th><th>Título</th><th>Local</th><th>Status</th><th class="actions-col">Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['start_time']) ?> — <?= htmlspecialchars($s['end_time']) ?></td>
                            <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                            <td><?= htmlspecialchars($s['location'] ?: '-') ?></td>
                            <td><span class="badge <?= $s['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>"><?= $s['is_visible'] ? 'Visível' : 'Oculto' ?></span></td>
                            <td class="actions-col">
                                <a href="/admin/schedule.php?action=form&id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-action"><i class="fas fa-edit"></i></a>
                                <form method="post" action="/admin/schedule.php?id=<?= $s['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?><input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-outline-secondary btn-action"><i class="fas fa-<?= $s['is_visible'] ? 'eye-slash' : 'eye' ?>"></i></button>
                                </form>
                                <form method="post" action="/admin/schedule.php?id=<?= $s['id'] ?>" class="d-inline">
                                    <?= CSRF::field() ?><input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-outline-danger btn-action" data-confirm="Excluir este item?"><i class="fas fa-trash"></i></button>
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
