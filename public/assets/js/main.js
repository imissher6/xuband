/* XUBand — main.js */

document.addEventListener('DOMContentLoaded', function () {

  // ── Sidebar toggle (mobile) ──
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const toggler  = document.getElementById('sidebarToggler');

  function openSidebar()  { sidebar?.classList.add('show');    backdrop?.classList.add('show');    document.body.style.overflow = 'hidden'; }
  function closeSidebar() { sidebar?.classList.remove('show'); backdrop?.classList.remove('show'); document.body.style.overflow = ''; }

  toggler?.addEventListener('click', openSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  // ── Auto-dismiss alerts ──
  document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 4000);
  });

  // ── data-confirm (for non-logout delete actions) ──
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
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
  window.updatePts = function (uid) {
    const sel = document.getElementById('sel_' + uid);
    const el  = document.getElementById('pts_' + uid);
    if (!sel || !el) return;
    const pts = penMap[sel.value] ?? 0;
    el.textContent = pts;
    el.className = 'fw-bold penalty-badge ' + (pts === 0 ? 'text-success' : pts === 75 ? 'text-warning' : 'text-danger');
  };

  // ── Custom modal system ──
  window.openModal = function (id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
  };

  window.closeModal = function (el) {
    const overlay = el.closest('.xu-modal-overlay');
    if (overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
  };

  // Open via data-modal="id"
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modal));
  });

  // Close via data-modal-close
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn));
  });

  // Close by clicking overlay backdrop
  document.querySelectorAll('.xu-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
    });
  });

  // ── ESC to close modals (fixed: targets .xu-modal-overlay not .modal-overlay) ──
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.xu-modal-overlay.open').forEach(overlay => {
        overlay.classList.remove('open'); document.body.style.overflow = '';
      });
    }
  });

});
