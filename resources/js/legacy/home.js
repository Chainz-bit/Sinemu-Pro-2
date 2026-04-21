async function bootNavbar() {
    if (!document.getElementById('mainNavBar')) return;
    const mod = await import('./home/navbar.js');
    mod.initNavbar();
}

async function bootFilterAndCounts() {
    if (!document.getElementById('filterForm')) return;
    const mod = await import('./home/filter.js');
    mod.initFilterAndCounts();
}

async function bootCarousel() {
    if (!document.querySelector('[data-carousel-target],.carousel-draggable')) return;
    const mod = await import('./home/carousel.js');
    mod.initCarousel();
}

async function bootContactForm() {
    if (!document.getElementById('contactForm')) return;
    const mod = await import('./home/contact.js');
    mod.initContactForm();
}

async function bootMap() {
    if (!document.getElementById('pickupMap')) return;
    const mod = await import('./home/map.js');
    mod.initMap();
}

async function bootSectionScroll() {
    if (!document.querySelector('#pencarian, #hilang-temuan, #klaim, #lokasi-pengambilan, #kontak')) return;
    const mod = await import('./home/section-scroll.js?v=20260416b');
    mod.initSectionScroll();
}

document.addEventListener('DOMContentLoaded', function () {
    void bootSectionScroll();
    void bootNavbar();
    void bootFilterAndCounts();
    void bootCarousel();
    void bootContactForm();
    void bootMap();
});
