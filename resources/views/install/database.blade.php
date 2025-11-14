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
    <p>Configure your database connection. You can either create a new database or use an existing one.</p>

    <form id="database-form">
        @csrf
        <div class="form-group">
            <label for="db_connection">Database Connection</label>
            <select class="form-control" id="db_connection" name="db_connection" onchange="updatePortField()">
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
                <option value="sqlite">SQLite</option>
                <option value="sqlsrv">SQL Server</option>
            </select>
        </div>

        <div class="form-group" id="host-group">
            <label for="db_host">Database Host</label>
            <input type="text" class="form-control" id="db_host" name="db_host" value="127.0.0.1" placeholder="127.0.0.1">
        </div>

        <div class="form-group" id="port-group">
            <label for="db_port">Database Port</label>
            <input type="text" class="form-control" id="db_port" name="db_port" value="3306" placeholder="3306">
        </div>

        <div class="form-group">
            <label for="db_username">Database Username</label>
            <input type="text" class="form-control" id="db_username" name="db_username" value="root" placeholder="Enter database username">
        </div>

        <div class="form-group">
            <label for="db_password">Database Password</label>
            <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Enter database password (leave empty if none)">
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-secondary" id="test-credentials" onclick="testCredentials()">Test Connection Credentials</button>
        </div>

        <div id="credentials-result"></div>

        <!-- Database Selection Section -->
        <div id="database-section" style="display: none;">
            <hr style="margin: 2rem 0;">
            <h3>Database Selection</h3>

            <div class="form-group">
                <label>Choose Database Option:</label>
                <div class="radio-group" style="margin-top: 0.5rem;">
                    <label class="radio-option">
                        <input type="radio" name="db_option" value="existing" onchange="toggleDatabaseOption()">
                        Use Existing Database
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="db_option" value="new" onchange="toggleDatabaseOption()">
                        Create New Database
                    </label>
                </div>
            </div>

            <!-- Existing Database Selection -->
            <div id="existing-db-section" style="display: none;">
                <div class="form-group">
                    <label for="existing_db_select">Select Existing Database:</label>
                    <select class="form-control" id="existing_db_select" name="existing_db_select">
                        <option value="">Loading databases...</option>
                    </select>
                    <button type="button" class="btn btn-secondary" onclick="refreshDatabaseList()" style="margin-top: 0.5rem;">Refresh List</button>
                </div>
            </div>

            <!-- New Database Creation -->
            <div id="new-db-section" style="display: none;">
                <div class="form-group">
                    <label for="new_db_name">New Database Name:</label>
                    <input type="text" class="form-control" id="new_db_name" name="new_db_name" placeholder="Enter new database name (letters, numbers, underscores only)" pattern="[a-zA-Z0-9_]+" maxlength="64">
                    <small class="form-text text-muted">Database name should contain only letters, numbers, and underscores.</small>
                    <button type="button" class="btn btn-success" onclick="createNewDatabase()" style="margin-top: 0.5rem;">Create Database</button>
                </div>
            </div>

            <div id="database-operation-result"></div>
        </div>

        <div class="form-group" id="final-db-section" style="display: none;">
            <label for="db_name">Final Database Name:</label>
            <input type="text" class="form-control" id="db_name" name="db_name" readonly>
        </div>
    </form>

    <div id="connection-result"></div>

    <div class="installation-footer">
        <a href="{{ route('install.requirements') }}" class="btn btn-secondary">Back</a>
        <button class="btn" id="test-final-connection" onclick="testFinalConnection()" style="display: none;">Test Final Connection</button>
        <button class="btn" id="continue-btn" onclick="saveDatabaseConfig()" style="display: none;">Continue</button>
    </div>
</div>

@section('scripts')
<script>
function updatePortField() {
    const dbConnection = document.getElementById('db_connection').value;
    const portField = document.getElementById('db_port');

    const defaultPorts = {
        'mysql': '3306',
        'pgsql': '5432',
        'sqlite': '',
        'sqlsrv': '1433'
    };

    portField.value = defaultPorts[dbConnection] || '3306';

    // Hide host/port for SQLite
    const hostGroup = document.getElementById('host-group');
    const portGroup = document.getElementById('port-group');

    if (dbConnection === 'sqlite') {
        hostGroup.style.display = 'none';
        portGroup.style.display = 'none';
    } else {
        hostGroup.style.display = 'block';
        portGroup.style.display = 'block';
    }
}

function testCredentials() {
    const dbConnection = document.getElementById('db_connection').value;
    const dbHost = document.getElementById('db_host').value;
    const dbPort = document.getElementById('db_port').value;
    const dbUsername = document.getElementById('db_username').value;
    const dbPassword = document.getElementById('db_password').value;

    if (!dbHost || !dbPort || !dbUsername) {
        $('#credentials-result').html('<div class="alert alert-warning">Please fill in all connection fields.</div>');
        return;
    }

    const formData = {
        _token: '{{ csrf_token() }}',
        db_connection: dbConnection,
        db_host: dbHost,
        db_port: dbPort,
        db_username: dbUsername,
        db_password: dbPassword
    };

    $('#credentials-result').html('<div class="alert alert-info">Testing connection credentials...</div>');

    $.post('{{ route("install.database.list") }}', formData)
        .done(function(response) {
            if (response.success) {
                $('#credentials-result').html('<div class="alert alert-success">Connection successful! You can now proceed with database selection.</div>');
                $('#database-section').show();
                loadExistingDatabases(response.databases);
            } else {
                $('#credentials-result').html('<div class="alert alert-danger">' + response.message + '</div>');
                $('#database-section').hide();
            }
        })
        .fail(function(xhr) {
            let errorMsg = 'Connection test failed';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            $('#credentials-result').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            $('#database-section').hide();
        });
}

function loadExistingDatabases(databases) {
    const selectElement = document.getElementById('existing_db_select');
    selectElement.innerHTML = '<option value="">Select a database...</option>';

    if (Array.isArray(databases)) {
        databases.forEach(function(dbName) {
            const option = document.createElement('option');
            option.value = dbName;
            option.textContent = dbName;
            selectElement.appendChild(option);
        });
    }
}

function toggleDatabaseOption() {
    const selectedOption = document.querySelector('input[name="db_option"]:checked').value;
    const existingDbSection = document.getElementById('existing-db-section');
    const newDbSection = document.getElementById('new-db-section');

    if (selectedOption === 'existing') {
        existingDbSection.style.display = 'block';
        newDbSection.style.display = 'none';
    } else {
        existingDbSection.style.display = 'none';
        newDbSection.style.display = 'block';
    }

    // Clear any previous database operation results
    document.getElementById('database-operation-result').innerHTML = '';
    document.getElementById('final-db-section').style.display = 'none';
    document.getElementById('test-final-connection').style.display = 'none';
    document.getElementById('continue-btn').style.display = 'none';
}

function refreshDatabaseList() {
    testCredentials();
}

function createNewDatabase() {
    const newDbName = document.getElementById('new_db_name').value;

    if (!newDbName) {
        $('#database-operation-result').html('<div class="alert alert-warning">Please enter a database name.</div>');
        return;
    }

    if (!/^[a-zA-Z0-9_]+$/.test(newDbName)) {
        $('#database-operation-result').html('<div class="alert alert-warning">Database name should contain only letters, numbers, and underscores.</div>');
        return;
    }

    const formData = {
        _token: '{{ csrf_token() }}',
        db_connection: document.getElementById('db_connection').value,
        db_host: document.getElementById('db_host').value,
        db_port: document.getElementById('db_port').value,
        db_username: document.getElementById('db_username').value,
        db_password: document.getElementById('db_password').value,
        new_db_name: newDbName
    };

    $('#database-operation-result').html('<div class="alert alert-info">Creating database and updating configuration...</div>');

    $.post('{{ route("install.database.create") }}', formData)
        .done(function(response) {
            if (response.success) {
                let message = response.message;
                if (response.env_updated) {
                    message += ' Configuration has been automatically saved.';
                }
                $('#database-operation-result').html('<div class="alert alert-success">' + message + '</div>');
                setFinalDatabase(response.database_name);
                // Auto-test the connection after creation
                setTimeout(function() {
                    testFinalConnection();
                }, 1000);
            } else {
                $('#database-operation-result').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        })
        .fail(function(xhr) {
            let errorMsg = 'Failed to create database';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            $('#database-operation-result').html('<div class="alert alert-danger">' + errorMsg + '</div>');
        });
}

function setFinalDatabase(dbName) {
    document.getElementById('db_name').value = dbName;
    document.getElementById('final-db-section').style.display = 'block';
    document.getElementById('test-final-connection').style.display = 'inline-block';
}

// Handle existing database selection
$(document).on('change', '#existing_db_select', function() {
    const selectedDb = $(this).val();
    if (selectedDb) {
        setFinalDatabase(selectedDb);
        // Automatically save configuration when existing database is selected
        saveExistingDatabaseConfig(selectedDb);
    }
});

function saveExistingDatabaseConfig(databaseName) {
    const formData = {
        _token: '{{ csrf_token() }}',
        db_connection: document.getElementById('db_connection').value,
        db_host: document.getElementById('db_host').value,
        db_port: document.getElementById('db_port').value,
        db_name: databaseName,
        db_username: document.getElementById('db_username').value,
        db_password: document.getElementById('db_password').value
    };

    $('#database-operation-result').html('<div class="alert alert-info">Saving database configuration...</div>');

    $.post('{{ route("install.database.save") }}', formData)
        .done(function(response) {
            if (response.success) {
                $('#database-operation-result').html('<div class="alert alert-success">' + response.message + '</div>');
                // Auto-test the connection after saving
                setTimeout(function() {
                    testFinalConnection();
                }, 1000);
            } else {
                $('#database-operation-result').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        })
        .fail(function(xhr) {
            let errorMsg = 'Failed to save database configuration';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            $('#database-operation-result').html('<div class="alert alert-danger">' + errorMsg + '</div>');
        });
}

function testFinalConnection() {
    const formData = $('#database-form').serialize();

    $('#connection-result').html('<div class="alert alert-info">Testing final database connection...</div>');

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
            $('#connection-result').html('<div class="alert alert-danger">Final connection test failed</div>');
            $('#continue-btn').hide();
        });
}

function saveDatabaseConfig() {
    // First save to server (already done automatically when db is selected/created)
    // Then save to sessionStorage for frontend use
    const dbForm = document.getElementById('database-form');
    const formData = new FormData(dbForm);

    const dbConfig = {};
    for (let [key, value] of formData) {
        dbConfig[key] = value;
    }

    // Verify that database configuration is saved
    const dbName = document.getElementById('db_name').value;
    if (!dbName) {
        $('#connection-result').html('<div class="alert alert-warning">Please select or create a database first.</div>');
        return;
    }

    sessionStorage.setItem('db_config', JSON.stringify(dbConfig));

    // Show success message and proceed
    $('#connection-result').html('<div class="alert alert-success">Database configuration is ready! Proceeding to administrator setup...</div>');

    // Add a small delay to show the message
    setTimeout(function() {
        window.location.href = '{{ route("install.administrator") }}';
    }, 1500);
}

// Initialize port field on page load
$(document).ready(function() {
    updatePortField();
});
</script>
@endsection
@endsection
