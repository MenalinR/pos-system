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
    <h2>Administrator Account</h2>
    <p>Create an administrator account for your Laravel application.</p>
    
    <form id="admin-form">
        @csrf
        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input type="text" class="form-control" id="site_name" name="site_name" value="My Laravel App" placeholder="My Laravel App">
        </div>
        
        <div class="form-group">
            <label for="admin_name">Admin Name</label>
            <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Your full name">
        </div>
        
        <div class="form-group">
            <label for="admin_email">Admin Email</label>
            <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="Your email address">
        </div>
        
        <div class="form-group">
            <label for="admin_password">Admin Password</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" placeholder="Create a strong password">
        </div>
        
        <div class="form-group">
            <label for="admin_password_confirmation">Confirm Password</label>
            <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" placeholder="Confirm your password">
        </div>
    </form>
    
    <div class="installation-footer">
        <a href="{{ route('install.database') }}" class="btn btn-secondary">Back</a>
        <button class="btn" onclick="prepareInstallation()">Install Now</button>
    </div>
</div>

@section('scripts')
<script>
function prepareInstallation() {
    // Validate form first
    const adminForm = document.getElementById('admin-form');
    const formData = new FormData(adminForm);
    
    // Check if passwords match
    const password = document.getElementById('admin_password').value;
    const confirmPassword = document.getElementById('admin_password_confirmation').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    if (!password) {
        alert('Please enter a password!');
        return;
    }
    
    // Get admin form data
    const adminData = {};
    for (let [key, value] of formData) {
        adminData[key] = value;
    }
    
    // Get database configuration from previous step
    const dbConfig = JSON.parse(sessionStorage.getItem('db_config') || '{}');
    
    // Combine all data
    const installData = {...dbConfig, ...adminData};
    
    // Store combined data
    sessionStorage.setItem('install_data', JSON.stringify(installData));
    
    // Redirect to installation page
    window.location.href = '{{ route("install.run.show") }}';
}
</script>
@endsection
@endsection