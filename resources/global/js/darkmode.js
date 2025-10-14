const darkModeBtn = document.querySelector('#dark-mode-btn');
const darkModeSun = document.querySelector('#dark-mode-sun');
const darkModeMoon = document.querySelector('#dark-mode-moon');

/**
 * Initialize dark mode on page load
 * Sync html and body classes based on body's dark class (set by server)
 */
function initDarkMode() {
    const body = document.querySelector('body');
    const html = document.querySelector('html');

    // If body has dark class (from server), sync it to html
    if (body.classList.contains('dark')) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
}

/**
 * Darkmode switcher
 */
function darkmodeSwitcher() {
    // Toggle dark class on both html and body to ensure compatibility
    document.querySelector('html').classList.toggle('dark');
    document.querySelector('body').classList.toggle('dark');

    const isDark = document.querySelector('html').classList.contains('dark');

    if (isDark) {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.remove('hidden');
        darkModeMoon.classList.add('hidden');
    } else {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.add('hidden');
        darkModeMoon.classList.remove('hidden');
    }

    // Save preference to server
    fetch(darkModeBtn.dataset.url);
}

// Initialize on page load
initDarkMode();

if (darkModeBtn) {
    darkModeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        darkmodeSwitcher();
    });
}
