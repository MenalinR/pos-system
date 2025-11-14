@extends('layouts.install')

@section('content')
<!-- Step Indicator -->
<div class="step-indicator">
    <div class="step completed" data-step="1">
        <div class="step-circle">1</div>
        <div class="step-label">Requirements</div>
    </div>
    <div class="step completed" data-step="2">
        <div class="step-circle">2</div>
        <div class="step-label">Database</div>
    </div>
    <div class="step active" data-step="3">
        <div class="step-circle">3</div>
        <div class="step-label">Administrator</div>
    </div>
    <div class="step" data-step="4">
        <div class="step-circle">4</div>
        <div class="step-label">Finish</div>
    </div>
</div>

<!-- Step 3: Administrator Setup -->
<div class="step-content active" id="step-3">
    <h2>Administrator Account Setup</h2>
    <p>Create your administrator account and configure basic application settings. This account will have full access to manage the POS system.</p>

    <form id="admin-form">
        @csrf

        <!-- Application Settings -->
        <div class="form-section">
            <h3>Application Settings</h3>
            <div class="form-group">
                <label for="site_name">Application Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="site_name" name="site_name" value="POS System" placeholder="Enter your application name" required>
                <small class="form-text text-muted">This name will appear throughout your application.</small>
            </div>

            <div class="form-group">
                <label for="site_url">Application URL</label>
                <input type="url" class="form-control" id="site_url" name="site_url" value="{{ config('app.url', 'http://localhost:8000') }}" placeholder="https://your-domain.com">
                <small class="form-text text-muted">The public URL where your application will be accessible.</small>
            </div>
        </div>

        <!-- Administrator Account -->
        <div class="form-section">
            <h3>Administrator Account</h3>
            <div class="form-group">
                <label for="admin_name">Full Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Enter your full name" required>
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label for="admin_email">Email Address <span class="required">*</span></label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@yourcompany.com" required>
                <small class="form-text text-muted">This will be your login email address.</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label for="admin_password">Password <span class="required">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="admin_password" name="admin_password" placeholder="Create a strong password" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('admin_password')">
                            <i class="password-toggle-icon">👁</i>
                        </button>
                    </div>
                </div>
                <div class="password-strength" id="password-strength"></div>
                <small class="form-text text-muted">Password should be at least 8 characters long with letters, numbers, and special characters.</small>
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label for="admin_password_confirmation">Confirm Password <span class="required">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" placeholder="Confirm your password" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('admin_password_confirmation')">
                            <i class="password-toggle-icon">👁</i>
                        </button>
                    </div>
                </div>
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="form-section">
            <h3>Security Settings</h3>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="enable_2fa" name="enable_2fa" value="1">
                    <label for="enable_2fa">Enable Two-Factor Authentication (Recommended)</label>
                </div>
                <small class="form-text text-muted">You can set up 2FA after installation is complete.</small>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="force_https" name="force_https" value="1" checked>
                    <label for="force_https">Force HTTPS in production</label>
                </div>
                <small class="form-text text-muted">Ensures all connections use secure HTTPS protocol.</small>
            </div>
        </div>

        <div id="validation-messages"></div>
    </form>

    <div class="installation-footer">
        <a href="{{ route('install.database') }}" class="btn btn-secondary">Back to Database</a>
        <button class="btn" id="install-btn" onclick="validateAndPrepareInstallation()" disabled>
            <span id="install-btn-text">Validate & Continue</span>
            <div id="install-btn-spinner" class="btn-spinner" style="display: none;"></div>
        </button>
    </div>
</div>

@section('scripts')
<script>
// Form validation state
let isValid = {
    name: false,
    email: false,
    password: false,
    confirmation: false
};

// Real-time validation
$(document).ready(function() {
    // Form field validation
    $('#admin_name').on('input', validateName);
    $('#admin_email').on('input', validateEmail);
    $('#admin_password').on('input', validatePassword);
    $('#admin_password_confirmation').on('input', validatePasswordConfirmation);

    // Enable/disable install button based on validation
    function updateInstallButton() {
        const allValid = Object.values(isValid).every(valid => valid);
        $('#install-btn').prop('disabled', !allValid);

        if (allValid) {
            $('#install-btn-text').text('Proceed to Installation');
            $('#install-btn').removeClass('btn-disabled').addClass('btn-primary');
        } else {
            $('#install-btn-text').text('Please Complete Form');
            $('#install-btn').removeClass('btn-primary').addClass('btn-disabled');
        }
    }

    function validateName() {
        const name = $('#admin_name').val().trim();
        const field = $('#admin_name');

        if (name.length < 2) {
            showFieldError(field, 'Name must be at least 2 characters long.');
            isValid.name = false;
        } else if (!/^[a-zA-Z\\s]+$/.test(name)) {
            showFieldError(field, 'Name should only contain letters and spaces.');
            isValid.name = false;
        } else {
            showFieldSuccess(field);
            isValid.name = true;
        }
        updateInstallButton();
    }

    function validateEmail() {
        const email = $('#admin_email').val().trim();
        const field = $('#admin_email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            showFieldError(field, 'Email address is required.');
            isValid.email = false;
        } else if (!emailRegex.test(email)) {
            showFieldError(field, 'Please enter a valid email address.');
            isValid.email = false;
        } else {
            showFieldSuccess(field);
            isValid.email = true;
        }
        updateInstallButton();
    }

    function validatePassword() {
        const password = $('#admin_password').val();
        const field = $('#admin_password');
        const strengthIndicator = $('#password-strength');

        if (password.length === 0) {
            showFieldError(field, 'Password is required.');
            strengthIndicator.html('');
            isValid.password = false;
        } else if (password.length < 8) {
            showFieldError(field, 'Password must be at least 8 characters long.');
            showPasswordStrength(password, strengthIndicator);
            isValid.password = false;
        } else {
            const strength = calculatePasswordStrength(password);
            if (strength.score < 3) {
                showFieldError(field, 'Password is too weak. ' + strength.suggestions);
                isValid.password = false;
            } else {
                showFieldSuccess(field);
                isValid.password = true;
            }
            showPasswordStrength(password, strengthIndicator);
        }

        // Re-validate confirmation if it exists
        if ($('#admin_password_confirmation').val()) {
            validatePasswordConfirmation();
        }
        updateInstallButton();
    }

    function validatePasswordConfirmation() {
        const password = $('#admin_password').val();
        const confirmation = $('#admin_password_confirmation').val();
        const field = $('#admin_password_confirmation');

        if (!confirmation) {
            showFieldError(field, 'Password confirmation is required.');
            isValid.confirmation = false;
        } else if (password !== confirmation) {
            showFieldError(field, 'Passwords do not match.');
            isValid.confirmation = false;
        } else {
            showFieldSuccess(field);
            isValid.confirmation = true;
        }
        updateInstallButton();
    }

    function showFieldError(field, message) {
        field.removeClass('is-valid').addClass('is-invalid');
        field.siblings('.invalid-feedback').text(message);
    }

    function showFieldSuccess(field) {
        field.removeClass('is-invalid').addClass('is-valid');
        field.siblings('.invalid-feedback').text('');
    }

    function calculatePasswordStrength(password) {
        let score = 0;
        let suggestions = [];

        if (password.length >= 8) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        else suggestions.push('Use both uppercase and lowercase letters');

        if (/\\d/.test(password)) score++;
        else suggestions.push('Include at least one number');

        if (/[^\\w\\s]/.test(password)) score++;
        else suggestions.push('Include special characters (!@#$%^&*)');

        if (password.length >= 12) score++;

        return { score, suggestions: suggestions.join(', ') };
    }

    function showPasswordStrength(password, indicator) {
        const strength = calculatePasswordStrength(password);
        const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'];

        const label = strengthLabels[Math.min(strength.score, 4)];
        const color = strengthColors[Math.min(strength.score, 4)];

        indicator.html(`
            <div class="strength-meter">
                <div class="strength-bar" style="width: ${(strength.score / 5) * 100}%; background-color: ${color};"></div>
            </div>
            <small style="color: ${color};">Strength: ${label}</small>
        `);
    }
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.parentElement.querySelector('.password-toggle-icon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.textContent = '🙈';
    } else {
        field.type = 'password';
        icon.textContent = '👁';
    }
}

function validateAndPrepareInstallation() {
    // Show loading state
    $('#install-btn').prop('disabled', true);
    $('#install-btn-text').text('Validating...');
    $('#install-btn-spinner').show();

    // Get form data
    const form = document.getElementById('admin-form');
    const formData = new FormData(form);

    // Convert FormData to plain object for better debugging
    const formObject = {};
    for (let [key, value] of formData.entries()) {
        formObject[key] = value;
    }

    // Add database configuration from previous step
    const dbConfig = JSON.parse(sessionStorage.getItem('db_config') || '{}');

    if (!dbConfig.db_name) {
        showValidationErrors(['Database configuration not found. Please go back and configure the database first.']);
        resetButton();
        return;
    }

    // Add database config to form object
    for (const key in dbConfig) {
        formObject[key] = dbConfig[key];
    }

    // Debug: log the data being sent
    console.log('Sending validation data:', formObject);

    // Send validation request
    $.ajax({
        url: '{{ route("install.administrator.validate") }}',
        method: 'POST',
        data: formObject,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Store the validated data
                sessionStorage.setItem('install_data', JSON.stringify(response.data));

                // Show success message
                $('#validation-messages').html('<div class="alert alert-success">Configuration validated successfully! Proceeding to installation...</div>');

                // Redirect to installation page
                setTimeout(() => {
                    window.location.href = '{{ route("install.run.show") }}';
                }, 1500);
            } else {
                if (response.errors) {
                    const errors = Object.values(response.errors).flat();
                    showValidationErrors(errors);
                } else {
                    showValidationErrors([response.message || 'Validation failed']);
                }
                resetButton();
            }
        },
        error: function(xhr) {
            console.log('AJAX Error:', xhr);
            let errors = ['An error occurred during validation.'];

            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    const serverErrors = Object.values(xhr.responseJSON.errors).flat();
                    errors = serverErrors.length > 0 ? serverErrors : errors;
                } else if (xhr.responseJSON.message) {
                    errors = [xhr.responseJSON.message];
                }
            } else if (xhr.status === 419) {
                errors = ['CSRF token mismatch. Please refresh the page and try again.'];
            } else if (xhr.status === 422) {
                errors = ['Validation failed. Please check your inputs.'];
            } else if (xhr.status === 500) {
                errors = ['Server error occurred. Please check your database connection.'];
            }

            showValidationErrors(errors);
            resetButton();
        }
    });
}function prepareInstallation() {
    try {
        const adminForm = document.getElementById('admin-form');
        const formData = new FormData(adminForm);

        // Get admin form data
        const adminData = {};
        for (let [key, value] of formData) {
            adminData[key] = value;
        }

        // Get database configuration from previous step
        const dbConfig = JSON.parse(sessionStorage.getItem('db_config') || '{}');

        if (!dbConfig.db_name) {
            showValidationErrors(['Database configuration not found. Please go back and configure the database first.']);
            resetButton();
            return;
        }

        // Combine all data
        const installData = {...dbConfig, ...adminData};

        // Store combined data
        sessionStorage.setItem('install_data', JSON.stringify(installData));

        // Show success message
        $('#validation-messages').html('<div class=\"alert alert-success\">Configuration validated successfully! Proceeding to installation...</div>');

        // Redirect to installation page
        setTimeout(() => {
            window.location.href = '{{ route("install.run.show") }}';
        }, 1500);

    } catch (error) {
        console.error('Error preparing installation:', error);
        showValidationErrors(['An error occurred while preparing the installation. Please try again.']);
        resetButton();
    }
}

function showValidationErrors(errors) {
    const errorHtml = '<div class=\"alert alert-danger\"><ul class=\"mb-0\">' +
        errors.map(error => `<li>${error}</li>`).join('') +
        '</ul></div>';
    $('#validation-messages').html(errorHtml);
}

function resetButton() {
    $('#install-btn').prop('disabled', false);
    $('#install-btn-text').text('Validate & Continue');
    $('#install-btn-spinner').hide();
}
</script>
@endsection
@endsection
