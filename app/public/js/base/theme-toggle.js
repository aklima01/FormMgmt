document.addEventListener('DOMContentLoaded', function () {
    // Theme toggle logic
    const body = document.body;
    const nav = document.getElementById('main-navbar');
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const tables = document.querySelectorAll('table.table'); // all Bootstrap tables

    function applyTheme(theme) {
        if (theme === 'dark') {
            body.classList.remove('bg-light', 'text-dark');
            body.classList.add('bg-dark', 'text-light');

            nav.classList.remove('navbar-light', 'bg-primary');
            nav.classList.add('navbar-dark', 'bg-dark');

            themeIcon.classList.remove('bi-moon');
            themeIcon.classList.add('bi-sun');

            // Add dark mode class to tables
            tables.forEach(table => {
                table.classList.add('table-dark');
                table.classList.remove('table-light'); // optional, in case you used table-light
            });
        } else {
            body.classList.remove('bg-dark', 'text-light');
            body.classList.add('bg-light', 'text-dark');

            nav.classList.remove('navbar-dark', 'bg-dark');
            nav.classList.add('navbar-light', 'bg-primary');

            themeIcon.classList.remove('bi-sun');
            themeIcon.classList.add('bi-moon');

            // Remove dark mode class from tables
            tables.forEach(table => {
                table.classList.remove('table-dark');
                table.classList.add('table-light'); // optional, to ensure light style
            });
        }
        localStorage.setItem('theme', theme);
    }

    // Toggle theme on button click
    themeToggleBtn.addEventListener('click', function () {
        const current = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
        applyTheme(current);
    });

    // Apply saved preference
    applyTheme(localStorage.getItem('theme') || 'light');
});
