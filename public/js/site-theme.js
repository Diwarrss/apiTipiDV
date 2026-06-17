(function () {
    var KEY = 'tipidv-theme';

    function preferred() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }

    function current() {
        return document.documentElement.getAttribute('data-theme') || preferred();
    }

    function init() {
        var saved = localStorage.getItem(KEY);
        apply(saved === 'dark' || saved === 'light' ? saved : preferred());
    }

    function toggle() {
        var next = current() === 'dark' ? 'light' : 'dark';
        localStorage.setItem(KEY, next);
        apply(next);
    }

    init();

    function bind() {
        var btn = document.getElementById('theme-toggle');
        if (btn) btn.addEventListener('click', toggle);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }

    window.TipiDVTheme = { toggle: toggle };
})();
