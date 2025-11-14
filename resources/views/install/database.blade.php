@extends('layouts.install')

@section('content')
<!-- Step Indicator -->
<div class="step-indicator">
    <div class="step completed" data-step="1">
        <div class="step-circle">1</div>
        <div class="step-label">Requirements</div>
    </div>
    <div class="step active" data-step="2">
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

<!-- Step 2: Database Configuration -->
<div class="step-content active" id="step-2">
    <h2>Database Configuration</h2>
    <p>Please provide your database connection details.</p>
    
    <form id="database-form">
        @csrf
        <div class="form-group">
            <label for="db_connection">Database Connection</label>
            <select class="form-control" id="db_connection" name="db_connection">
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
                <option value="sqlite">SQLite</option>
                <option value="sqlsrv">SQL Server</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="db_host">Database Host</label>
            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" placeholder="localhost">
        </div>
        
        <div class="form-group">
            <label for="db_port">Database Port</label>
            <input type="text" class="form-control" id="db_port" name="db_port" value="3306" placeholder="3306">
        </div>
        
        <div class="form-group">
            <label for="db_name">Database Name</label>
            <input type="text" class="form-control" id="db_name" name="db_name" placeholder="Enter database name">
        </div>
        
        <div class="form-group">
            <label for="db_username">Database Username</label>
            <input type="text" class="form-control" id="db_username" name="db_username" placeholder="Enter database username">
        </div>
        
        <div class="form-group">
            <label for="db_password">Database Password</label>
            <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Enter database password">
        </div>
    </form>
    
    <div id="connection-result"></div>
    
    <div class="installation-footer">
        <a href="{{ route('install.requirements') }}" class="btn btn-secondary">Back</a>
        <button class="btn" id="test-connection">Test Connection</button>
        <button class="btn" id="continue-btn" onclick="saveDatabaseConfig()" style="display: none;">Continue</button>
    </div>
</div>

@section('scripts')
<script>
function saveDatabaseConfig() {
    // Save database configuration to sessionStorage
    const dbForm = document.getElementById('database-form');
    const formData = new FormData(dbForm);
    
    const dbConfig = {};
    for (let [key, value] of formData) {
        dbConfig[key] = value;
    }
    
    sessionStorage.setItem('db_config', JSON.stringify(dbConfig));
    window.location.href = '{{ route("install.administrator") }}';
}

$(document).ready(function() {
    $('#test-connection').click(function() {
        const formData = $('#database-form').serialize();
        
        $('#connection-result').html('<div class="alert alert-info">Testing database connection...</div>');
        
        $.post('{{ route("install.database.test") }}', formData)
            .done(function(response) {
                if (response.success) {
                    $('#connection-result').html('<div class="alert alert-success">' + response.message + '</div>');
                    $('#continue-btn').show();
                } else {
                    $('#connection-result').html('<div class="alert alert-danger">' + response.message + '</div>');
                    $('#continue-btn').hide();
                }
            })
            .fail(function() {
                $('#connection-result').html('<div class="alert alert-danger">Connection test failed</div>');
                $('#continue-btn').hide();
            });
    });
});
</script>
@endsection
@endsection