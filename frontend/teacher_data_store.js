document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const toggle = document.getElementById('password-toggle');

    if (!password || !toggle) return;

    toggle.addEventListener('click', function () {
        const showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        const label = showPassword ? 'Hide password' : 'Show password';
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
        toggle.setAttribute('aria-pressed', String(showPassword));
    });
});
