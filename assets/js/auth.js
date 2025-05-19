/**
 * Authentication JavaScript
 * Client-side functionality for the authentication system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const passwordFields = document.querySelectorAll('input[type="password"]');
    
    passwordFields.forEach(function(field) {
        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '👁️';
        toggleButton.title = 'Show password';
        toggleButton.style.position = 'absolute';
        toggleButton.style.right = '10px';
        toggleButton.style.top = '50%';
        toggleButton.style.transform = 'translateY(-50%)';
        toggleButton.style.border = 'none';
        toggleButton.style.background = 'transparent';
        toggleButton.style.cursor = 'pointer';
        
        // Create wrapper for field
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        
        // Insert the field into the wrapper
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        wrapper.appendChild(toggleButton);
        
        // Add event listener to toggle button
        toggleButton.addEventListener('click', function() {
            if (field.type === 'password') {
                field.type = 'text';
                toggleButton.innerHTML = '✕';
                toggleButton.title = 'Hide password';
            } else {
                field.type = 'password';
                toggleButton.innerHTML = '👁️';
                toggleButton.title = 'Show password';
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        const passwordField = form.querySelector('input[name="password"]');
        const confirmField = form.querySelector('input[name="confirm_password"]');
        
        if (passwordField && confirmField) {
            // Check password and confirm password match
            confirmField.addEventListener('input', function() {
                if (passwordField.value !== confirmField.value) {
                    confirmField.setCustomValidity('Passwords do not match');
                } else {
                    confirmField.setCustomValidity('');
                }
            });
            
            passwordField.addEventListener('input', function() {
                if (confirmField.value && passwordField.value !== confirmField.value) {
                    confirmField.setCustomValidity('Passwords do not match');
                } else {
                    confirmField.setCustomValidity('');
                }
            });
        }
        
        // Password strength indicator
        if (passwordField) {
            // Create strength indicator
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            strengthIndicator.style.height = '5px';
            strengthIndicator.style.marginTop = '5px';
            strengthIndicator.style.borderRadius = '2px';
            strengthIndicator.style.transition = 'width 0.3s, background-color 0.3s';
            
            const strengthText = document.createElement('span');
            strengthText.className = 'strength-text';
            strengthText.style.fontSize = '0.8rem';
            strengthText.style.marginTop = '5px';
            strengthText.style.display = 'inline-block';
            
            // Only add if we're on a registration or password reset page
            if (form.querySelector('input[name="confirm_password"]')) {
                passwordField.parentNode.appendChild(strengthIndicator);
                passwordField.parentNode.appendChild(strengthText);
                
                passwordField.addEventListener('input', function() {
                    const password = passwordField.value;
                    let strength = 0;
                    let feedback = '';
                    
                    if (password.length > 0) {
                        // Length check
                        if (password.length >= 8) {
                            strength += 1;
                        }
                        
                        // Contains letters
                        if (/[a-zA-Z]/.test(password)) {
                            strength += 1;
                        }
                        
                        // Contains numbers
                        if (/\d/.test(password)) {
                            strength += 1;
                        }
                        
                        // Contains special characters
                        if (/[^a-zA-Z0-9]/.test(password)) {
                            strength += 1;
                        }
                        
                        // Set indicator width and color based on strength
                        switch (strength) {
                            case 0:
                            case 1:
                                strengthIndicator.style.width = '25%';
                                strengthIndicator.style.backgroundColor = '#e84118';
                                feedback = 'Weak';
                                break;
                            case 2:
                                strengthIndicator.style.width = '50%';
                                strengthIndicator.style.backgroundColor = '#fbc531';
                                feedback = 'Moderate';
                                break;
                            case 3:
                                strengthIndicator.style.width = '75%';
                                strengthIndicator.style.backgroundColor = '#4cd137';
                                feedback = 'Strong';
                                break;
                            case 4:
                                strengthIndicator.style.width = '100%';
                                strengthIndicator.style.backgroundColor = '#44bd32';
                                feedback = 'Very Strong';
                                break;
                        }
                        
                        strengthText.textContent = feedback;
                        strengthText.style.color = strengthIndicator.style.backgroundColor;
                    } else {
                        strengthIndicator.style.width = '0';
                        strengthText.textContent = '';
                    }
                });
            }
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Handle Enter key in login/register forms
    const formInputs = document.querySelectorAll('input');
    formInputs.forEach(function(input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !input.form.querySelector('input[type="text"][name="name"]')) {
                e.preventDefault();
                const submitButton = input.form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.click();
                }
            }
        });
    });
});