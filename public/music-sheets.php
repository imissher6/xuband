<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isOfficer()) { http_response_code(403); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'create_folder') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { flash('error', 'Folder name is required.'); redirect('/music-sheets.php'); }
        dbInsert('INSERT INTO music_folders (name,description,created_by) VALUES (?,?,?)', [$name,$desc,$user['id']]);
        flash('success', "Folder \"$name\" created.");
        redirect('/music-sheets.php');
    }

    if ($action === 'delete_folder') {
        $id = (int)($_POST['id'] ?? 0);
        $files = dbQuery('SELECT file_path FROM music_sheets WHERE folder_id = ?', [$id]);
        foreach ($files as $f) { $p = __DIR__ . '/' . $f['file_path']; if (file_exists($p)) @unlink($p); }
        dbExecute('DELETE FROM music_folders WHERE id = ?', [$id]);
        flash('success', 'Folder deleted.');
        redirect('/music-sheets.php');
    }

    if ($action === 'upload_file') {
        $folder_id  = (int)($_POST['folder_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $instrument = trim($_POST['instrument'] ?? '');
        if (!$folder_id || !$title) { flash('error', 'Folder and title are required.'); redirect('/music-sheets.php?folder=' . $folder_id); }
        if (empty($_FILES['file']['name'])) { flash('error', 'Please select a file.'); redirect('/music-sheets.php?folder=' . $folder_id); }
        $upload = uploadFile($_FILES['file'], 'music-sheets');
        if (!$upload['ok']) { flash('error', $upload['error']); redirect('/music-sheets.php?folder=' . $folder_id); }
        $sheet_id = dbInsert('INSERT INTO music_sheets (folder_id,title,instrument,file_path,file_name,file_size,file_type,uploaded_by) VALUES (?,?,?,?,?,?,?,?)',
            [$folder_id,$title,$instrument,$upload['path'],$upload['filename'],$upload['size'],$upload['mime'],$user['id']]);
        $members = dbQuery('SELECT id FROM users WHERE role="member" AND status="active"');
        foreach ($members as $m) {
            dbExecute('INSERT IGNORE INTO music_assignments (sheet_id,user_id,assigned_by) VALUES (?,?,?)', [$sheet_id,$m['id'],$user['id']]);
        }
        flash('success', "File \"$title\" uploaded.");
        redirect('/music-sheets.php?folder=' . $folder_id);
    }

    if ($action === 'delete_file') {
        $id        = (int)($_POST['id'] ?? 0);
        $folder_id = (int)($_POST['folder_id'] ?? 0);
        $sheet = dbQueryOne('SELECT * FROM music_sheets WHERE id = ?', [$id]);
        if ($sheet) {
            $p = __DIR__ . '/' . $sheet['file_path'];
            if (file_exists($p)) @unlink($p);
            dbExecute('DELETE FROM music_sheets WHERE id = ?', [$id]);
        }
        flash('success', 'File deleted.');
        redirect('/music-sheets.php?folder=' . $folder_id);
    }

    if ($action === 'save_assignments') {
        $sheet_id  = (int)($_POST['sheet_id'] ?? 0);
        $folder_id = (int)($_POST['folder_id'] ?? 0);
        $assigned  = $_POST['assigned'] ?? [];
        dbExecute('DELETE FROM music_assignments WHERE sheet_id = ?', [$sheet_id]);
        foreach ($assigned as $uid) {
            dbExecute('INSERT IGNORE INTO music_assignments (sheet_id,user_id,assigned_by) VALUES (?,?,?)', [$sheet_id,(int)$uid,$user['id']]);
        }
        flash('success', 'Assignments saved.');
        redirect('/music-sheets.php?folder=' . $folder_id);
    }
}

$folderId = (int)($_GET['folder'] ?? 0);

if ($folderId) {
    $folder = dbQueryOne('SELECT * FROM music_folders WHERE id = ?', [$folderId]);
    if (!$folder) { flash('error', 'Folder not found.'); redirect('/music-sheets.php'); }

    if (isOfficer()) {
        $sheets = dbQuery('SELECT ms.*, u.name AS uploader FROM music_sheets ms JOIN users u ON u.id = ms.uploaded_by WHERE ms.folder_id = ? ORDER BY ms.created_at DESC', [$folderId]);
    } else {
        $sheets = dbQuery('SELECT ms.*, u.name AS uploader FROM music_sheets ms JOIN users u ON u.id = ms.uploaded_by JOIN music_assignments ma ON ma.sheet_id = ms.id WHERE ms.folder_id = ? AND ma.user_id = ? ORDER BY ms.created_at DESC', [$folderId, $user['id']]);
    }
    $allMembers = isOfficer() ? dbQuery('SELECT id,name,instrument FROM users WHERE role="member" AND status="active" ORDER BY name') : [];

    layout_head('Music Sheets — ' . $folder['name'], 'music-sheets');
?>

<?php if ($e = getFlash('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-exclamation-triangle-fill"></i> <?= h($e) ?>
</div>
<?php endif; ?>
<?php if ($s = getFlash('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-check-circle-fill"></i> <?= h($s) ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="/music-sheets.php" class="btn btn-outline btn-sm">
    <i class="bi bi-arrow-left me-1"></i> Back to Folders
  </a>
  <h2 class="mb-0" style="color:var(--xu-navy)">
    <i class="bi bi-folder2-open me-2"></i><?= h($folder['name']) ?>
  </h2>
  <?php if ($folder['description']): ?>
  <span class="text-muted small"><?= h($folder['description']) ?></span>
  <?php endif; ?>
</div>

<?php if (!isOfficer()): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
  <i class="bi bi-info-circle-fill"></i> Showing only your assigned music sheets.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="card-title"><i class="bi bi-music-note-beamed me-2"></i>Files in this folder</span>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('xumodalUpload')">
      <i class="bi bi-upload me-1"></i> Upload File
    </button>
    <?php endif; ?>
  </div>
  <?php if (!$sheets): ?>
  <div class="empty-state">
    <div class="empty-icon"><i class="bi bi-music-note-beamed"></i></div>
    <p><?= isOfficer() ? 'No files uploaded yet.' : 'No sheets assigned to you yet.' ?></p>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Title</th><th>Instrument</th><th>File</th>
          <th>Uploaded by</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sheets as $sh):
          $ext  = strtolower(pathinfo($sh['file_name'], PATHINFO_EXTENSION));
          $icon = match($ext) {
            'pdf'                    => 'bi-file-pdf text-danger',
            'mp3'                    => 'bi-file-music text-primary',
            'jpg','jpeg','png','gif' => 'bi-file-image text-success',
            default                  => 'bi-file-earmark'
          };
          $size      = $sh['file_size'] ? round($sh['file_size']/1024,1).' KB' : '';
          $isImage   = in_array($ext, ['jpg','jpeg','png','gif']);
          $isPdf     = $ext === 'pdf';
          $isAudio   = $ext === 'mp3';
          $fileUrl   = '/' . h($sh['file_path']);
        ?>
        <tr>
          <td><strong><?= h($sh['title']) ?></strong></td>
          <td><?= $sh['instrument'] ? '<span class="badge bg-secondary">'.h($sh['instrument']).'</span>' : '<span class="text-muted">—</span>' ?></td>
          <td>
            <i class="bi <?= $icon ?>"></i>
            <span class="small ms-1"><?= h($sh['file_name']) ?><?= $size ? " · $size" : '' ?></span>
          </td>
          <td class="small text-muted"><?= h($sh['uploader']) ?></td>
          <td class="small text-muted"><?= formatDate($sh['created_at']) ?></td>
          <td>
            <div class="d-flex gap-1 flex-wrap">
              <?php if ($isPdf || $isImage): ?>
              <button class="btn btn-sm btn-outline-secondary"
                onclick="previewFile('<?= $fileUrl ?>', '<?= $ext ?>', '<?= h(addslashes($sh['title'])) ?>')">
                <i class="bi bi-eye me-1"></i>Preview
              </button>
              <?php elseif ($isAudio): ?>
              <button class="btn btn-sm btn-outline-secondary"
                onclick="previewFile('<?= $fileUrl ?>', 'mp3', '<?= h(addslashes($sh['title'])) ?>')">
                <i class="bi bi-play-circle me-1"></i>Play
              </button>
              <?php endif; ?>
              <a href="<?= $fileUrl ?>" download="<?= h($sh['file_name']) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download me-1"></i>Download
              </a>
              <?php if (isOfficer()): ?>
              <button class="btn btn-sm btn-outline-dark"
                onclick="openModal('xumodalAssign'); loadAssignments(<?= $sh['id'] ?>, <?= $folderId ?>, '<?= h(addslashes($sh['title'])) ?>')">
                <i class="bi bi-people me-1"></i>Assign
              </button>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="delete_file">
                <input type="hidden" name="id" value="<?= $sh['id'] ?>">
                <input type="hidden" name="folder_id" value="<?= $folderId ?>">
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this file?">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Preview Modal -->
<div class="xu-modal-overlay" id="xumodalPreview">
  <div class="xu-modal" style="max-width:800px;width:95vw">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="previewTitle">Preview</span>
      <button class="xu-modal-close" onclick="closeModal(this);stopAudio()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body p-0" id="previewBody" style="min-height:300px;display:flex;align-items:center;justify-content:center;background:#f8f9fa">
    </div>
  </div>
</div>

<?php if (isOfficer()): ?>
<!-- Upload Modal -->
<div class="xu-modal-overlay" id="xumodalUpload">
  <div class="xu-modal">
    <div class="xu-modal-header">
      <span class="xu-modal-title">Upload File to <?= h($folder['name']) ?></span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_file">
      <input type="hidden" name="folder_id" value="<?= $folderId ?>">
      <div class="xu-modal-body">
        <div class="mb-3">
          <label class="form-label">File *</label>
          <input name="file" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.mp3" required>
          <div class="form-text text-muted">PDF, JPG, PNG, GIF, MP3 (max 20MB). Auto-assigned to all active members.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Title *</label>
          <input name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Instrument</label>
          <input name="instrument" class="form-control" placeholder="e.g. Trumpet, Saxophone, Flute…">
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- Assign Modal -->
<div class="xu-modal-overlay" id="xumodalAssign">
  <div class="xu-modal">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="assignTitle">Manage Assignments</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" id="assignForm">
      <input type="hidden" name="action" value="save_assignments">
      <input type="hidden" name="sheet_id" id="assign_sheet_id" value="">
      <input type="hidden" name="folder_id" id="assign_folder_id" value="">
      <div class="xu-modal-body">
        <p class="small text-muted mb-3">Select which members can see and download this sheet:</p>
        <div id="assignMemberList">
          <?php foreach ($allMembers as $m): ?>
          <div class="d-flex align-items-center gap-2 py-2 border-bottom">
            <input type="checkbox" name="assigned[]" value="<?= $m['id'] ?>"
              class="form-check-input member-assign-cb" data-uid="<?= $m['id'] ?>">
            <span><?= h($m['name']) ?></span>
            <?php if ($m['instrument']): ?><span class="badge bg-secondary"><?= h($m['instrument']) ?></span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllAssign(true)">Select All</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllAssign(false)">None</button>
        <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function loadAssignments(sheetId, folderId, title) {
  document.getElementById('assign_sheet_id').value  = sheetId;
  document.getElementById('assign_folder_id').value = folderId;
  document.getElementById('assignTitle').textContent = 'Assign: ' + title;
  fetch('/api/assignments.php?sheet_id=' + sheetId)
    .then(r => r.json())
    .then(data => {
      document.querySelectorAll('.member-assign-cb').forEach(cb => {
        cb.checked = data.includes(parseInt(cb.dataset.uid));
      });
    });
}
function toggleAllAssign(val) {
  document.querySelectorAll('.member-assign-cb').forEach(cb => cb.checked = val);
}
function stopAudio() {
  const a = document.querySelector('#previewBody audio');
  if (a) { a.pause(); a.currentTime = 0; }
}
function previewFile(url, ext, title) {
  document.getElementById('previewTitle').textContent = title;
  const body = document.getElementById('previewBody');
  if (ext === 'pdf') {
    body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:75vh;border:0"></iframe>';
  } else if (['jpg','jpeg','png','gif'].includes(ext)) {
    body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:75vh;object-fit:contain;padding:1rem">';
  } else if (ext === 'mp3') {
    body.innerHTML = '<div class="p-4 text-center"><i class="bi bi-file-music" style="font-size:3rem;color:var(--xu-navy)"></i><br><br><audio controls autoplay style="width:100%;max-width:400px"><source src="' + url + '" type="audio/mpeg"></audio></div>';
  }
  openModal('xumodalPreview');
}
</script>

<?php

} else {
    if (isOfficer()) {
        $folders = dbQuery('SELECT f.*, u.name AS creator, COUNT(ms.id) AS file_count FROM music_folders f JOIN users u ON u.id = f.created_by LEFT JOIN music_sheets ms ON ms.folder_id = f.id GROUP BY f.id ORDER BY f.created_at DESC');
    } else {
        $folders = dbQuery('SELECT DISTINCT f.*, u.name AS creator, (SELECT COUNT(DISTINCT ms2.id) FROM music_sheets ms2 JOIN music_assignments ma2 ON ma2.sheet_id=ms2.id WHERE ms2.folder_id=f.id AND ma2.user_id=?) AS file_count FROM music_folders f JOIN users u ON u.id=f.created_by JOIN music_sheets ms ON ms.folder_id=f.id JOIN music_assignments ma ON ma.sheet_id=ms.id WHERE ma.user_id=? ORDER BY f.created_at DESC', [$user['id'],$user['id']]);
    }

    layout_head('Music Sheets', 'music-sheets');
?>

<?php if ($e = getFlash('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-exclamation-triangle-fill"></i> <?= h($e) ?>
</div>
<?php endif; ?>
<?php if ($s = getFlash('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-check-circle-fill"></i> <?= h($s) ?>
</div>
<?php endif; ?>

<?php if (!isOfficer()): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
  <i class="bi bi-info-circle-fill"></i> Showing folders that contain sheets assigned to you.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="card-title"><i class="bi bi-music-note-beamed me-2"></i>Music Sheet Library</span>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('xumodalFolder')">
      <i class="bi bi-folder-plus me-1"></i> New Folder
    </button>
    <?php endif; ?>
  </div>
  <div class="card-body p-3">
    <?php if (!$folders): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-folder2"></i></div>
      <p><?= isOfficer() ? 'No folders yet. Create one to start uploading music sheets.' : 'No music sheets assigned to you yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($folders as $f): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="folder-card" onclick="window.location='/music-sheets.php?folder=<?= $f['id'] ?>'">
          <i class="bi bi-folder2-open folder-icon"></i>
          <div class="fw-bold" style="color:var(--xu-navy);margin-bottom:4px"><?= h($f['name']) ?></div>
          <?php if ($f['description']): ?>
          <div class="small text-muted mb-2"><?= h(mb_substr($f['description'],0,60)) ?></div>
          <?php endif; ?>
          <div class="text-muted" style="font-size:.75rem">
            <i class="bi bi-file-earmark me-1"></i><?= $f['file_count'] ?> file<?= $f['file_count']!=1?'s':'' ?>
            &nbsp;&middot;&nbsp;<?= h($f['creator']) ?>
          </div>
          <?php if (isOfficer()): ?>
          <div class="mt-2" onclick="event.stopPropagation()">
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="delete_folder">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" data-confirm="Delete folder and all files inside?">
                <i class="bi bi-trash me-1"></i>Delete
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (isOfficer()): ?>
<div class="xu-modal-overlay" id="xumodalFolder">
  <div class="xu-modal">
    <div class="xu-modal-header">
      <span class="xu-modal-title">Create Folder</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_folder">
      <div class="xu-modal-body">
        <div class="mb-3">
          <label class="form-label">Folder Name *</label>
          <input name="name" class="form-control" placeholder="e.g. ABBA Medley, Competition 2025…" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-folder-plus me-1"></i>Create Folder
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php } ?>

<?php layout_foot(); ?>
