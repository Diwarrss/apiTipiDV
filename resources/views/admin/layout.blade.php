<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — TipiDV</title>
    <link rel="icon" href="{{ asset('images/tipidv-logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
    @hasSection('nav')
        <header class="admin-header">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <img src="{{ asset('images/tipidv-logo.png') }}" alt="TipiDV" class="admin-brand-logo" width="36" height="36">
                <span class="admin-brand-text">Tipi<span>DV</span> <span class="admin-brand-sub">· Admin</span></span>
            </a>
            <div class="admin-nav">
                @yield('nav')
                <a href="{{ route('site.home') }}" class="admin-nav-link" target="_blank" rel="noopener">Ver sitio</a>
                <form action="{{ route('admin.logout') }}" method="post" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn--muted btn--sm">Salir</button>
                </form>
            </div>
        </header>
    @endif

    <main class="@hasSection('nav') admin-wrap @else login-page @endif">
        @hasSection('nav')
            @yield('content')
        @else
            <div class="login-card">
                @yield('content')
            </div>
        @endif
    </main>

    <div class="toast-stack" id="toast-stack" aria-live="polite"></div>

    <div class="modal-backdrop" id="confirm-modal" hidden role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="modal">
            <div class="modal-icon" aria-hidden="true">!</div>
            <h3 id="confirm-title">¿Confirmar acción?</h3>
            <p id="confirm-message">Esta acción no se puede deshacer fácilmente.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn--ghost" id="confirm-cancel">Cancelar</button>
                <button type="button" class="btn btn--danger" id="confirm-ok">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const stack = document.getElementById('toast-stack');

        function showToast(message, type) {
            if (!message || !stack) return;
            const toast = document.createElement('div');
            toast.className = 'toast toast--' + (type || 'success');
            toast.setAttribute('role', 'alert');
            toast.innerHTML =
                '<span class="toast-icon">' + (type === 'error' ? '✕' : '✓') + '</span>' +
                '<span class="toast-body"></span>' +
                '<button type="button" class="toast-close" aria-label="Cerrar">×</button>';
            toast.querySelector('.toast-body').textContent = message;
            stack.appendChild(toast);

            const close = () => {
                toast.classList.add('is-leaving');
                setTimeout(() => toast.remove(), 260);
            };
            toast.querySelector('.toast-close').addEventListener('click', close);
            setTimeout(close, 5500);
        }

        @if (session('status'))
            showToast(@json(session('status')), 'success');
        @endif
        @if (session('error'))
            showToast(@json(session('error')), 'error');
        @endif
        @if ($errors->any() && !request()->routeIs('admin.login'))
            showToast(@json($errors->first()), 'error');
        @endif

        const modal = document.getElementById('confirm-modal');
        const confirmMsg = document.getElementById('confirm-message');
        const confirmOk = document.getElementById('confirm-ok');
        const confirmCancel = document.getElementById('confirm-cancel');
        let pendingForm = null;

        function openConfirm(message, form) {
            pendingForm = form;
            confirmMsg.textContent = message;
            modal.hidden = false;
            confirmOk.focus();
        }
        function closeConfirm() {
            modal.hidden = true;
            pendingForm = null;
        }

        confirmCancel.addEventListener('click', closeConfirm);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeConfirm(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) closeConfirm();
        });
        confirmOk.addEventListener('click', () => {
            if (pendingForm) pendingForm.submit();
            closeConfirm();
        });

        document.querySelectorAll('[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                openConfirm(form.dataset.confirm || '¿Confirmar?', form);
            });
        });

        document.querySelectorAll('[data-copy]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const text = btn.dataset.copy || '';
                navigator.clipboard.writeText(text).then(() => {
                    const prev = btn.textContent;
                    btn.textContent = 'Copiado';
                    setTimeout(() => { btn.textContent = prev; }, 1500);
                    showToast('Clave copiada al portapapeles', 'success');
                }).catch(() => showToast('No se pudo copiar', 'error'));
            });
        });
    })();
    </script>
</body>
</html>
