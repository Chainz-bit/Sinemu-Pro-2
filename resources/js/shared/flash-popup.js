(function () {
    function getContainer() {
        var existing = document.getElementById('sinemu-toast-container');
        if (existing) {
            return existing;
        }

        var container = document.createElement('div');
        container.id = 'sinemu-toast-container';
        container.className = 'sinemu-toast-container';
        document.body.appendChild(container);
        return container;
    }

    function resolveType(node) {
        var classList = node.classList;
        if (
            classList.contains('error') ||
            classList.contains('alert-error') ||
            classList.contains('alert-danger')
        ) {
            return 'error';
        }

        if (classList.contains('warning') || classList.contains('alert-warning')) {
            return 'warning';
        }

        if (classList.contains('info') || classList.contains('alert-info')) {
            return 'info';
        }

        return 'success';
    }

    function resolveTitle(type) {
        if (type === 'error') return 'Gagal';
        if (type === 'warning') return 'Perhatian';
        if (type === 'info') return 'Informasi';
        return 'Berhasil';
    }

    function resolveIcon(type) {
        if (type === 'error') return '!';
        if (type === 'warning') return '!';
        if (type === 'info') return 'i';
        return '✓';
    }

    function extractMessage(node) {
        var bodyMessage = node.querySelector('.feedback-alert-body span');
        if (bodyMessage && bodyMessage.textContent.trim() !== '') {
            return bodyMessage.textContent.trim();
        }

        return (node.textContent || '').trim().replace(/\s+/g, ' ');
    }

    function closeToast(toast) {
        if (!toast || toast.dataset.closing === '1') {
            return;
        }

        toast.dataset.closing = '1';
        toast.classList.add('is-hiding');
        window.setTimeout(function () {
            toast.remove();
        }, 260);
    }

    function pushToast(node) {
        if (!node || node.dataset.toastHandled === '1') {
            return;
        }

        node.dataset.toastHandled = '1';

        var message = extractMessage(node);
        if (message === '') {
            node.remove();
            return;
        }

        var type = resolveType(node);
        var toast = document.createElement('div');
        toast.className = 'sinemu-toast sinemu-toast--' + type;

        var icon = document.createElement('span');
        icon.className = 'sinemu-toast__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = resolveIcon(type);

        var body = document.createElement('div');
        body.className = 'sinemu-toast__body';

        var title = document.createElement('strong');
        title.className = 'sinemu-toast__title';
        title.textContent = resolveTitle(type);

        var text = document.createElement('span');
        text.className = 'sinemu-toast__message';
        text.textContent = message;

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sinemu-toast__close';
        closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () {
            closeToast(toast);
        });

        body.appendChild(title);
        body.appendChild(text);
        toast.appendChild(icon);
        toast.appendChild(body);
        toast.appendChild(closeBtn);
        getContainer().appendChild(toast);

        var delay = type === 'error' ? 3800 : 3200;
        window.setTimeout(function () {
            closeToast(toast);
        }, delay);

        node.remove();
    }

    function normalizeAlerts() {
        var selectors = [
            '.feedback-alert',
            '.alert-box',
            '.alert.alert-success',
            '.alert.alert-danger',
            '.alert.alert-warning',
            '.alert.alert-info',
        ];

        var nodes = document.querySelectorAll(selectors.join(','));
        nodes.forEach(function (node) {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            if (node.closest('#sinemu-toast-container')) {
                return;
            }

            pushToast(node);
        });
    }

    function normalizeServerFlashMessages() {
        var messages = Array.isArray(window.__SINEMU_FLASH_MESSAGES)
            ? window.__SINEMU_FLASH_MESSAGES
            : [];

        messages.forEach(function (item) {
            if (!item || typeof item !== 'object') {
                return;
            }

            var messageText = typeof item.message === 'string' ? item.message.trim() : '';
            if (messageText === '') {
                return;
            }

            var holder = document.createElement('div');
            holder.className = 'feedback-alert ' + (item.type === 'error' ? 'error' : 'success');
            holder.textContent = messageText;
            pushToast(holder);
        });

        window.__SINEMU_FLASH_MESSAGES = [];
    }

    function boot() {
        normalizeServerFlashMessages();
        normalizeAlerts();

        var observer = new MutationObserver(function () {
            normalizeAlerts();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
