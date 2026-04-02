<?php
/**
 * Gerenciador do Carrossel — 5 slots fixos
 */

@ini_set('upload_max_filesize', '120M');
@ini_set('post_max_size', '125M');
@ini_set('max_execution_time', '300');

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

    // ── Salvar slide personalizado ─────────────────────────────────
    if ($postAction === 'save') {
        $mediaType = $_POST['media_type'] ?? 'image';
        $rawLink   = trim($_POST['link'] ?? '');
        $rawVideo  = trim($_POST['video_url'] ?? '');

        $data = [
            'is_in_gallery' => isset($_POST['is_in_gallery']) ? 1 : 0,
            'is_visible'    => isset($_POST['is_visible']) ? 1 : 0,
        ];

        // Limpar campos que não correspondem ao tipo selecionado
        if ($mediaType === 'image') {
            $data['link']       = $rawLink;
            $data['video_url']  = '';
            $data['video_path'] = $id ? (Database::fetchOne("SELECT video_path FROM carousel WHERE id = ?", [$id])['video_path'] ?? '') : '';
            // Se tinha video_path antigo, deletar
            if ($id && $data['video_path']) {
                Upload::delete($data['video_path']);
                $data['video_path'] = '';
            }
        } elseif ($mediaType === 'video_file') {
            $data['link']      = '';
            $data['video_url'] = '';
            // Manter image existente (thumbnail) ou vazio
            if (!$id && empty($_FILES['image']['name'])) {
                $data['image'] = '';
            }
        } elseif ($mediaType === 'video_url') {
            $data['link']      = '';
            $data['video_url'] = $rawVideo;
            // Se tinha video_path antigo, deletar
            if ($id) {
                $old = Database::fetchOne("SELECT video_path FROM carousel WHERE id = ?", [$id]);
                if ($old && $old['video_path']) Upload::delete($old['video_path']);
            }
            $data['video_path'] = '';
            // Manter image existente (thumbnail) ou vazio
            if (!$id && empty($_FILES['image']['name'])) {
                $data['image'] = '';
            }
        }

        $errors = [];
        if ($mediaType === 'image' && $rawLink !== '' && !preg_match('#^(https?://|/[^/])#i', $rawLink)) {
            $errors[] = 'Link deve comecar com http://, https:// ou /';
        }
        if ($mediaType === 'video_url' && ($rawVideo === '' || !preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/#i', $rawVideo))) {
            $errors[] = 'URL de video deve ser do YouTube ou Vimeo.';
        }

        // Upload de imagem
        if (!empty($_FILES['image']['name'])) {
            $upload = Upload::image($_FILES['image'], 'carousel');
            if ($upload['success']) {
                $data['image'] = $upload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT image FROM carousel WHERE id = ?", [$id]);
                    if ($old && $old['image'] && str_starts_with($old['image'], 'admin/uploads/')) Upload::delete($old['image']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }

        // Upload de video (arquivo)
        if ($mediaType === 'video_file' && !empty($_FILES['video_file']['name'])) {
            $vupload = Upload::video($_FILES['video_file'], 'carousel');
            if ($vupload['success']) {
                $data['video_path'] = $vupload['path'];
                if ($id > 0) {
                    $old = Database::fetchOne("SELECT video_path FROM carousel WHERE id = ?", [$id]);
                    if ($old && $old['video_path']) Upload::delete($old['video_path']);
                }
            } else {
                $errors[] = $vupload['error'];
            }
        }

        // Validar: novo slide precisa de conteudo
        if (!$id) {
            if ($mediaType === 'image' && empty($_FILES['image']['name'])) {
                $errors[] = 'Envie uma imagem para o slide.';
            } elseif ($mediaType === 'video_file' && empty($_FILES['video_file']['name'])) {
                $errors[] = 'Envie um arquivo de video.';
            }
        }

        if (empty($errors)) {
            // Posição no carrossel
            $slot = (int) ($_POST['carousel_slot'] ?? 0);
            if ($slot >= 1 && $slot <= 5) {
                // Liberar slot atual
                Database::query("UPDATE carousel SET is_pinned = 0, display_order = 0 WHERE display_order = ? AND is_pinned = 1 AND id != ?", [$slot, $id]);
                Database::query("UPDATE news SET is_pinned = 0, carousel_order = 0 WHERE carousel_order = ? AND is_pinned = 1", [$slot]);
                $data['is_pinned'] = 1;
                $data['display_order'] = $slot;
            } else {
                $data['is_pinned'] = 0;
                $data['display_order'] = 0;
            }

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

    // ── Excluir ────────────────────────────────────────────────────
    if ($postAction === 'delete' && $id > 0) {
        $slide = Database::fetchOne("SELECT image, video_path FROM carousel WHERE id = ?", [$id]);
        if ($slide) {
            if ($slide['image'] && str_starts_with($slide['image'], 'admin/uploads/')) Upload::delete($slide['image']);
            if ($slide['video_path']) Upload::delete($slide['video_path']);
            Database::delete('carousel', 'id = ?', [$id]);
            Auth::log(Auth::user()['id'], 'carousel_deleted', "Slide #{$id}");
            $_SESSION['flash_message'] = 'Slide removido.';
        }
        header('Location: /admin/carousel.php');
        exit;
    }

    // ── Toggle visibilidade ────────────────────────────────────────
    if ($postAction === 'toggle' && $id > 0) {
        $slide = Database::fetchOne("SELECT is_visible FROM carousel WHERE id = ?", [$id]);
        if ($slide) {
            $newVis = $slide['is_visible'] ? 0 : 1;
            Database::query("UPDATE carousel SET is_visible = ? WHERE id = ?", [$newVis, $id]);
            if (!$newVis) Database::query("UPDATE carousel SET is_pinned = 0, display_order = 0 WHERE id = ?", [$id]);
        }
        header('Location: /admin/carousel.php');
        exit;
    }

    // ── Fixar em slot ──────────────────────────────────────────────
    if ($postAction === 'pin') {
        $type     = $_POST['type'] ?? '';
        $sourceId = (int) ($_POST['source_id'] ?? 0);
        $slot     = (int) ($_POST['slot'] ?? 0);
        if (in_array($type, ['news', 'carousel']) && $sourceId > 0 && $slot >= 1 && $slot <= 5) {
            Database::query("UPDATE carousel SET is_pinned = 0, display_order = 0 WHERE display_order = ? AND is_pinned = 1", [$slot]);
            Database::query("UPDATE news SET is_pinned = 0, carousel_order = 0 WHERE carousel_order = ? AND is_pinned = 1", [$slot]);
            if ($type === 'carousel') {
                Database::query("UPDATE carousel SET is_pinned = 1, display_order = ? WHERE id = ?", [$slot, $sourceId]);
            } else {
                Database::query("UPDATE news SET is_pinned = 1, carousel_order = ? WHERE id = ?", [$slot, $sourceId]);
            }
            $_SESSION['flash_message'] = "Item fixado no slot {$slot}.";
        }
        header('Location: /admin/carousel.php');
        exit;
    }

    // ── Trocar posição (swap) ──────────────────────────────────────
    if ($postAction === 'swap') {
        $from = (int) ($_POST['from'] ?? 0);
        $to   = (int) ($_POST['to'] ?? 0);
        if ($from >= 1 && $from <= 5 && $to >= 1 && $to <= 5 && $from !== $to) {
            // Descobrir quem está em cada slot
            $inFrom = Database::fetchOne("SELECT id, 'carousel' as src FROM carousel WHERE is_pinned = 1 AND display_order = ?", [$from])
                   ?: Database::fetchOne("SELECT id, 'news' as src FROM news WHERE is_pinned = 1 AND carousel_order = ?", [$from]);
            $inTo   = Database::fetchOne("SELECT id, 'carousel' as src FROM carousel WHERE is_pinned = 1 AND display_order = ?", [$to])
                   ?: Database::fetchOne("SELECT id, 'news' as src FROM news WHERE is_pinned = 1 AND carousel_order = ?", [$to]);

            // Mover quem estava em $from para $to
            if ($inFrom) {
                if ($inFrom['src'] === 'carousel') {
                    Database::query("UPDATE carousel SET display_order = ? WHERE id = ?", [$to, $inFrom['id']]);
                } else {
                    Database::query("UPDATE news SET carousel_order = ? WHERE id = ?", [$to, $inFrom['id']]);
                }
            }
            // Mover quem estava em $to para $from
            if ($inTo) {
                if ($inTo['src'] === 'carousel') {
                    Database::query("UPDATE carousel SET display_order = ? WHERE id = ?", [$from, $inTo['id']]);
                } else {
                    Database::query("UPDATE news SET carousel_order = ? WHERE id = ?", [$from, $inTo['id']]);
                }
            }
            $_SESSION['flash_message'] = "Slots {$from} e {$to} trocados.";
        }
        header('Location: /admin/carousel.php');
        exit;
    }

    // ── Desfixar ───────────────────────────────────────────────────
    if ($postAction === 'unpin') {
        $type     = $_POST['type'] ?? '';
        $sourceId = (int) ($_POST['source_id'] ?? 0);
        if ($type === 'carousel' && $sourceId > 0) {
            Database::query("UPDATE carousel SET is_pinned = 0, display_order = 0 WHERE id = ?", [$sourceId]);
        } elseif ($type === 'news' && $sourceId > 0) {
            Database::query("UPDATE news SET is_pinned = 0, carousel_order = 0 WHERE id = ?", [$sourceId]);
        }
        $_SESSION['flash_message'] = 'Slot liberado.';
        header('Location: /admin/carousel.php');
        exit;
    }
}

require __DIR__ . '/templates/header.php';

if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif;

// ════════════════════════════════════════════════════════════════════
// FORMULARIO — slide personalizado (uma midia por vez)
// ════════════════════════════════════════════════════════════════════
if ($action === 'form'):
    $slide = $id ? Database::fetchOne("SELECT * FROM carousel WHERE id = ?", [$id]) : null;
    // Detectar tipo atual
    $currentType = 'image';
    if ($slide) {
        if (!empty($slide['video_path'])) $currentType = 'video_file';
        elseif (!empty($slide['video_url'])) $currentType = 'video_url';
    }
    // Slots ocupados (para picker de posição)
    $slotOccupants = array_fill(1, 5, null);
    $pinnedC = Database::fetchAll("SELECT id, display_order FROM carousel WHERE is_pinned = 1 AND display_order BETWEEN 1 AND 5 AND is_visible = 1");
    foreach ($pinnedC as $pc) {
        if ($id && $pc['id'] == $id) continue;
        $slotOccupants[(int)$pc['display_order']] = 'Slide #' . $pc['id'];
    }
    $pinnedN = Database::fetchAll("SELECT id, title, carousel_order FROM news WHERE is_pinned = 1 AND carousel_order BETWEEN 1 AND 5 AND is_featured = 1 AND is_visible = 1");
    foreach ($pinnedN as $pn) $slotOccupants[(int)$pn['carousel_order']] = mb_substr($pn['title'], 0, 25);
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $slide ? 'Editar' : 'Novo' ?> Slide Personalizado</h2>
        <a href="/admin/carousel.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data" action="/admin/carousel.php<?= $id ? "?id={$id}" : '' ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="save">

            <!-- Tipo de midia -->
            <div class="form-group mb-4">
                <label><strong>Tipo de conteudo</strong></label>
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="media_type" id="mt_image" value="image" <?= $currentType === 'image' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mt_image"><i class="fas fa-image"></i> Imagem (com link opcional)</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="media_type" id="mt_video_file" value="video_file" <?= $currentType === 'video_file' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mt_video_file"><i class="fas fa-film"></i> Video (arquivo MP4/WebM)</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="media_type" id="mt_video_url" value="video_url" <?= $currentType === 'video_url' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mt_video_url"><i class="fab fa-youtube"></i> Video externo (YouTube/Vimeo)</label>
                </div>
            </div>

            <!-- Campos por tipo -->
            <div id="fields-image" class="media-fields">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Imagem</label>
                            <input type="file" class="form-control-file" name="image" accept="image/jpeg,image/png,image/webp" data-preview="prevSlide">
                            <small class="form-text text-muted">1920x1080 (16:9). Max. 15 MB.</small>
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
                            <small class="form-text text-muted">Opcional. Redireciona ao clicar.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div id="fields-video_file" class="media-fields" style="display:none">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Arquivo de video</label>
                            <input type="file" class="form-control-file" name="video_file" accept="video/mp4,video/webm">
                            <small class="form-text text-muted">MP4 ou WebM. Max. 100 MB.</small>
                            <?php if ($slide && !empty($slide['video_path'])): ?>
                            <div class="mt-2"><span class="badge badge-info"><i class="fas fa-film"></i> <?= htmlspecialchars(basename($slide['video_path'])) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Capa do video (imagem)</label>
                            <input type="file" class="form-control-file" name="image" accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">Opcional. Thumbnail exibida antes do play.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div id="fields-video_url" class="media-fields" style="display:none">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>URL do video</label>
                            <input type="url" class="form-control" name="video_url" value="<?= htmlspecialchars($slide['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
                            <small class="form-text text-muted">YouTube ou Vimeo.</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <div class="form-group mb-3">
                <label><strong>Posição no carrossel</strong></label>
                <select class="form-control" name="carousel_slot">
                    <option value="0">Não fixar (salvar sem posição)</option>
                    <?php for ($s = 1; $s <= 5; $s++):
                        $occupant = $slotOccupants[$s];
                        $isCurrent = $slide && ($slide['is_pinned'] ?? 0) && ($slide['display_order'] ?? 0) == $s;
                    ?>
                    <option value="<?= $s ?>"<?= $isCurrent ? ' selected' : '' ?>>
                        Slot <?= $s ?><?= $occupant ? ' — ' . htmlspecialchars($occupant) . ' (trocar)' : ' — vazio' ?><?= $isCurrent ? ' (atual)' : '' ?>
                    </option>
                    <?php endfor; ?>
                </select>
                <small class="form-text text-muted">Ao selecionar um slot ocupado, o item anterior será removido.</small>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="is_visible" value="1" <?= ($slide['is_visible'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label">Visivel no site</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="is_in_gallery" value="1" <?= ($slide['is_in_gallery'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label">Exibir no album de fotos</label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </form>
    </div></div>

    <script>
    (function() {
        var radios = document.querySelectorAll('input[name="media_type"]');
        function toggle() {
            document.querySelectorAll('.media-fields').forEach(function(el) { el.style.display = 'none'; });
            var checked = document.querySelector('input[name="media_type"]:checked');
            if (checked) document.getElementById('fields-' + checked.value).style.display = '';
        }
        radios.forEach(function(r) { r.addEventListener('change', toggle); });
        toggle();
    })();
    </script>

<?php
// ════════════════════════════════════════════════════════════════════
// GERENCIADOR — 5 slots
// ════════════════════════════════════════════════════════════════════
else:
    function slideThumb(array $item): string {
        if (!empty($item['image'])) return '/' . htmlspecialchars($item['image']);
        if (!empty($item['video_url']) && preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $item['video_url'], $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
        }
        return '';
    }
    function slideLabel(array $item): string {
        if ($item['source'] === 'news') return htmlspecialchars(mb_substr($item['title'] ?? '', 0, 50));
        if (!empty($item['link'])) return htmlspecialchars(mb_substr($item['link'], 0, 50));
        if (!empty($item['video_path'])) return '<i class="fas fa-film"></i> Video enviado';
        if (!empty($item['video_url'])) return '<i class="fas fa-video"></i> Video externo';
        return 'Slide personalizado';
    }

    // Montar 5 slots
    $slots = array_fill(1, 5, null);
    $pinnedManual = Database::fetchAll("SELECT id, image, link, video_url, video_path, display_order, 'carousel' as source FROM carousel WHERE is_pinned = 1 AND display_order BETWEEN 1 AND 5 AND is_visible = 1");
    foreach ($pinnedManual as $item) $slots[(int)$item['display_order']] = $item;
    $pinnedNews = Database::fetchAll("SELECT id, title, featured_image as image, video_url, video_path, carousel_order as display_order, 'news' as source FROM news WHERE is_pinned = 1 AND carousel_order BETWEEN 1 AND 5 AND is_featured = 1 AND is_visible = 1");
    foreach ($pinnedNews as $item) {
        $pos = (int)$item['display_order'];
        if ($slots[$pos] === null) $slots[$pos] = $item;
    }

    // Slides personalizados nao fixados (disponiveis para fixar)
    $availableCustom = Database::fetchAll("SELECT id, image, link, video_url, video_path, 'carousel' as source FROM carousel WHERE is_pinned = 0 AND is_visible = 1 ORDER BY created_at DESC");

    // Todos os personalizados (para CRUD)
    $allCustom = Database::fetchAll("SELECT * FROM carousel ORDER BY created_at DESC");
    $filledCount = count(array_filter($slots));
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Carrossel de Destaques</h2>
        <a href="/admin/carousel.php?action=form" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Novo Slide</a>
    </div>

    <p class="text-muted mb-3">
        O carrossel tem <strong>5 posicoes</strong>. Preencha cada slot com um slide personalizado ou uma noticia em destaque.
    </p>

    <!-- 5 SLOTS -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="thead-light">
                    <tr><th style="width:50px">Slot</th><th style="width:70px">Preview</th><th>Conteudo</th><th style="width:120px">Tipo</th><th style="width:280px">Acao</th></tr>
                </thead>
                <tbody>
                <?php for ($i = 1; $i <= 5; $i++): $item = $slots[$i]; ?>
                <tr>
                    <td class="align-middle text-center"><strong style="color:#64428c;font-size:18px"><?= $i ?></strong></td>
                    <td class="align-middle">
                        <?php if ($item):
                            $thumb = slideThumb($item);
                            if ($thumb): ?>
                            <img src="<?= $thumb ?>" style="width:60px;height:34px;object-fit:cover;border-radius:3px">
                            <?php elseif (!empty($item['video_path'])): ?>
                            <i class="fas fa-film text-muted"></i>
                            <?php endif;
                        else: ?>
                            <span style="opacity:0.15"><i class="fas fa-image fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <?php if ($item): ?>
                            <?= slideLabel($item) ?>
                        <?php else: ?>
                            <span class="text-muted">Vazio</span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <?php if ($item): ?>
                            <span class="badge badge-<?= $item['source'] === 'news' ? 'warning' : 'primary' ?>"><?= $item['source'] === 'news' ? 'Noticia' : 'Personalizado' ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <?php if ($item): ?>
                            <!-- Setas de reordenação -->
                            <?php if ($i > 1): ?>
                            <form method="post" action="/admin/carousel.php" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="swap">
                                <input type="hidden" name="from" value="<?= $i ?>">
                                <input type="hidden" name="to" value="<?= $i - 1 ?>">
                                <button type="submit" class="btn btn-outline-dark btn-sm" title="Mover para cima"><i class="fas fa-arrow-up"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($i < 5): ?>
                            <form method="post" action="/admin/carousel.php" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="swap">
                                <input type="hidden" name="from" value="<?= $i ?>">
                                <input type="hidden" name="to" value="<?= $i + 1 ?>">
                                <button type="submit" class="btn btn-outline-dark btn-sm" title="Mover para baixo"><i class="fas fa-arrow-down"></i></button>
                            </form>
                            <?php endif; ?>
                            <!-- Liberar -->
                            <form method="post" action="/admin/carousel.php" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="unpin">
                                <input type="hidden" name="type" value="<?= $item['source'] ?>">
                                <input type="hidden" name="source_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Liberar slot"><i class="fas fa-times"></i></button>
                            </form>
                            <!-- Editar -->
                            <?php if ($item['source'] === 'carousel'): ?>
                            <a href="/admin/carousel.php?action=form&id=<?= $item['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php else: ?>
                            <a href="/admin/news.php?action=form&id=<?= $item['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                        <?php elseif (!empty($availableCustom)): ?>
                            <!-- Fixar slide disponivel -->
                            <form method="post" action="/admin/carousel.php" class="form-inline d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="pin">
                                <input type="hidden" name="slot" value="<?= $i ?>">
                                <input type="hidden" name="type" value="carousel">
                                <select name="source_id" class="form-control form-control-sm mr-1" style="width:auto">
                                    <?php foreach ($availableCustom as $ac): ?>
                                    <option value="<?= $ac['id'] ?>"><?= slideLabel($ac) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-thumbtack"></i></button>
                            </form>
                        <?php else: ?>
                            <a href="/admin/carousel.php?action=form" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Criar slide</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SLIDES PERSONALIZADOS (CRUD) -->
    <h5 class="mb-3"><i class="fas fa-images"></i> Slides Personalizados <small class="text-muted">(<?= count($allCustom) ?>)</small></h5>
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($allCustom)): ?>
                <p class="text-muted p-3">Nenhum slide personalizado. <a href="/admin/carousel.php?action=form">Criar</a>.</p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Preview</th><th>Descricao</th><th>Midia</th><th>Status</th><th class="actions-col">Acoes</th></tr></thead>
                    <tbody>
                    <?php foreach ($allCustom as $s): ?>
                        <tr>
                            <td><?php if ($s['image']): ?><img src="/<?= htmlspecialchars($s['image']) ?>" style="width:80px;height:45px;object-fit:cover;border-radius:3px"><?php else: ?><i class="fas fa-film text-muted"></i><?php endif; ?></td>
                            <td><?= $s['link'] ? htmlspecialchars(mb_substr($s['link'], 0, 40)) : '<span class="text-muted">&mdash;</span>' ?></td>
                            <td>
                                <?php if (!empty($s['video_path'])): ?><span class="badge badge-info"><i class="fas fa-film"></i> Arquivo</span>
                                <?php elseif (!empty($s['video_url'])): ?><span class="badge badge-secondary"><i class="fas fa-video"></i> Externo</span>
                                <?php else: ?><span class="badge badge-light">Imagem</span><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $s['is_visible'] ? 'badge-visible' : 'badge-hidden' ?>"><?= $s['is_visible'] ? 'Visivel' : 'Oculto' ?></span>
                                <?php if ($s['is_pinned']): ?><span class="badge badge-secondary ml-1"><i class="fas fa-thumbtack"></i> <?= $s['display_order'] ?></span><?php endif; ?>
                            </td>
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
