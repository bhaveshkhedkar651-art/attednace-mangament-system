
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}


function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.background = type === 'success' ? '#059669' : 
                             type === 'error' ? '#dc2626' : '#1e293b';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}


window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const toast = document.getElementById('toast');

    if (toast && toast.dataset.message) {
        showToast(toast.dataset.message, toast.dataset.type || 'info');
    }

    if (params.get('login') === 'success') showToast('Login successful!', 'success');
    if (params.get('logout') === 'success') showToast('Logged out successfully', 'success');
    if (params.get('qr') === 'generated') showToast('QR generated successfully!', 'success');

    if (params.get('success') === '1') showToast('Subject added successfully!', 'success');
    if (params.get('deleted') === '1') showToast('Subject removed successfully!', 'success');

    if (params.has('success') || params.has('deleted')) {
        params.delete('success');
        params.delete('deleted');
        const query = params.toString();
        const cleanUrl = window.location.pathname + (query ? `?${query}` : '') + window.location.hash;
        window.history.replaceState(null, '', cleanUrl);
    }

    const subjectForm = document.querySelector('form[action="save_subject.php"]');
    if (subjectForm) {
        subjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = subjectForm.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;

            try {
                const response = await fetch(subjectForm.action, {
                    method: 'POST',
                    body: new FormData(subjectForm),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                showToast(result.message || 'Unable to save the subject.', result.success ? 'success' : 'error');

                if (result.success) {
                    window.setTimeout(() => window.location.reload(), 900);
                }
            } catch (error) {
                showToast('Unable to save the subject. Please try again.', 'error');
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        });
    }
});