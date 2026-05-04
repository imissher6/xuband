<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

// Handle Upload (officers/moderator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isOfficer()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $title   = trim($_POST['title'] ?? '');
        $composer= trim($_POST['composer'] ?? '');
        $arranger= trim($_POST['arranger'] ?? '');
        $section = trim($_POST['instrument_section'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$title) { flash('error', 'Title is required.'); redirect('/music-sheets.php'); }
        if (empty($_FILES['file']['name'])) { flash('error', 'Please select a file.'); redirect('/music-sheets.php'); }

        $upload = uploadFile($_FILES['file'], 'music-sheets');
        if (!$upload['ok']) { flash('error', $upload['error']); redirect('/music-sheets.php'); }

        dbInsert('INSERT INTO music_sheets (title,composer,arranger,instrument_section,file_path,file_name,file_size,file_type,description,uploaded_by)
                  VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$title,$composer,$arranger,$section,$upload['path'],$upload['filename'],
             $upload['size'],$upload['mime'],$desc,$user['id']]);
        flash('success', "\"$title\" uploaded.");
        redirect('/music-sheets.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sheet = dbQueryOne('SELECT * FROM music_sheets WHERE id = ?', [$id]);
        if ($sheet) {
            $full = __DIR__ . '/' . $sheet['file_path'];
            if (file_exists($full)) @unlink($full);
            dbExecute('DELETE FROM music_sheets WHERE id = ?', [$id]);
        }
        flash('success', 'Sheet deleted.');
        redirect('/music-sheets.php');
    }
}

// Filter
$search  = trim($_GET['q'] ?? '');
$section = trim($_GET['section'] ?? '');
$params  = [];
$where   = 'WHERE 1=1';
if ($search)  { $where .= ' AND (ms.title LIKE ? OR ms.composer LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($section) { $where .= ' AND ms.instrument_section = ?'; $params[] = $section; }

$sheets   = dbQuery("SELECT ms.*, u.name AS uploader FROM music_sheets ms JOIN users u ON u.id = ms.uploaded_by $where ORDER BY ms.created_at DESC", $params);
$sections = array_column(dbQuery('SELECT DISTINCT instrument_section FROM music_sheets WHERE instrument_section IS NOT NULL AND instrument_section != "" ORDER BY instrument_section'), 'instrument_section');

layout_head('Music Sheets', 'music-sheets');
?>

<?php if ($e = getFlash('error')): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?= h($e) ?></div><?php endif; ?>
<?php if ($s = getFlash('success')): ?><div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div><?php endif; ?>

<div class="card mb-4">
  <div class="card-header">
    <span class="card-title">🎼 Music Sheet Library</span>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" data-modal="modalUpload">+ Upload Sheet</button>
    <?php endif; ?>
  </div>
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" class="search-bar">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input name="q" type="search" class="form-control" placeholder="Search title or composer…" value="<?= h($search) ?>">
      </div>
      <select name="section" class="form-control" style="width:auto">
        <option value="">All Sections</option>
        <?php foreach ($sections as $sec): ?>
        <option value="<?= h($sec) ?>" <?= $section===$sec?'selected':'' ?>><?= h($sec) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">Filter</button>
      <?php if ($search || $section): ?><a href="/music-sheets.php" class="btn btn-sm" style="color:var(--red)">Clear</a><?php endif; ?>
    </form>
  </div>

  <?php if (!$sheets): ?>
  <div class="empty-state"><div class="empty-icon">🎼</div><p>No music sheets uploaded yet.</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;padding:20px">
    <?php foreach ($sheets as $sh):
      $ext = strtolower(pathinfo($sh['file_name'], PATHINFO_EXTENSION));
      $icon = match($ext) { 'pdf' => '📄', 'mp3' => '🎵', 'jpg','jpeg','png','gif' => '🖼️', default => '📁' };
      $size = $sh['file_size'] ? round($sh['file_size']/1024, 1) . ' KB' : '';
    ?>
    <div class="card" style="box-shadow:var(--shadow-md)">
      <div style="padding:16px">
        <div style="font-size:2.5rem;margin-bottom:10px"><?= $icon ?></div>
        <div style="font-weight:700;color:var(--navy);margin-bottom:4px"><?= h($sh['title']) ?></div>
        <?php if ($sh['composer']): ?><div class="text-sm text-muted">Composer: <?= h($sh['composer']) ?></div><?php endif; ?>
        <?php if ($sh['arranger']): ?><div class="text-sm text-muted">Arranger: <?= h($sh['arranger']) ?></div><?php endif; ?>
        <?php if ($sh['instrument_section']): ?>
          <span class="badge badge-member mt-2"><?= h($sh['instrument_section']) ?></span>
        <?php endif; ?>
        <?php if ($sh['description']): ?><div class="text-sm text-muted mt-2"><?= h(mb_substr($sh['description'],0,80)) ?></div><?php endif; ?>
        <div class="text-xs text-muted mt-2">
          By <?= h($sh['uploader']) ?> · <?= formatDate($sh['created_at']) ?> <?= $size ? "· $size" : '' ?>
        </div>
      </div>
      <div style="padding:10px 16px;border-top:1px solid var(--border);display:flex;gap:8px">
        <a href="/<?= h($sh['file_path']) ?>" target="_blank" class="btn btn-outline btn-xs">👁 View</a>
        <a href="/<?= h($sh['file_path']) ?>" download class="btn btn-gold btn-xs">⬇ Download</a>
        <?php if (isOfficer()): ?>
        <form method="POST" style="margin-left:auto">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $sh['id'] ?>">
          <button type="submit" class="btn btn-danger btn-xs" data-confirm="Delete this sheet?">🗑</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (isOfficer()): ?>
<!-- Upload Modal -->
<div class="modal-overlay" id="modalUpload">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Upload Music Sheet</span>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">File *</label>
          <input name="file" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.mp3" required>
          <div class="form-hint">Accepted: PDF, JPG, PNG, GIF, MP3 (max 20MB)</div>
        </div>
        <div class="form-group"><label class="form-label">Title *</label><input name="title" class="form-control" required></div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Composer</label><input name="composer" class="form-control"></div>
          <div class="form-group"><label class="form-label">Arranger</label><input name="arranger" class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label">Instrument Section</label>
          <input name="instrument_section" class="form-control" placeholder="e.g. Brass, Woodwinds, Percussion…">
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Upload</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php layout_foot(); ?>
