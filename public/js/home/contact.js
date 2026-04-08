export function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) {
        return;
    }

    contactForm.addEventListener('submit', function (event) {
        event.preventDefault();
        alert('Pesan berhasil dikirim. Tim kami akan segera menghubungi Anda.');
        contactForm.reset();
    });
}
