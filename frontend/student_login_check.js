document.addEventListener('DOMContentLoaded', function () {
    const error = document.getElementById('login-error');
    const errorType = new URLSearchParams(window.location.search).get('error');

    if (error && ['credentials', 'invalid', 'wrong_password'].includes(errorType)) {
        error.textContent = errorType === 'wrong_password'
            ? 'Password is incorrect. Please try again.'
            : errorType === 'credentials'
                ? 'Student account was not found. Please check your email.'
                : 'Enter a valid email and password.';
        error.hidden = false;
    }

    const password = document.getElementById('password');
    const toggle = document.getElementById('password-toggle');

    if (!password || !toggle) return;

    toggle.addEventListener('click', function () {
        const showPassword = password.type === 'password';
        const label = showPassword ? 'Hide password' : 'Show password';

        password.type = showPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
        toggle.setAttribute('aria-pressed', String(showPassword));
    });
});
