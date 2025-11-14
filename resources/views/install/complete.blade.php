@extends('layouts.install')

@section('content')
<!-- Success Screen -->
<div class="step-content success-screen active" id="success-screen">
    <div class="success-icon">✓</div>
    <h2>Installation Complete!</h2>
    <p>Your Laravel application has been successfully installed.</p>
    <div class="alert alert-success" style="text-align: left; margin: 1.5rem 0;">
        <p><strong>Important:</strong> For security reasons, please delete the installation files after completing the setup.</p>
        <p><strong>Note:</strong> You may need to configure your web server to point to the <code>public</code> directory.</p>
    </div>
    <div style="margin-top: 2rem;">
        <a href="{{ url('/') }}" class="btn" style="margin-right: 1rem;">Visit Your Site</a>
        <a href="{{ url('/login') }}" class="btn btn-secondary">Log In to Admin</a>
    </div>
</div>
@endsection