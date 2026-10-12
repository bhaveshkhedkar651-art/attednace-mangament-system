document.addEventListener('DOMContentLoaded', function () {
    const countdownElement = document.getElementById('countdown');
    if (!countdownElement) return;

    const timerElement = countdownElement.closest('.timer');
    let expiresAt = Date.now() + Math.max(0, Number(countdownElement.dataset.time || 0)) * 1000;

    function updateCountdown() {
        const timeLeft = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        countdownElement.textContent = String(timeLeft);

        if (timeLeft === 0) {
            timerElement?.classList.add('is-expired');
            
            window.location.reload();
            return;
        }

        window.setTimeout(updateCountdown, 250);
    }

    updateCountdown();
});