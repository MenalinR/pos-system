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
    <div class="step completed" data-step="3">
        <div class="step-circle">3</div>
        <div class="step-label">Administrator</div>
    </div>
    <div class="step active" data-step="4">
        <div class="step-circle">4</div>
        <div class="step-label">Finish</div>
    </div>
</div>

<!-- Step 4: Installation Progress & Completion -->
<div class="step-content active" id="step-4">
    <h2>Installation</h2>
    <p>We're now ready to install your Laravel application. This may take a few moments.</p>
    
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div id="progress-text" style="text-align: center; margin-top: 0.5rem;">0%</div>
    </div>
    
    <div id="installation-log">
        <div class="alert alert-info">Preparing installation...</div>
    </div>

    <div id="debug-info" class="debug-info" style="display: none;">
        <h4>Debug Information:</h4>
        <div id="debug-content"></div>
    </div>
    
    <div class="installation-footer">
        <a href="{{ route('install.administrator') }}" class="btn btn-secondary">Back</a>
        <button class="btn btn-success" id="finish-button" onclick="startInstallation()">Start Installation</button>
    </div>
</div>

@section('scripts')
<script>
function startInstallation() {
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const log = document.getElementById('installation-log');
    const finishButton = document.getElementById('finish-button');
    const debugInfo = document.getElementById('debug-info');
    const debugContent = document.getElementById('debug-content');
    
    finishButton.disabled = true;
    finishButton.textContent = 'Installing...';
    
    // Get installation data from sessionStorage
    const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
    
    if (Object.keys(installData).length === 0) {
        log.innerHTML += '<div class="alert alert-danger">No installation data found. Please go back and try again.</div>';
        finishButton.disabled = false;
        finishButton.textContent = 'Try Again';
        return;
    }
    
    // Convert to FormData
    const formData = new FormData();
    for (const key in installData) {
        formData.append(key, installData[key]);
    }
    
    // Add CSRF token
    formData.append('_token', '{{ csrf_token() }}');
    
    let progress = 0;
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += 10;
            progressFill.style.width = `${progress}%`;
            progressText.textContent = `${progress}%`;
        }
    }, 500);
    
    // Send actual installation request
    $.ajax({
        url: '{{ route("install.run") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            clearInterval(progressInterval);
            progress = 100;
            progressFill.style.width = '100%';
            progressText.textContent = '100% - Complete!';
            
            if (response.success) {
                log.innerHTML += '<div class="alert alert-success">' + response.message + '</div>';
                log.innerHTML += '<div class="alert alert-info">✓ Environment file updated</div>';
                log.innerHTML += '<div class="alert alert-info">✓ Database migrations completed</div>';
                log.innerHTML += '<div class="alert alert-info">✓ Admin user created: ' + response.admin_user.name + ' (' + response.admin_user.email + ')</div>';
                
                // Show debug info if available
                if (response.debug) {
                    debugInfo.style.display = 'block';
                    debugContent.innerHTML = response.debug.join('<br>');
                }
                
                // Clear session storage
                sessionStorage.removeItem('install_data');
                sessionStorage.removeItem('db_config');
                
                setTimeout(() => {
                    window.location.href = '{{ route("install.complete") }}';
                }, 3000);
            } else {
                log.innerHTML += '<div class="alert alert-danger">' + response.message + '</div>';
                if (response.errors) {
                    for (const error in response.errors) {
                        log.innerHTML += '<div class="alert alert-danger">' + response.errors[error] + '</div>';
                    }
                }
                // Show debug info if available
                if (response.debug) {
                    debugInfo.style.display = 'block';
                    debugContent.innerHTML = response.debug.join('<br>');
                }
                finishButton.disabled = false;
                finishButton.textContent = 'Try Again';
            }
        },
        error: function(xhr) {
            clearInterval(progressInterval);
            progress = 100;
            progressFill.style.width = '100%';
            progressText.textContent = '100% - Error!';
            
            let errorMessage = 'Installation failed';
            let debugInfoContent = '';
            
            if (xhr.responseJSON) {
                errorMessage = xhr.responseJSON.message || errorMessage;
                if (xhr.responseJSON.debug) {
                    debugInfo.style.display = 'block';
                    debugContent.innerHTML = xhr.responseJSON.debug.join('<br>');
                }
            }
            
            log.innerHTML += '<div class="alert alert-danger">' + errorMessage + '</div>';
            log.innerHTML += '<div class="alert alert-info">Check your database credentials and try again.</div>';
            
            finishButton.disabled = false;
            finishButton.textContent = 'Try Again';
        }
    });
}

// Auto-start installation after a short delay
$(document).ready(function() {
    setTimeout(() => {
        startInstallation();
    }, 1000);
});
</script>
@endsection
@endsection