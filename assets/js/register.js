document.addEventListener("DOMContentLoaded", function() {
    const togglePasswords = document.querySelectorAll('.toggle-password');

    togglePasswords.forEach(function(icon) {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.querySelector(targetId);
            
            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                this.classList.toggle('bi-eye-slash');
                this.classList.toggle('bi-eye');
            }
        });
    });
});