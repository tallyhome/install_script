/**
 * Installation Wizard JavaScript
 */

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle language switching
    $('.language-switcher a').on('click', function(e) {
        // Already handled by href, this is just for visual feedback
        $('.language-switcher a').removeClass('active');
        $(this).addClass('active');
    });
    
    // Automatically show alerts for 5 seconds then fade out
    setTimeout(function() {
        $('.alert:not(.persistent)').fadeOut('slow');
    }, 5000);
    
    // Password strength meter
    $('#admin_password').on('input', function() {
        const password = $(this).val();
        const strengthMeter = $('#password-strength');
        
        if (!strengthMeter.length) {
            return;
        }
        
        // Simple password strength calculation
        let strength = 0;
        
        // Length check
        if (password.length >= 8) {
            strength += 1;
        }
        
        // Contains lowercase
        if (/[a-z]/.test(password)) {
            strength += 1;
        }
        
        // Contains uppercase
        if (/[A-Z]/.test(password)) {
            strength += 1;
        }
        
        // Contains number
        if (/[0-9]/.test(password)) {
            strength += 1;
        }
        
        // Contains special character
        if (/[^a-zA-Z0-9]/.test(password)) {
            strength += 1;
        }
        
        // Update strength meter
        strengthMeter.removeClass('bg-danger bg-warning bg-info bg-success');
        
        if (strength === 0) {
            strengthMeter.css('width', '0%');
        } else if (strength === 1) {
            strengthMeter.addClass('bg-danger').css('width', '20%');
        } else if (strength === 2) {
            strengthMeter.addClass('bg-warning').css('width', '40%');
        } else if (strength === 3) {
            strengthMeter.addClass('bg-info').css('width', '60%');
        } else if (strength === 4) {
            strengthMeter.addClass('bg-success').css('width', '80%');
        } else {
            strengthMeter.addClass('bg-success').css('width', '100%');
        }
    });
    
    // Confirm password validation
    $('#confirm_password').on('input', function() {
        const password = $('#admin_password').val();
        const confirmPassword = $(this).val();
        
        if (password && confirmPassword) {
            if (password !== confirmPassword) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
            }
        }
    });
    
    // Form validation before submission
    $('form').on('submit', function(e) {
        const requiredFields = $(this).find('[required]');
        let isValid = true;
        
        requiredFields.each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
});
