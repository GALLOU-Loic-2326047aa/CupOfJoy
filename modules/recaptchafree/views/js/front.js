document.addEventListener('DOMContentLoaded', function() {
    // Selectionne le Captcha et le place vers le bas du container
    const loginForm = document.getElementById('login-form');
    const captchaContainer = document.getElementById('recaptcha-container-for-login');

    if (loginForm && captchaContainer) {
        const formFooter = loginForm.querySelector('.form-footer');
        if (formFooter) {
            // Deplace le captcha vers le bas
            formFooter.before(captchaContainer);
        }
    }
});