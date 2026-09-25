/* Self-contained presentation interactions; shipment data is explicitly simulated. */
(() => {
    'use strict';
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    window.showToast = (message, tone = 'error') => {
        const region = document.getElementById('toast-region');
        const toast = document.createElement('div');
        toast.className = 'wasla-toast pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-xl text-sm';
        toast.dataset.tone = ['error', 'success', 'info'].includes(tone) ? tone : 'info';
        toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-atomic', 'true');
        const icon = document.createElement('span');
        icon.className = 'material-symbols-outlined shrink-0 ' + (tone === 'error' ? 'text-rose-400' : tone === 'success' ? 'text-emerald-400' : 'text-sky-400');
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = tone === 'error' ? 'error' : tone === 'success' ? 'check_circle' : 'info';
        const copy = document.createElement('p');
        copy.className = 'flex-1 min-w-0 break-words leading-relaxed';
        copy.textContent = String(message);
        const close = document.createElement('button');
        close.className = 'shrink-0 rounded-lg p-2 -m-1';
        close.type = 'button';
        close.setAttribute('aria-label', 'Fermer la notification');
        close.textContent = '×';
        let timer;
        let leaving = false;
        const dismiss = () => {
            if (leaving) return;
            leaving = true;
            clearTimeout(timer);
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), reducedMotion.matches ? 0 : 180);
        };
        const schedule = () => { clearTimeout(timer); timer = setTimeout(dismiss, 8000); };
        close.addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', () => clearTimeout(timer));
        toast.addEventListener('mouseleave', schedule);
        toast.addEventListener('focusin', () => clearTimeout(timer));
        toast.addEventListener('focusout', schedule);
        toast.append(icon, copy, close);
        region.append(toast);
        schedule();
    };

    const themeButton = document.getElementById('mapStyleBtn');
    function syncThemeButton() {
        const dark = document.documentElement.classList.contains('dark');
        themeButton.innerHTML = '<span class="material-symbols-outlined text-sm" aria-hidden="true">' + (dark ? 'light_mode' : 'dark_mode') + '</span><span>' + (dark ? 'Mode clair' : 'Mode sombre') + '</span>';
        themeButton.setAttribute('aria-label', dark ? 'Activer le mode clair' : 'Activer le mode sombre');
        themeButton.title = 'Changer le thème de toute l’interface';
    }
    themeButton.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('wasla-theme', dark ? 'dark' : 'light'); } catch (_) { /* Theme still works when storage is unavailable. */ }
        syncThemeButton();
    });
    syncThemeButton();

    const dialog = document.getElementById('tracking-dialog');
    const open = document.getElementById('tracking-open');
    const pause = document.getElementById('tracking-pause');
    const route = document.getElementById('tracking-route-progress');
    const routeLength = route.getTotalLength();
    const byId = id => document.getElementById('tracking-' + id);
    const duration = 150000;
    let progress = .38;
    let paused = false;
    let frame = 0;
    let previous = 0;
    let lastReadout = 0;
    let oldOverflow = '';
    function render(now, force = false) {
        const point = route.getPointAtLength(routeLength * progress);
        byId('truck').setAttribute('transform', `translate(${point.x} ${point.y})`);
        route.style.strokeDashoffset = 100 * (1 - progress);
        byId('fill').style.transform = `scaleX(${progress})`;
        if (!force && now - lastReadout < 1000) return;
        lastReadout = now;
        const remaining = 65 * (1 - progress);
        const minutes = Math.ceil(remaining / 64 * 60);
        const percent = Math.floor(progress * 100);
        byId('percent').textContent = `${percent} %`;
        byId('progress').setAttribute('aria-valuenow', percent);
        byId('eta').textContent = minutes ? `${minutes} min` : 'Arrivé';
        byId('distance').textContent = `${remaining.toFixed(1)} km`;
        byId('speed').textContent = progress >= 1 ? '0 km/h' : `${64 + Math.round(Math.sin(progress * 80) * 3)} km/h`;
        byId('coordinates').textContent = `${(30.00 + progress * .42).toFixed(5)}° N / ${(9.65 - progress * .05).toFixed(5)}° W`;
        byId('updated').textContent = `Dernier signal simulé · ${new Date().toLocaleTimeString('fr-FR')}`;
        if (progress >= 1) {
            byId('status').textContent = 'Livré · Simulation';
            byId('destination').textContent = 'Livraison simulée terminée';
            pause.disabled = true;
            pause.textContent = 'Trajet terminé';
        }
    }
    function tick(now) {
        if (!dialog.open || dialog.classList.contains('is-closing')) return;
        if (!paused && !document.hidden) {
            progress = Math.min(1, progress + Math.min(now - previous, 100) / duration);
            // Reduced motion retains telemetry without continuously moving the truck.
            if (!reducedMotion.matches || now - lastReadout >= 1000 || progress === 1) render(now, progress === 1);
        }
        previous = now;
        if (progress < 1) frame = requestAnimationFrame(tick);
    }
    open.addEventListener('click', () => {
        if (dialog.open) return;
        oldOverflow = document.body.style.overflow;
        document.body.style.setProperty('overflow', 'hidden', 'important');
        dialog.showModal();
        byId('close').focus();
        previous = performance.now();
        render(previous, true);
        frame = requestAnimationFrame(tick);
    });
    function closeDialog() {
        if (dialog.classList.contains('is-closing')) return;
        cancelAnimationFrame(frame);
        dialog.classList.add('is-closing');
        setTimeout(() => { dialog.close(); }, reducedMotion.matches ? 0 : 200);
    }
    dialog.addEventListener('close', () => {
        cancelAnimationFrame(frame);
        dialog.classList.remove('is-closing');
        document.body.style.overflow = oldOverflow;
        open.focus();
    });
    byId('close').addEventListener('click', closeDialog);
    dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialog(); });
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) closeDialog();
    });
    pause.addEventListener('click', () => {
        paused = !paused;
        pause.textContent = paused ? 'Reprendre' : 'Mettre en pause';
        pause.setAttribute('aria-pressed', String(paused));
        byId('status').textContent = paused ? 'Simulation en pause' : 'En transit';
    });
    byId('restart').addEventListener('click', () => {
        cancelAnimationFrame(frame);
        progress = .38;
        paused = false;
        pause.disabled = false;
        pause.textContent = 'Mettre en pause';
        pause.setAttribute('aria-pressed', 'false');
        byId('status').textContent = 'En transit';
        byId('destination').textContent = 'Réception planifiée';
        previous = performance.now();
        render(previous, true);
        frame = requestAnimationFrame(tick);
    });
    document.addEventListener('visibilitychange', () => { previous = performance.now(); });
    // Leaflet must recalculate after the workspace stacks or changes height.
    new ResizeObserver(() => {
        if (typeof map !== 'undefined' && typeof map?.invalidateSize === 'function') map.invalidateSize({ pan:false });
    }).observe(document.getElementById('map'));
})();
