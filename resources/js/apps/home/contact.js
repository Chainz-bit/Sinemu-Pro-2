export function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) {
        return;
    }

    const nameInput = document.getElementById('contactName');
    const emailInput = document.getElementById('contactEmail');
    const phoneInput = document.getElementById('contactPhone');
    const messageInput = document.getElementById('contactMessage');
    const feedback = document.getElementById('contactFormFeedback');
    const fallbackLink = document.getElementById('contactWhatsappFallback');
    const submitButton = contactForm.querySelector('.contact-submit-btn');
    const whatsappNumber = '6285174386642';
    const successMessage = 'WhatsApp akan terbuka. Silakan tekan kirim untuk mengirim pesan.';

    function setFeedback(type, message) {
        if (!feedback) return;

        feedback.classList.remove('is-success', 'is-error');
        feedback.textContent = message;

        if (message) {
            feedback.classList.add(type === 'error' ? 'is-error' : 'is-success');
        }
    }

    function clearInvalidState() {
        [nameInput, emailInput, phoneInput, messageInput].forEach(function (field) {
            field?.classList.remove('is-invalid');
        });
    }

    function validateForm() {
        clearInvalidState();

        const name = (nameInput?.value || '').trim();
        const email = (emailInput?.value || '').trim();
        const phone = (phoneInput?.value || '').trim();
        const message = (messageInput?.value || '').trim();
        const phonePattern = /^(08[0-9]{8,13}|\+628[0-9]{8,13})$/;

        if (!name) {
            nameInput?.classList.add('is-invalid');
            setFeedback('error', 'Nama lengkap wajib diisi.');
            return null;
        }

        if (email && emailInput && !emailInput.checkValidity()) {
            emailInput.classList.add('is-invalid');
            setFeedback('error', 'Alamat email tidak valid.');
            return null;
        }

        if (phone && !phonePattern.test(phone)) {
            phoneInput?.classList.add('is-invalid');
            setFeedback('error', 'Nomor telepon harus memakai format 08... atau +628...');
            return null;
        }

        if (!message) {
            messageInput?.classList.add('is-invalid');
            setFeedback('error', 'Pesan wajib diisi.');
            return null;
        }

        return { name, email, phone, message };
    }

    function buildWhatsappUrl(data) {
        const text = [
            'Halo Tim SiNemu, saya ingin bertanya.',
            '',
            'Nama: ' + data.name,
            'Email: ' + (data.email || '-'),
            'Telepon: ' + (data.phone || '-'),
            'Pesan: ' + data.message,
        ].join('\n');

        return 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(text);
    }

    function openWhatsapp(url) {
        if (fallbackLink) {
            fallbackLink.href = url;
        }

        setFeedback('success', successMessage);

        const openedWindow = window.open(url, '_blank', 'noopener,noreferrer');
        if (!openedWindow) {
            window.location.href = url;
        }
    }

    contactForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const data = validateForm();
        if (!data) return;

        openWhatsapp(buildWhatsappUrl(data));
    });

    submitButton?.addEventListener('click', function () {
        submitButton.blur();
    });
}
