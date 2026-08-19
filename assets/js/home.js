(function () {
    const el = document.getElementById('nextCountdown');
    const text = document.getElementById('countdownText');
    if (el && text) {
        const target = new Date(el.dataset.time.replace(' ', 'T')).getTime();
        const tick = function () {
            const diff = target - Date.now();
            if (diff <= 0) {
                text.textContent = t('js.soon');
                return;
            }
            const s = Math.floor(diff / 1000);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            const pad = (v) => String(v).padStart(2, '0');
            text.textContent = h > 0
                ? t('js.countdown', { h: h, m: pad(m), s: pad(sec) })
                : t('js.countdown_short', { m: pad(m), s: pad(sec) });
        };
        tick();
        setInterval(tick, 1000);
    }

    document.getElementById('copyCron')?.addEventListener('click', function () {
        const url = document.getElementById('cronUrl')?.textContent || '';
        navigator.clipboard.writeText(url).then(function () {
            toast(t('js.copied'), 'success');
        }).catch(function () {
            toast(t('js.copy_fail'), 'error');
        });
    });
})();
