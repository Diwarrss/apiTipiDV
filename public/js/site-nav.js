(function () {
    function init() {
        var header = document.querySelector('.site-header');
        if (!header) {
            return;
        }

        var toggle = header.querySelector('.nav-toggle');
        var nav = header.querySelector('#site-nav');
        if (!toggle || !nav) {
            return;
        }

        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute(
                'aria-label',
                open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'
            );
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            setOpen(!nav.classList.contains('is-open'));
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setOpen(false);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                setOpen(false);
            }
        });

        document.addEventListener('click', function (e) {
            if (!header.contains(e.target)) {
                setOpen(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
