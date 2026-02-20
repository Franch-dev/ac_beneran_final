/* ==========================================
   AC SERVIS MASJID — MAIN APP.JS
   ========================================== */

// === CSRF TOKEN SETUP ===
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// === POPUP MANAGEMENT ===
function openPopup(id) {
    document.getElementById(id)?.classList.add('active');
    document.getElementById('overlay')?.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePopup(id) {
    document.getElementById(id)?.classList.remove('active');
    // Only close overlay if no other popups are open
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
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
}

// Apply saved theme
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
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        }
    };
    if (data) options.body = JSON.stringify(data);

    const res = await fetch(url, options);
    const json = await res.json();

    if (!res.ok) {
        throw new Error(json.message || 'Terjadi kesalahan.');
    }

    return json;
}

// Form data helper (for FormData with CSRF)
async function apiFetchForm(url, method = 'POST', formData) {
    const data = {};
    formData.forEach((val, key) => {
        if (key.endsWith('[]')) {
            const k = key.slice(0, -2);
            if (!data[k]) data[k] = [];
            data[k].push(val);
        } else {
            data[key] = val;
        }
    });
    return apiFetch(url, method, data);
}

// === TOAST NOTIFICATION ===
function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
        background: ${type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#2563eb'};
        color: #fff; padding: 0.75rem 1.25rem; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2); font-size: 0.9rem;
        display: flex; align-items: center; gap: 0.5rem;
        animation: slideIn 0.3s ease;
        max-width: 350px;
    `;

    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `${icon} ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Add toast animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { to { transform: translateX(100px); opacity: 0; } }
`;
document.head.appendChild(style);

// === ESCAPE KEY CLOSES POPUPS ===
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllPopups();
});
