/* XUBand — main.js */

// ── Sidebar toggle (mobile) ──
const sidebar  = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const toggler  = document.getElementById('sidebarToggler');

function openSidebar()  { sidebar?.classList.add('show');    backdrop?.classList.add('show');    document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar?.classList.remove('show'); backdrop?.classList.remove('show'); document.body.style.overflow='';       }

toggler?.addEventListener('click', openSidebar);
backdrop?.addEventListener('click', closeSidebar);

// ── Auto-dismiss alerts ──
document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
  setTimeout(() => { el.classList.add('fade'); setTimeout(() => el.remove(), 300); }, 4000);
});

// ── Confirm on data-confirm ──
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
});

// ── Table search ──
const searchInput = document.getElementById('tableSearch');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase();
    document.querySelectorAll('[data-searchable] tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Attendance penalty preview ──
const penMap = { present: 0, late: 75, absent: 150 };
function updatePts(uid) {
  const sel = document.getElementById('sel_' + uid);
  const el  = document.getElementById('pts_' + uid);
  if (!sel || !el) return;
  const pts = penMap[sel.value] ?? 0;
  el.textContent = pts;
  el.className = 'fw-bold penalty-badge ' + (pts === 0 ? 'text-success' : pts === 75 ? 'text-warning' : 'text-danger');
}

// ── Custom modal system ──
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(el) {
  const overlay = el.closest('.modal-overlay');
  if (overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
}

// Open via data-modal="id"
document.querySelectorAll('[data-modal]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.dataset.modal));
});

// Close via data-modal-close (inside modal)
document.querySelectorAll('[data-modal-close]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn));
});

// Close by clicking overlay backdrop
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
  });
});
