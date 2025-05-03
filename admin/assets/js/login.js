// Initialize AOS
AOS.init({
    duration: 1000,
    once: true
});

// Form submission handling
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const loading = document.querySelector('.loading');
    loading.classList.add('active');
});

// Input focus effects
const inputs = document.querySelectorAll('.form-control');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    input.addEventListener('blur', function() {
        if (!this.value) {
            this.parentElement.classList.remove('focused');
        }
    });
}); 