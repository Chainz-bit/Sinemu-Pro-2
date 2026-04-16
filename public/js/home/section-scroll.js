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

function getNavigationType() {
    const entries = window.performance && typeof window.performance.getEntriesByType === 'function'
        ? window.performance.getEntriesByType('navigation')
        : [];

    if (!entries.length) {
        return '';
    }

    return entries[0].type || '';
}

function isValidSectionId(sectionId) {
    return SECTION_IDS.includes(sectionId);
}

function getNavbarOffset() {
    const navBar = document.getElementById('mainNavBar');
    return navBar ? navBar.offsetHeight + 16 : 16;
}

function getSavedScrollY() {
    try {
        const value = sessionStorage.getItem(STORAGE_SCROLL_Y_KEY);
        if (value === null) return null;
        const parsed = Number(value);
        return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
    } catch (error) {
        return null;
    }
}

function saveScrollY() {
    try {
        sessionStorage.setItem(STORAGE_SCROLL_Y_KEY, String(Math.max(0, Math.round(window.scrollY || 0))));
    } catch (error) {
        // ignore storage failures
    }
}

function getSavedSectionId() {
    try {
        return sessionStorage.getItem(STORAGE_SECTION_KEY) || '';
    } catch (error) {
        return '';
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

function forceScrollY(top) {
    window.scrollTo({ top: top, left: 0, behavior: 'auto' });

    // Browser kadang melakukan hash-jump setelah layout stabil; paksa ulang.
    setTimeout(function () {
        window.scrollTo({ top: top, left: 0, behavior: 'auto' });
    }, 0);

    setTimeout(function () {
        window.scrollTo({ top: top, left: 0, behavior: 'auto' });
    }, 120);
}

export function initSectionScroll() {
    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    const navigationType = getNavigationType();
    const hashId = window.location.hash ? window.location.hash.slice(1) : '';
    const savedScrollY = getSavedScrollY();
    const savedSectionId = getSavedSectionId();
    const isReload = navigationType === 'reload';

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
        if (isReload && savedScrollY !== null) {
            forceScrollY(savedScrollY);
            return;
        }

        if (isReload && isValidSectionId(savedSectionId)) {
            scrollToSection(savedSectionId);
            return;
        }

        if (!isReload && isValidSectionId(hashId)) {
            scrollToSection(hashId);
            persistCurrentPosition();
            return;
        }

        scrollToSection(DEFAULT_SECTION_ID);
        persistCurrentPosition();
    }

    window.requestAnimationFrame(applyInitialScroll);
    window.addEventListener('load', applyInitialScroll, { once: true });
}
