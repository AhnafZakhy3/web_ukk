/**
 * NusaPOS - General Client Utilities
 */

// Format Rupiah
function formatRupiah(number) {
  return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
}

// Toast Notification
function showToast(message, type = 'success') {
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bgClass = type === 'success' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant border-tertiary/20' :
                  type === 'error' ? 'bg-error-container text-on-error-container border-error/20' :
                  'bg-surface-container-highest text-on-surface border-outline-variant';
  const icon = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';

  toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg transition-all duration-300 transform translate-y-2 opacity-0 font-label-md text-label-md ${bgClass}`;
  toast.innerHTML = `
    <span class="material-symbols-outlined text-[20px]">${icon}</span>
    <span class="flex-1">${message}</span>
  `;

  toastContainer.appendChild(toast);

  // Animate in
  setTimeout(() => {
    toast.classList.remove('translate-y-2', 'opacity-0');
  }, 10);

  // Auto remove
  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-x-4');
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// Global modal helpers
function openModal(modalId) {
  const el = document.getElementById(modalId);
  if (el) {
    el.classList.remove('hidden');
    el.classList.add('flex');
  }
}

function closeModal(modalId) {
  const el = document.getElementById(modalId);
  if (el) {
    el.classList.add('hidden');
    el.classList.remove('flex');
  }
}

// Mobile sidebar toggle
function toggleSidebar(open) {
  const sidebar = document.getElementById('app-sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  if (!sidebar) return;
  if (open) {
    sidebar.classList.remove('-translate-x-full');
    if (backdrop) backdrop.classList.remove('hidden');
  } else {
    sidebar.classList.add('-translate-x-full');
    if (backdrop) backdrop.classList.add('hidden');
  }
}

// Theme Toggle (Dark & Light Mode)
function toggleTheme() {
  const isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('nusapos_theme', isDark ? 'dark' : 'light');
  updateThemeIcons();
  showToast(isDark ? 'Mode gelap diaktifkan' : 'Mode terang diaktifkan', 'info');
}

function updateThemeIcons() {
  const isDark = document.documentElement.classList.contains('dark');
  const icons = document.querySelectorAll('.theme-toggle-icon');
  icons.forEach(icon => {
    icon.textContent = isDark ? 'light_mode' : 'dark_mode';
  });
  const textLabels = document.querySelectorAll('.theme-toggle-text');
  textLabels.forEach(label => {
    label.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
  });
  const statusLabels = document.querySelectorAll('.theme-status-text');
  statusLabels.forEach(status => {
    status.textContent = isDark ? 'Gelap' : 'Terang';
  });
}

document.addEventListener('DOMContentLoaded', updateThemeIcons);
