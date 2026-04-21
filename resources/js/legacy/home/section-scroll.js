const SECTION_IDS = [
    'pencarian',
    'hilang-temuan',
    'klaim',
    'lokasi-pengambilan',
    'kontak'
];

const DEFAULT_SECTION_ID = 'pencarian';
const STORAGE_SECTION_KEY = 'sinemu_home_active_section';
const STORAGE_SCROLL_Y_KEY = 'sinemu_home_scroll_y';

function isValidSectionId(sectionId) {
    return SECTION_IDS.includes(sectionId);
}

function getNavbarOffset() {
    const navBar = document.getElementById('mainNavBar');
    return navBar ? navBar.offsetHeight + 16 : 16;
}

function saveScrollY() {
    try {
        sessionStorage.setItem(STORAGE_SCROLL_Y_KEY, String(Math.max(0, Math.round(window.scrollY || 0))));
    } catch (error) {
        // ignore storage failures
    }
}

function saveSectionId(sectionId) {
    if (!isValidSectionId(sectionId)) {
        return;
    }

    try {
        sessionStorage.setItem(STORAGE_SECTION_KEY, sectionId);
    } catch (error) {
        // ignore storage failures
    }
}

function getClosestSectionId() {
    const offset = getNavbarOffset() + 32;
    let activeSectionId = DEFAULT_SECTION_ID;
    let smallestDistance = Number.POSITIVE_INFINITY;

    SECTION_IDS.forEach(function (id) {
        const section = document.getElementById(id);
        if (!section) {
            return;
        }

        const distance = Math.abs(section.getBoundingClientRect().top - offset);
        if (distance < smallestDistance) {
            smallestDistance = distance;
            activeSectionId = id;
        }
    });

    return activeSectionId;
}

function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) {
        return;
    }

    const top = Math.max(0, window.scrollY + section.getBoundingClientRect().top - getNavbarOffset());
    window.scrollTo({
        top: top,
        left: 0,
        behavior: 'auto'
    });
}

function persistCurrentPosition() {
    saveScrollY();
    saveSectionId(getClosestSectionId());
}

function forceScrollTop() {
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });

    setTimeout(function () {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }, 0);

    setTimeout(function () {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }, 120);
}

export function initSectionScroll() {
    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    const hashId = window.location.hash ? window.location.hash.slice(1) : '';

    let ticking = false;
    window.addEventListener(
        'scroll',
        function () {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(function () {
                persistCurrentPosition();
                ticking = false;
            });
        },
        { passive: true }
    );

    window.addEventListener('beforeunload', persistCurrentPosition);
    window.addEventListener('pagehide', persistCurrentPosition);

    function applyInitialScroll() {
        if (isValidSectionId(hashId)) {
            scrollToSection(hashId);
            persistCurrentPosition();
            return;
        }

        try {
            sessionStorage.removeItem(STORAGE_SECTION_KEY);
            sessionStorage.removeItem(STORAGE_SCROLL_Y_KEY);
        } catch (error) {
            // ignore storage failures
        }

        forceScrollTop();
        scrollToSection(DEFAULT_SECTION_ID);
        persistCurrentPosition();
    }

    window.requestAnimationFrame(applyInitialScroll);
    window.addEventListener('load', applyInitialScroll, { once: true });
    window.addEventListener('pageshow', function () {
        if (!window.location.hash) {
            forceScrollTop();
        }
    });
}
