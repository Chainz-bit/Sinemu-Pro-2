(function () {
    const body = document.body;
    if (!body) return;

    body.classList.add('page-transition');

    const showPage = function () {
        requestAnimationFrame(function () {
            body.classList.add('page-ready');
            body.classList.remove('page-leaving');
        });
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

        event.preventDefault();
        body.classList.add('page-leaving');

        window.setTimeout(function () {
            window.location.href = url.href;
        }, 220);
    });
})();
