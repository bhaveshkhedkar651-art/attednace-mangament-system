function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('open');
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.background = type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#0f172a';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
}

window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('login') === 'success') showToast('Login successful!', 'success');
    if (params.get('logout') === 'success') showToast('Logged out successfully.', 'success');
    if (params.get('success') === '1') showToast('Password updated successfully!', 'success');
    if (params.get('profile') === 'success') showToast('Profile photo updated.', 'success');
    if (params.get('attendance') === 'already_marked') showToast('Attendance was already marked for this subject today.');

    const cleanKeys = ['login', 'logout', 'success', 'profile', 'attendance'];
    for (const key of cleanKeys) {
        if (params.has(key)) {
            params.delete(key);
        }
    }

    const cleanUrl = window.location.pathname + (params.toString() ? `?${params.toString()}` : '');
    if (window.location.search !== '') {
        window.history.replaceState(null, '', cleanUrl);
    }
});
