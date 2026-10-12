document.addEventListener('DOMContentLoaded', function () {
    const status = document.getElementById('scanner-status');
    const startButton = document.getElementById('start-camera');
    let scannerRunning = false;
    let handlingScan = false;

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.classList.toggle('error', isError);
    }

    if (typeof Html5Qrcode === 'undefined') {
        startButton.disabled = true;
        setStatus('QR scanner could not load. Check your internet connection and reload.', true);
        return;
    }

    const scanner = new Html5Qrcode('qr-reader');

    async function getCameraDiagnostics() {
        let permission = 'unknown';
        let cameraCount = null;

        if (navigator.permissions?.query) {
            try {
                permission = (await navigator.permissions.query({ name: 'camera' })).state;
            } catch {
                permission = 'unsupported';
            }
        } else {
            permission = 'unsupported';
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            cameraCount = devices.filter(function (device) {
                return device.kind === 'videoinput';
            }).length;
        } catch {
            cameraCount = null;
        }

        return { permission, cameraCount };
    }

    function getCameraErrorMessage(error, diagnostics) {
        const errorName = error?.name || '';
        const errorText = typeof error === 'string' ? error : (error?.message || String(error || ''));
        const errorDetails = `${errorName} ${errorText}`;

        if (diagnostics.permission === 'denied' || /NotAllowedError|PermissionDeniedError/i.test(errorDetails)) {
            return 'Camera permission is denied for this site. Allow Camera in Chrome site settings and Android app permissions, then reload.';
        }
        if (/NotFoundError|DevicesNotFoundError/i.test(errorDetails)
            || (diagnostics.permission === 'granted' && diagnostics.cameraCount === 0)) {
            return 'No camera is available to this browser. Check that the device has a camera and Chrome can access it.';
        }
        if (/NotReadableError|TrackStartError|AbortError/i.test(errorDetails)) {
            return 'The camera could not be opened. Close other apps using it and check Android camera access for Chrome.';
        }
        if (/OverconstrainedError|ConstraintNotSatisfiedError/i.test(errorDetails)) {
            return 'The preferred camera could not be selected. This device may not expose a compatible camera.';
        }

        const cameraCount = diagnostics.cameraCount === null ? 'unknown' : diagnostics.cameraCount;
        const reason = errorText || 'No browser error details were provided.';
        return `Camera startup failed (${errorName || 'unknown error'}): ${reason} Permission: ${diagnostics.permission}; cameras detected: ${cameraCount}.`;
    }

    async function stopScanner() {
        if (!scannerRunning) return;
        await scanner.stop();
        scannerRunning = false;
    }

    async function handleScan(decodedText) {
        if (handlingScan) return;

        let token;
        try {
            token = new URL(decodedText).searchParams.get('token');
        } catch {
            setStatus('This QR code is not a valid attendance code.', true);
            return;
        }

        if (!token || !/^[a-f0-9]{32}$/i.test(token)) {
            setStatus('This QR code is not a valid attendance code.', true);
            return;
        }

        handlingScan = true;
        setStatus('QR code recognized. Opening attendance...');
        try {
            await stopScanner();
        } catch {
        }
        window.location.assign(`scan.php?token=${encodeURIComponent(token)}`);
    }

    startButton.addEventListener('click', async function () {
        const hasGetUserMedia = typeof navigator.mediaDevices?.getUserMedia === 'function';
        console.info('[student-scan] camera environment', {
            origin: window.location.origin,
            protocol: window.location.protocol,
            isSecureContext: window.isSecureContext,
            hasMediaDevices: Boolean(navigator.mediaDevices),
            hasGetUserMedia
        });

        if (!window.isSecureContext) {
            setStatus('Unable to start camera in this browser context.', true);
            return;
        }
        if (!hasGetUserMedia) {
            setStatus('This browser does not provide camera access. Update Chrome or use a supported browser.', true);
            return;
        }

        startButton.disabled = true;
        setStatus('Starting camera...');
        try {
            const scanConfig = { fps: 10, qrbox: { width: 240, height: 240 } };
            const onScanError = function () {};
            try {
                await scanner.start(
                    { facingMode: { ideal: 'environment' } },
                    scanConfig,
                    handleScan,
                    onScanError
                );
            } catch (error) {
                const errorDetails = `${error?.name || ''} ${typeof error === 'string' ? error : error?.message || ''}`;
                if (!/OverconstrainedError|ConstraintNotSatisfiedError/i.test(errorDetails)) throw error;

                console.warn('[student-scan] rear-camera preference unavailable; retrying with browser default camera', error);
                await scanner.start({}, scanConfig, handleScan, onScanError);
            }
            scannerRunning = true;
            startButton.disabled = true;
            setStatus('Camera is on. Point it at your teacher\'s QR code.');
        } catch (error) {
            startButton.disabled = false;
            const diagnostics = await getCameraDiagnostics();
            console.error('[student-scan] camera startup failed', {
                error,
                isSecureContext: window.isSecureContext,
                permission: diagnostics.permission,
                cameraCount: diagnostics.cameraCount
            });
            setStatus(getCameraErrorMessage(error, diagnostics), true);
        }
    });

    window.addEventListener('pagehide', function () {
        if (!scannerRunning) return;
        scannerRunning = false;
        scanner.stop().catch(function (error) {
            console.warn('[student-scan] camera cleanup failed', error);
        });
    });
});