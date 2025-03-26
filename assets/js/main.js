// main.js

// Document ready function to ensure the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function() {
    // Smooth scrolling for anchor links in the footer
    const footerLinks = document.querySelectorAll('.main-footer a[href^="#"]');
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Example: Form validation for any forms in the footer (if applicable)
    const footerForms = document.querySelectorAll('.main-footer form');
    footerForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    isValid = false;
                    input.classList.add('invalid'); // Add a class for styling invalid inputs
                } else {
                    input.classList.remove('invalid');
                }
            });
            if (!isValid) {
                e.preventDefault(); // Prevent form submission if invalid
                alert('Please fill out all required fields correctly.');
            }
        });
    });

    // Example: Toggle mobile menu if applicable
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
});