(function () {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');
    const googleButton = document.getElementById('googleLoginBtn');

    if (toggleButton && passwordInput) {
        toggleButton.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleButton.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    }

    if (googleButton) {
        googleButton.addEventListener('click', function () {
            window.alert('Login Google belum tersedia, fitur ini masih dalam pengembangan.');
        });
    }
})();
