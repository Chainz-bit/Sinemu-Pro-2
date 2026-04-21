(function () {
    const body = document.body;
    if (!body) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let navigating = false;

    body.classList.add('page-transition');

    const showPage = function () {
        navigating = false;
        body.classList.remove('page-leaving');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showPage, { once: true });
    } else {
        showPage();
    }

    window.addEventListener('pageshow', function () {
        showPage();
    });

    document.addEventListener('click', function (event) {
        const anchor = event.target.closest('a[href]');
        if (!anchor) return;

        const href = anchor.getAttribute('href') || '';
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (anchor.target === '_blank' || anchor.hasAttribute('download')) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const url = new URL(anchor.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.href === window.location.href) return;
        if (navigating) return;

        event.preventDefault();
        navigating = true;

        // Jika browser mendukung View Transition API, biarkan navigasi langsung.
        // CSS `@view-transition { navigation: auto; }` akan meng-handle animasi.
        if ('startViewTransition' in document && !prefersReducedMotion) {
            window.location.href = url.href;
            return;
        }

        // Fallback ringan untuk browser lama.
        body.classList.add('page-leaving');
        window.setTimeout(function () {
            window.location.href = url.href;
        }, 45);
    });
})();
