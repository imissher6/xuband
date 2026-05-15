<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

$members = dbQuery(
    'SELECT u.id, u.name, u.role, u.instrument, u.year_level, u.student_id,
            u.status, u.avatar_path, u.contact_number, u.profile_notes,
            COALESCE(ps.total_points,0) AS penalty_points
     FROM users u
     LEFT JOIN penalty_summary ps ON ps.user_id = u.id
     WHERE u.role != "moderator" AND u.status = "active"
     ORDER BY u.role ASC, u.name ASC'
);

layout_head('Members', 'band-members');
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="card-title"><i class="bi bi-people me-2"></i>Band Members</span>
    <div class="input-group" style="max-width:260px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input id="memberSearch" type="search" class="form-control" placeholder="Search…">
    </div>
  </div>
  <div class="card-body p-3">
    <?php if (!$members): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-people"></i></div>
      <p>No members found.</p>
    </div>
    <?php else: ?>
    <div class="row g-3" id="memberGrid">
      <?php foreach ($members as $m):
        $initials = strtoupper(substr($m['name'], 0, 1));
        $isMe = $m['id'] === $user['id'];
        $hasAvatar = !empty($m['avatar_path']) && file_exists(__DIR__ . '/' . $m['avatar_path']);
      ?>
      <div class="col-sm-6 col-md-4 col-lg-3 member-item">
        <div class="member-card-clickable card h-100 text-center p-3"
             style="border:1.5px solid #e3e6ea;border-radius:.6rem;cursor:pointer;transition:box-shadow .18s,transform .18s"
             onclick="openMemberModal(<?= htmlspecialchars(json_encode([
               'id'            => $m['id'],
               'name'          => $m['name'],
               'role'          => $m['role'],
               'instrument'    => $m['instrument'] ?? '',
               'year_level'    => $m['year_level'] ?? '',
               'student_id'    => $m['student_id'] ?? '',
               'contact_number'=> $m['contact_number'] ?? '',
               'profile_notes' => $m['profile_notes'] ?? '',
               'penalty_points'=> $m['penalty_points'],
               'avatar_path'   => $hasAvatar ? $m['avatar_path'] : '',
               'initials'      => $initials,
               'is_me'         => $isMe,
             ]), ENT_QUOTES) ?>)">
          <?php if ($hasAvatar): ?>
          <img src="<?= h($m['avatar_path']) ?>" alt="<?= h($m['name']) ?>"
               style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto .75rem">
          <?php else: ?>
          <div class="user-avatar mx-auto mb-3" style="width:64px;height:64px;font-size:1.4rem">
            <?= h($initials) ?>
          </div>
          <?php endif; ?>
          <div class="fw-bold" style="color:var(--xu-navy)">
            <?= h($m['name']) ?>
            <?= $isMe ? ' <span class="badge text-bg-info" style="font-size:.65rem">You</span>' : '' ?>
          </div>
          <div class="mb-2"><?= roleBadge($m['role']) ?></div>
          <?php if ($m['instrument']): ?>
          <div class="text-muted small"><i class="bi bi-music-note me-1"></i><?= h($m['instrument']) ?></div>
          <?php endif; ?>
          <?php if ($m['year_level']): ?>
          <div class="text-muted small"><i class="bi bi-mortarboard me-1"></i><?= h($m['year_level']) ?></div>
          <?php endif; ?>
          <div class="text-muted small mt-2" style="font-size:.7rem;opacity:.6">
            <i class="bi bi-info-circle me-1"></i>Click to view details
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Member Detail Modal -->
<div class="xu-modal-overlay" id="xumodalMember">
  <div class="xu-modal" style="max-width:400px">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="modalMemberTitle">Member Info</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body">

      <!-- Avatar -->
      <div class="text-center mb-3">
        <div id="modalMemberAvatar"></div>
        <div class="fw-bold mt-2" style="font-size:1.1rem;color:var(--xu-navy)" id="modalMemberName"></div>
        <div id="modalMemberBadge" class="mt-1"></div>
        <div id="modalMemberMe" class="mt-1"></div>
      </div>

      <hr class="my-2">

      <div class="row g-2" style="font-size:.88rem">
        <div class="col-6">
          <div class="text-muted small">Instrument</div>
          <div id="modalMemberInstrument" class="fw-semibold">—</div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Year Level</div>
          <div id="modalMemberYear" class="fw-semibold">—</div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Student ID</div>
          <div id="modalMemberStudentId" class="fw-semibold">—</div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Contact Number</div>
          <div id="modalMemberContact" class="fw-semibold">—</div>
        </div>
        <div class="col-12">
          <div class="text-muted small">Penalty Points</div>
          <div id="modalMemberPenalty" class="fw-semibold">—</div>
        </div>
        <div class="col-12" id="modalMemberNotesWrap" style="display:none">
          <div class="text-muted small">Notes</div>
          <div id="modalMemberNotes" style="white-space:pre-line;font-size:.85rem"></div>
        </div>
      </div>

    </div>
    <div class="xu-modal-footer">
      <button class="btn btn-outline" onclick="closeModal(this)">Close</button>
      <a id="modalMemberContact2" href="#" class="btn btn-primary" style="display:none">
        <i class="bi bi-telephone me-1"></i>Call
      </a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Search
  const inp = document.getElementById('memberSearch');
  if (inp) {
    inp.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.member-item').forEach(function(item) {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Card hover effect
  document.querySelectorAll('.member-card-clickable').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
      this.style.boxShadow = '0 4px 16px rgba(0,0,0,.13)';
      this.style.transform = 'translateY(-3px)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.boxShadow = '';
      this.style.transform = '';
    });
  });
});

function openMemberModal(m) {
  // Avatar
  var avatarHtml = '';
  if (m.avatar_path) {
    avatarHtml = '<img src="' + m.avatar_path + '" alt="' + escHtml(m.name) + '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto">';
  } else {
    avatarHtml = '<div class="user-avatar mx-auto" style="width:80px;height:80px;font-size:1.8rem">' + escHtml(m.initials) + '</div>';
  }
  document.getElementById('modalMemberAvatar').innerHTML = avatarHtml;
  document.getElementById('modalMemberTitle').textContent = m.name;
  document.getElementById('modalMemberName').textContent  = m.name;
  document.getElementById('modalMemberBadge').innerHTML   = roleBadgeJs(m.role);
  document.getElementById('modalMemberMe').innerHTML      = m.is_me ? '<span class="badge text-bg-info" style="font-size:.7rem">You</span>' : '';

  document.getElementById('modalMemberInstrument').textContent = m.instrument  || '—';
  document.getElementById('modalMemberYear').textContent       = m.year_level  || '—';
  document.getElementById('modalMemberStudentId').textContent  = m.student_id  || '—';
  document.getElementById('modalMemberContact').textContent    = m.contact_number || '—';

  // Penalty badge
  var pts = parseInt(m.penalty_points) || 0;
  var penColor = pts === 0 ? '#16a34a' : pts < 5 ? '#d97706' : '#dc2626';
  document.getElementById('modalMemberPenalty').innerHTML =
    '<span style="color:' + penColor + ';font-weight:700">' + pts + ' pt' + (pts !== 1 ? 's' : '') + '</span>';

  // Notes
  var notesWrap = document.getElementById('modalMemberNotesWrap');
  if (m.profile_notes && m.profile_notes.trim()) {
    document.getElementById('modalMemberNotes').textContent = m.profile_notes;
    notesWrap.style.display = '';
  } else {
    notesWrap.style.display = 'none';
  }

  // Call button
  var callBtn = document.getElementById('modalMemberContact2');
  if (m.contact_number) {
    callBtn.href = 'tel:' + m.contact_number;
    callBtn.style.display = '';
  } else {
    callBtn.style.display = 'none';
  }

  openModal('xumodalMember');
}

function escHtml(str) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

function roleBadgeJs(role) {
  var map = {
    'officer': ['bg-warning text-dark', 'Officer'],
    'member':  ['bg-secondary', 'Member'],
  };
  var cfg = map[role] || ['bg-secondary', role];
  return '<span class="badge ' + cfg[0] + '">' + cfg[1] + '</span>';
}
</script>

<?php layout_foot(); ?>
