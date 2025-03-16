document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const validateForm = (formId) => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    };

    // Password strength checker
    const checkPasswordStrength = (password) => {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
        
        return strength;
    };

    // Database connection tester
    const testDatabaseConnection = async (host, user, pass, dbname) => {
        try {
            const response = await fetch('check_database.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ host, user, pass, dbname })
            });
            
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Database connection test failed:', error);
            return false;
        }
    };

    // Progress updater
    const updateProgress = (step, totalSteps) => {
        const progress = (step / totalSteps) * 100;
        const progressBar = document.querySelector('.progress-bar-fill');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        // Update steps indicator
        const steps = document.querySelectorAll('.install-steps li');
        steps.forEach((stepEl, index) => {
            if (index < step) {
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
            } else if (index === step) {
                stepEl.classList.add('active');
                stepEl.classList.remove('completed');
            } else {
                stepEl.classList.remove('active', 'completed');
            }
        });
    };

    // Alert message handler
    const showAlert = (message, type = 'success') => {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;

        const container = document.querySelector('.install-container');
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    };

    // Initialize form validation
    validateForm('database-form');
    validateForm('admin-form');

    // Handle password strength indicator
    const adminPassword = document.getElementById('admin_password');
    if (adminPassword) {
        adminPassword.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            const indicator = document.getElementById('password-strength');
            if (indicator) {
                indicator.className = `strength-${strength}`;
                indicator.textContent = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'][strength - 1];
            }
        });
    }

    // Handle database connection testing
    const testDbButton = document.getElementById('test-db-connection');
    if (testDbButton) {
        testDbButton.addEventListener('click', async function() {
            const host = document.getElementById('db_host').value;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;
            const dbname = document.getElementById('db_name').value;

            testDbButton.disabled = true;
            testDbButton.textContent = 'Testing...';

            const success = await testDatabaseConnection(host, user, pass, dbname);
            
            testDbButton.disabled = false;
            testDbButton.textContent = 'Test Connection';

            showAlert(
                success ? 'Database connection successful!' : 'Database connection failed!',
                success ? 'success' : 'error'
            );
        });
    }

    // Update initial progress
    const currentStep = document.querySelector('.install-steps li.active');
    if (currentStep) {
        const stepIndex = Array.from(currentStep.parentElement.children).indexOf(currentStep);
        const totalSteps = document.querySelectorAll('.install-steps li').length;
        updateProgress(stepIndex, totalSteps);
    }
});
