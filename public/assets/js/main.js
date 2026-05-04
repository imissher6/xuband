/* XUBand — Main JS */

// ── Mobile Sidebar ──
const sidebar = document.getElementById('sidebar');
const hamburger = document.getElementById('hamburger');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (hamburger) {
  hamburger.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
  });
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    sidebar?.classList.remove('open');
  });
}

// ── Modals ──
document.querySelectorAll('[data-modal]').forEach(trigger => {
  trigger.addEventListener('click', () => {
    const modalId = trigger.dataset.modal;
    openModal(modalId);
  });
});

document.querySelectorAll('.modal-close, [data-modal-close]').forEach(el => {
  el.addEventListener('click', () => closeModal(el.closest('.modal-overlay')?.id));
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}

// ── Tabs ──
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.dataset.tab;
    const container = btn.closest('[data-tabs]');
    if (!container) return;
    container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    container.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    container.querySelector('#tab-' + target)?.classList.add('active');
  });
});

// ── Auto-dismiss alerts ──
document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity .4s';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 400);
  }, 4000);
});

// ── Confirm delete ──
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', (e) => {
    const msg = el.dataset.confirm || 'Are you sure?';
    if (!confirm(msg)) e.preventDefault();
  });
});

// ── Search filter ──
const searchInput = document.getElementById('tableSearch');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase();
    document.querySelectorAll('[data-searchable] tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Attendance form: auto-compute penalty preview ──
document.querySelectorAll('select[name^="status_"]').forEach(sel => {
  sel.addEventListener('change', () => {
    const uid = sel.name.replace('status_', '');
    const pts = { absent: 3, late: 1, present: 0, excused: 0 };
    const ptsEl = document.getElementById('pts_' + uid);
    if (ptsEl) ptsEl.textContent = pts[sel.value] ?? 0;
  });
});
