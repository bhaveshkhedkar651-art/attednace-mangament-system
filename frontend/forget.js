document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('forgetForm');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(email)) {
            event.preventDefault();
            alert('Please enter a valid email address.');
            return;
        }

        if (password.length < 6) {
            event.preventDefault();
            alert('Password must be at least 6 characters long.');
            return;
        }

        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Passwords do not match.');
            return;
        }
    });
});
