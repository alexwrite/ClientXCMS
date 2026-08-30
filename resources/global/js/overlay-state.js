const triggers = document.querySelectorAll('[data-hs-overlay][aria-controls]');

triggers.forEach((trigger) => {
    const overlay = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!overlay) return;

    const sync = () => trigger.setAttribute('aria-expanded', overlay.classList.contains('open') ? 'true' : 'false');

    sync();
    new MutationObserver(sync).observe(overlay, { attributes: true, attributeFilter: ['class'] });
});
