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

    if ($action === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $pinned  = isset($_POST['pinned']) ? 1 : 0;
        $expires = $_POST['expires_at'] ?: null;
        if (!$title || !$body) { flash('error', 'Title and body are required.'); redirect('/announcements.php'); }
        dbInsert('INSERT INTO announcements (title,body,created_by,pinned,expires_at) VALUES (?,?,?,?,?)',
            [$title, $body, $user['id'], $pinned, $expires]);
        flash('success', 'Announcement posted.');
        redirect('/announcements.php');
    }

    if ($action === 'update') {
        $id      = (int)($_POST['id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $pinned  = isset($_POST['pinned']) ? 1 : 0;
        $expires = $_POST['expires_at'] ?: null;
        if (!$title || !$body) { flash('error', 'Title and body are required.'); redirect('/announcements.php'); }
        dbExecute('UPDATE announcements SET title=?,body=?,pinned=?,expires_at=?,updated_at=NOW() WHERE id=?',
            [$title, $body, $pinned, $expires, $id]);
        flash('success', 'Announcement updated.');
        redirect('/announcements.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['del_id'] ?? $_POST['id'] ?? 0);
        dbExecute('DELETE FROM announcements WHERE id = ?', [$id]);
        flash('success', 'Announcement deleted.');
        redirect('/announcements.php');
    }

    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            dbExecute("DELETE FROM announcements WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' announcement(s) deleted.');
        }
        redirect('/announcements.php');
    }
}

$announcements = dbQuery('SELECT a.*, u.name AS author FROM announcements a JOIN users u ON u.id = a.created_by ORDER BY a.pinned DESC, a.created_at DESC');

layout_head('Announcements', 'announcements');
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

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="card-title"><i class="bi bi-megaphone me-2"></i>Announcements</span>
    <?php if (isOfficer()): ?>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-danger d-none" id="bulkDeleteBtn"
        onclick="bulkDelete('announcements')">
        <i class="bi bi-trash me-1"></i>Delete Selected (<span id="bulkCount">0</span>)
      </button>
      <button class="btn btn-primary btn-sm" onclick="openModal('xumodalAnn'); resetAnnForm()">
        <i class="bi bi-plus-lg me-1"></i> Post Announcement
      </button>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-body p-3">
    <?php if (!$announcements): ?>
    <div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><p>No announcements yet.</p></div>
    <?php else: ?>
    <form method="POST" id="bulkForm-announcements">
      <input type="hidden" name="action" value="bulk_delete">
      <?php foreach ($announcements as $ann):
        $expired = $ann['expires_at'] && strtotime($ann['expires_at']) < strtotime('today');
      ?>
      <div class="announcement-card <?= $ann['pinned'] ? 'pinned' : '' ?>" <?= $expired ? 'style="opacity:.55"' : '' ?>>
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <?php if (isOfficer()): ?>
            <input type="checkbox" name="ids[]" value="<?= $ann['id'] ?>"
              class="form-check-input bulk-cb" onchange="updateBulkCount('announcements')" style="margin-top:2px">
            <?php endif; ?>
            <?php if ($ann['pinned']): ?>
            <span class="badge text-bg-warning" style="font-size:.7rem">
              <i class="bi bi-pin-angle-fill me-1"></i>PINNED
            </span>
            <?php endif; ?>
            <?php if ($expired): ?><span class="badge text-bg-secondary">Expired</span><?php endif; ?>
          </div>
          <?php if (isOfficer()): ?>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary"
              onclick="openModal('xumodalAnn'); fillAnn(<?= htmlspecialchars(json_encode($ann), ENT_QUOTES) ?>)">
              <i class="bi bi-pencil me-1"></i>Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger"
              onclick="if(confirm('Delete this announcement?')){this.closest('form').querySelector('[name=action]').value='delete';this.closest('form').querySelector('[name=del_id]').value='<?= $ann['id'] ?>';this.closest('form').submit();}">
              <i class="bi bi-trash me-1"></i>Delete
            </button>
          </div>
          <?php endif; ?>
        </div>
        <h3 style="color:var(--xu-navy);margin-bottom:6px"><?= h($ann['title']) ?></h3>
        <p style="color:var(--text);line-height:1.7;white-space:pre-line"><?= h($ann['body']) ?></p>
        <div class="text-muted" style="font-size:.75rem;margin-top:6px">
          <i class="bi bi-person me-1"></i><?= h($ann['author']) ?>
          &nbsp;&middot;&nbsp;
          <i class="bi bi-clock me-1"></i><?= formatDateTime($ann['created_at']) ?>
          <?= $ann['expires_at'] ? ' &nbsp;&middot;&nbsp; Expires ' . formatDate($ann['expires_at']) : '' ?>
        </div>
      </div>
      <?php endforeach; ?>
      <input type="hidden" name="del_id" value="">
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if (isOfficer()): ?>
<div class="xu-modal-overlay" id="xumodalAnn">
  <div class="xu-modal" style="max-width:640px">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="annModalTitle">Post Announcement</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="ann_action" value="create">
      <input type="hidden" name="id"     id="ann_id"     value="">
      <div class="xu-modal-body">
        <div class="mb-3">
          <label class="form-label">Title *</label>
          <input id="ann_title" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Body *</label>
          <textarea id="ann_body" name="body" class="form-control" rows="6" required></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Expiry Date (optional)</label>
            <input id="ann_expires" name="expires_at" type="date" class="form-control">
          </div>
          <div class="col-md-6 d-flex align-items-end pb-1">
            <div class="form-check">
              <input id="ann_pinned" name="pinned" type="checkbox" class="form-check-input">
              <label class="form-check-label" for="ann_pinned">Pin this announcement</label>
            </div>
          </div>
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Post</button>
      </div>
    </form>
  </div>
</div>
<script>
function resetAnnForm() {
  document.getElementById('ann_action').value = 'create';
  document.getElementById('annModalTitle').textContent = 'Post Announcement';
  ['id','title','body','expires'].forEach(k => { const el = document.getElementById('ann_'+k); if(el) el.value=''; });
  document.getElementById('ann_pinned').checked = false;
}
function fillAnn(a) {
  document.getElementById('ann_action').value = 'update';
  document.getElementById('annModalTitle').textContent = 'Edit Announcement';
  document.getElementById('ann_id').value      = a.id;
  document.getElementById('ann_title').value   = a.title;
  document.getElementById('ann_body').value    = a.body;
  document.getElementById('ann_expires').value = a.expires_at || '';
  document.getElementById('ann_pinned').checked = a.pinned == 1;
}
function updateBulkCount(scope) {
  const checked = document.querySelectorAll('#bulkForm-' + scope + ' .bulk-cb:checked').length;
  const btn = document.getElementById('bulkDeleteBtn');
  document.getElementById('bulkCount').textContent = checked;
  btn.classList.toggle('d-none', checked === 0);
}
function bulkDelete(scope) {
  const checked = document.querySelectorAll('#bulkForm-' + scope + ' .bulk-cb:checked').length;
  if (!checked) return;
  if (!confirm('Delete ' + checked + ' selected announcement(s)?')) return;
  document.getElementById('bulkForm-' + scope).submit();
}
// Select all toggle
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('.card-header');
  if (!header) return;
  const selectAllBtn = document.createElement('button');
  selectAllBtn.type = 'button';
  selectAllBtn.className = 'btn btn-sm btn-outline-secondary';
  selectAllBtn.innerHTML = '<i class="bi bi-check-all me-1"></i>Select All';
  selectAllBtn.onclick = function() {
    const cbs = document.querySelectorAll('#bulkForm-announcements .bulk-cb');
    const allChecked = [...cbs].every(c => c.checked);
    cbs.forEach(c => c.checked = !allChecked);
    updateBulkCount('announcements');
    selectAllBtn.innerHTML = allChecked
      ? '<i class="bi bi-check-all me-1"></i>Select All'
      : '<i class="bi bi-x-circle me-1"></i>Deselect All';
  };
  document.querySelector('.d-flex.gap-2').prepend(selectAllBtn);
});
</script>
<?php endif; ?>

<?php layout_foot(); ?>
