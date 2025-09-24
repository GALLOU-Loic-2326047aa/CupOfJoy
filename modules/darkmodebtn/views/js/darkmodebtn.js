document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('darkmode-toggle');
    if (!btn) return;

    function setDarkMode(enabled) {
        document.body.classList.toggle('darkmode', enabled);
        document.getElementById('wrapper')?.classList.toggle('darkmode', enabled);

        btn.textContent = enabled ? '🌙' : '🌞';
        localStorage.setItem('theme', enabled ? 'dark' : 'light');
    }

    // Initialisation
    const isDark = localStorage.getItem('theme') === 'dark';
    setDarkMode(isDark);

    // Toggle au clic
    btn.addEventListener('click', () => {
        setDarkMode(!document.body.classList.contains('darkmode'));
    });
});
