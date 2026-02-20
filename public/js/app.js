/* ==========================================
   AC SERVIS MASJID — MAIN APP.JS
   ========================================== */

// === CSRF TOKEN ===
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// === POPUP MANAGEMENT ===
function openPopup(id) {
    document.getElementById(id)?.classList.add('active');
    document.getElementById('overlay')?.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePopup(id) {
    document.getElementById(id)?.classList.remove('active');
    const anyOpen = document.querySelectorAll('.popup.active').length > 0;
    if (!anyOpen) {
        document.getElementById('overlay')?.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeAllPopups() {
    document.querySelectorAll('.popup.active').forEach(p => p.classList.remove('active'));
    document.getElementById('overlay')?.classList.remove('active');
    document.body.style.overflow = '';
}

// === DARK MODE ===
function toggleDarkMode() {
    const html  = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
}

(function () {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
})();

// === NAVBAR TOGGLE ===
function toggleNavbar() {
    const menu = document.querySelector('.navbar-menu');
    if (menu) menu.classList.toggle('open');
}

// === FETCH HELPER ===
async function apiFetch(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':  csrfToken,
            'Accept':        'application/json',
        }
    };
    if (data) options.body = JSON.stringify(data);

    const res  = await fetch(url, options);
    const json = await res.json();

    if (!res.ok) {
        // Attach status HTTP dan full JSON response ke error object
        const err  = new Error(json.message || 'Terjadi kesalahan.');
        err.status = res.status;
        err.data   = json;          // berisi has_existing, existing_order, dll
        throw err;
    }

    return json;
}

// === TOAST NOTIFICATION ===
function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;

    const bg = type === 'success' ? '#16a34a'
             : type === 'error'   ? '#dc2626'
             : type === 'info'    ? '#2563eb'
             : '#374151';

    toast.style.cssText =
        'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;' +
        'background:' + bg + ';color:#fff;padding:0.75rem 1.25rem;border-radius:8px;' +
        'box-shadow:0 4px 12px rgba(0,0,0,0.2);font-size:0.875rem;' +
        'display:flex;align-items:center;gap:0.5rem;' +
        'animation:slideIn 0.3s ease;max-width:360px;line-height:1.4;';

    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = icon + ' ' + message;
    document.body.appendChild(toast);

    setTimeout(function() {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

// Animasi toast
const _toastStyle = document.createElement('style');
_toastStyle.textContent =
    '@keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }' +
    '@keyframes slideOut { to { transform: translateX(100px); opacity: 0; } }';
document.head.appendChild(_toastStyle);

// === ESCAPE KEY ===
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllPopups();
});
