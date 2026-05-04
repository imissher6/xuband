<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isOfficer()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $pinned  = isset($_POST['pinned']) ? 1 : 0;
        $expires = $_POST['expires_at'] ?: null;
        if (!$title || !$body) { flash('error', 'Title and body are required.'); redirect('/announcements.php'); }
        dbInsert('INSERT INTO announcements (title,body,created_by,pinned,expires_at) VALUES (?,?,?,?,?)',
            [$title,$body,$user['id'],$pinned,$expires]);
        flash('success', 'Announcement posted.');
        redirect('/announcements.php');
    }

    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $body   = trim($_POST['body'] ?? '');
        $pinned = isset($_POST['pinned']) ? 1 : 0;
        $expires= $_POST['expires_at'] ?: null;
        dbExecute('UPDATE announcements SET title=?,body=?,pinned=?,expires_at=?,updated_at=NOW() WHERE id=?',
            [$title,$body,$pinned,$expires,$id]);
        flash('success', 'Announcement updated.');
        redirect('/announcements.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        dbExecute('DELETE FROM announcements WHERE id = ?', [$id]);
        flash('success', 'Announcement deleted.');
        redirect('/announcements.php');
    }
}

$announcements = dbQuery('SELECT a.*, u.name AS author FROM announcements a JOIN users u ON u.id = a.created_by ORDER BY a.pinned DESC, a.created_at DESC');

layout_head('Announcements', 'announcements');
?>

<?php if ($e = getFlash('error')): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?= h($e) ?></div><?php endif; ?>
<?php if ($s = getFlash('success')): ?><div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">📢 Announcements</span>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" data-modal="modalAnn" onclick="resetAnnForm()">+ Post Announcement</button>
    <?php endif; ?>
  </div>
  <div class="card-body" style="padding:20px">
    <?php if (!$announcements): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No announcements yet.</p></div>
    <?php else: foreach ($announcements as $ann):
      $expired = $ann['expires_at'] && strtotime($ann['expires_at']) < time();
    ?>
    <div class="announcement-card <?= $ann['pinned'] ? 'pinned' : '' ?>" <?= $expired ? 'style="opacity:.55"' : '' ?>>
      <div class="flex items-center gap-2 mb-2" style="justify-content:space-between">
        <div class="flex items-center gap-2">
          <?php if ($ann['pinned']): ?><span style="font-size:.72rem;font-weight:800;color:var(--gold);text-transform:uppercase">📌 Pinned</span><?php endif; ?>
          <?php if ($expired): ?><span class="badge badge-inactive">Expired</span><?php endif; ?>
        </div>
        <?php if (isOfficer()): ?>
        <div class="flex gap-2">
          <button class="btn btn-xs btn-outline" data-modal="modalAnn" onclick="fillAnn(<?= htmlspecialchars(json_encode($ann), ENT_QUOTES) ?>)">Edit</button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $ann['id'] ?>">
            <button class="btn btn-xs btn-danger" data-confirm="Delete this announcement?">Delete</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <h3 style="color:var(--navy);margin-bottom:6px"><?= h($ann['title']) ?></h3>
      <p style="color:var(--text);line-height:1.7;white-space:pre-line"><?= h($ann['body']) ?></p>
      <div class="text-xs text-muted mt-2">
        Posted by <?= h($ann['author']) ?> · <?= formatDateTime($ann['created_at']) ?>
        <?= $ann['expires_at'] ? ' · Expires ' . formatDate($ann['expires_at']) : '' ?>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php if (isOfficer()): ?>
<!-- Post/Edit Modal -->
<div class="modal-overlay" id="modalAnn">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <span class="modal-title" id="annModalTitle">Post Announcement</span>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="ann_action" value="create">
      <input type="hidden" name="id" id="ann_id" value="">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Title *</label><input id="ann_title" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Body *</label><textarea id="ann_body" name="body" class="form-control" rows="5" required></textarea></div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Expiry Date (optional)</label>
            <input id="ann_expires" name="expires_at" type="date" class="form-control">
          </div>
          <div class="form-group" style="padding-top:28px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input id="ann_pinned" name="pinned" type="checkbox" style="width:16px;height:16px">
              <span class="form-label" style="margin:0">Pin this announcement</span>
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Post</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetAnnForm() {
  document.getElementById('ann_action').value = 'create';
  document.getElementById('annModalTitle').textContent = 'Post Announcement';
  document.getElementById('ann_id').value = '';
  document.getElementById('ann_title').value = '';
  document.getElementById('ann_body').value = '';
  document.getElementById('ann_expires').value = '';
  document.getElementById('ann_pinned').checked = false;
}
function fillAnn(a) {
  document.getElementById('ann_action').value = 'update';
  document.getElementById('annModalTitle').textContent = 'Edit Announcement';
  document.getElementById('ann_id').value = a.id;
  document.getElementById('ann_title').value = a.title;
  document.getElementById('ann_body').value = a.body;
  document.getElementById('ann_expires').value = a.expires_at || '';
  document.getElementById('ann_pinned').checked = a.pinned == 1;
}
</script>
<?php endif; ?>

<?php layout_foot(); ?>
