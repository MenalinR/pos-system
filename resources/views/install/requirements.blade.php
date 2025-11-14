@extends('layouts.install')

@section('content')
<!-- Step Indicator -->
<div class="step-indicator">
    <div class="step active" data-step="1">
        <div class="step-circle">1</div>
        <div class="step-label">Requirements</div>
    </div>
    <div class="step" data-step="2">
        <div class="step-circle">2</div>
        <div class="step-label">Database</div>
    </div>
    <div class="step" data-step="3">
        <div class="step-circle">3</div>
        <div class="step-label">Administrator</div>
    </div>
    <div class="step" data-step="4">
        <div class="step-circle">4</div>
        <div class="step-label">Finish</div>
    </div>
</div>

<!-- Step 1: Requirements Check -->
<div class="step-content active" id="step-1">
    <h2>Server Requirements</h2>
    <p>Before we begin, let's check if your server meets the requirements for running Laravel.</p>
    
    <ul class="requirements-list">
        <li>
            <span class="requirement-status {{ $phpSupported ? 'status-success' : 'status-error' }}">
                {{ $phpSupported ? '✓' : '✗' }}
            </span>
            PHP Version 8.1 or higher ({{ $phpVersion }} detected)
        </li>
        @foreach($extensions as $extension => $enabled)
        <li>
            <span class="requirement-status {{ $enabled ? 'status-success' : 'status-error' }}">
                {{ $enabled ? '✓' : '✗' }}
            </span>
            {{ ucfirst($extension) }} PHP Extension ({{ $enabled ? 'Enabled' : 'Disabled' }})
        </li>
        @endforeach
        @foreach($permissions as $folder => $permission)
        <li>
            <span class="requirement-status {{ $permission['writable'] ? 'status-success' : 'status-error' }}">
                {{ $permission['writable'] ? '✓' : '✗' }}
            </span>
            {{ $folder }} directory writable (Current: {{ $permission['current'] }}, Required: {{ $permission['required'] }})
        </li>
        @endforeach
    </ul>
    
    @if($allRequirementsMet)
    <div class="alert alert-success">
        All requirements are met! You can proceed with the installation.
    </div>
    @else
    <div class="alert alert-danger">
        Some requirements are not met. Please fix them before proceeding.
    </div>
    @endif
    
    <div class="installation-footer">
        <div></div>
        <a href="{{ route('install.database') }}" class="btn" {{ !$allRequirementsMet ? 'disabled' : '' }}>
            Continue
        </a>
    </div>
</div>
@endsection