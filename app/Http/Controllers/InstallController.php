<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    public function index()
    {
        // Check if app is already installed
        if (file_exists(storage_path('installed'))) {
            return redirect('/')->with('message', 'Application is already installed.');
        }
        return redirect()->route('install.requirements');
    }

    public function requirements()
    {
        // Check if app is already installed
        if (file_exists(storage_path('installed'))) {
            return redirect('/')->with('message', 'Application is already installed.');
        }

        $requirements = [
            'php_version' => '8.1',
            'extensions' => [
                'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'json',
                'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'
            ],
        ];

        $phpVersion = phpversion();
        $phpSupported = version_compare($phpVersion, $requirements['php_version'], '>=');

        $extensions = [];
        foreach ($requirements['extensions'] as $extension) {
            $extensions[$extension] = extension_loaded($extension);
        }

        $permissions = [];
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($paths as $name => $path) {
            if (File::exists($path)) {
                $currentPermissions = substr(sprintf('%o', fileperms($path)), -4);
                $permissions[$name] = [
                    'writable' => is_writable($path),
                    'current' => $currentPermissions,
                    'required' => '0755'
                ];
            }
        }

        $allRequirementsMet = $phpSupported && !in_array(false, $extensions) &&
                             collect($permissions)->every(fn($perm) => $perm['writable']);

        return view('install.requirements', compact(
            'phpVersion', 'phpSupported', 'extensions', 'permissions', 'allRequirementsMet'
        ));
    }

    public function database()
    {
        // Check if app is already installed
        if (file_exists(storage_path('installed'))) {
            return redirect('/')->with('message', 'Application is already installed.');
        }

        return view('install.database');
    }

    public function testDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host' => 'required',
            'db_port' => 'required',
            'db_name' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill all required fields'
            ]);
        }

        try {
            Config::set('database.connections.install_test', [
                'driver' => $request->db_connection,
                'host' => $request->db_host,
                'port' => $request->db_port,
                'database' => $request->db_name,
                'username' => $request->db_username,
                'password' => $request->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);

            DB::connection('install_test')->getPdo();

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
        }
    }

    public function listDatabases(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host' => 'required',
            'db_port' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill all required connection fields'
            ]);
        }

        try {
            // Connect without specifying a database
            Config::set('database.connections.install_list', [
                'driver' => $request->db_connection,
                'host' => $request->db_host,
                'port' => $request->db_port,
                'database' => null,
                'username' => $request->db_username,
                'password' => $request->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);

            $pdo = DB::connection('install_list')->getPdo();

            // Get list of databases based on connection type
            $databases = [];
            switch ($request->db_connection) {
                case 'mysql':
                    $stmt = $pdo->query('SHOW DATABASES');
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $dbName = $row['Database'];
                        if (!in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                            $databases[] = $dbName;
                        }
                    }
                    break;
                case 'pgsql':
                    $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false AND datname != 'postgres'");
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $databases[] = $row['datname'];
                    }
                    break;
                default:
                    $databases = ['sqlite' => 'SQLite databases are file-based'];
            }

            return response()->json([
                'success' => true,
                'databases' => $databases
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list databases: ' . $e->getMessage()
            ]);
        }
    }

    public function createDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host' => 'required',
            'db_port' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
            'new_db_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid database name. Use only letters, numbers, and underscores.',
                'errors' => $validator->errors()
            ]);
        }

        try {
            // Connect without specifying a database
            Config::set('database.connections.install_create', [
                'driver' => $request->db_connection,
                'host' => $request->db_host,
                'port' => $request->db_port,
                'database' => null,
                'username' => $request->db_username,
                'password' => $request->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);

            $pdo = DB::connection('install_create')->getPdo();

            $dbName = $request->new_db_name;

            // Create database based on connection type
            switch ($request->db_connection) {
                case 'mysql':
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    break;
                case 'pgsql':
                    $pdo->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING 'UTF8'");
                    break;
                default:
                    throw new \Exception('Database creation not supported for this connection type');
            }

            // Automatically update .env file with new database configuration
            $this->updateDatabaseInEnv($request, $dbName);

            return response()->json([
                'success' => true,
                'message' => "Database '{$dbName}' created successfully and .env file updated!",
                'database_name' => $dbName,
                'env_updated' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create database: ' . $e->getMessage()
            ]);
        }
    }

    public function saveDatabaseConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host' => 'required',
            'db_port' => 'required',
            'db_name' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill all required fields',
                'errors' => $validator->errors()
            ]);
        }

        try {
            // Update .env file with database configuration
            $this->updateDatabaseInEnv($request, $request->db_name);

            return response()->json([
                'success' => true,
                'message' => 'Database configuration saved to .env file!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save database configuration: ' . $e->getMessage()
            ]);
        }
    }

    private function updateDatabaseInEnv($request, $databaseName)
    {
        $envPath = base_path('.env');

        // Ensure .env file exists
        if (!File::exists($envPath)) {
            $envExamplePath = base_path('.env.example');
            if (File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
            } else {
                // Create a basic .env file
                File::put($envPath, '');
            }
        }

        // Read current .env content
        $envContent = File::get($envPath);

        // Define database updates
        $updates = [
            'DB_CONNECTION' => $request->db_connection,
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $databaseName,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ? '"' . addslashes($request->db_password) . '"' : '""',
        ];

        // Apply each update
        foreach ($updates as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Replace existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Append new key
                $envContent .= "\n{$key}={$value}";
            }
        }

        // Write updated content back to .env
        File::put($envPath, $envContent);

        // Clear configuration cache to reload new values
        if (function_exists('config')) {
            Artisan::call('config:clear');
        }
    }

    public function administrator()
    {
        // Check if app is already installed
        if (file_exists(storage_path('installed'))) {
            return redirect('/')->with('message', 'Application is already installed.');
        }

        // Check if database configuration exists
        $dbConfig = session('db_config');
        if (!$dbConfig && !env('DB_DATABASE')) {
            return redirect()->route('install.database')
                ->with('error', 'Please configure the database first.');
        }

        return view('install.administrator');
    }

    public function validateAdmin(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Admin validation request:', $request->all());

            // Validate the administrator configuration
            $validator = Validator::make($request->all(), [
                'site_name' => 'required|string|max:255',
                'site_url' => 'nullable|url',
                'admin_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
                'admin_email' => 'required|email|max:255',
                'admin_password' => 'required|min:8|confirmed',
                'db_connection' => 'required',
                'db_host' => 'required',
                'db_port' => 'required',
                'db_name' => 'required',
                'db_username' => 'required',
                'db_password' => 'nullable',
                'enable_2fa' => 'boolean',
                'force_https' => 'boolean',
            ], [
                'admin_name.regex' => 'Admin name should only contain letters and spaces.',
                'admin_password.confirmed' => 'Password confirmation does not match.',
                'admin_password.min' => 'Password must be at least 8 characters long.',
                'admin_email.required' => 'This will be your login email address.',
                'admin_email.email' => 'Please enter a valid email address.',
                'admin_email.max' => 'Email address is too long.',
                'site_name.required' => 'Application name is required.',
                'site_url.url' => 'Please enter a valid URL for the application URL.',
            ]);

            if ($validator->fails()) {
                \Log::warning('Admin validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the validation errors.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Test database connection with provided credentials
            try {
                Config::set('database.connections.install_validate', [
                    'driver' => $request->db_connection,
                    'host' => $request->db_host,
                    'port' => $request->db_port,
                    'database' => $request->db_name,
                    'username' => $request->db_username,
                    'password' => $request->db_password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                ]);

                DB::connection('install_validate')->getPdo();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database connection failed. Please check your database credentials.',
                    'errors' => ['database' => ['Could not connect to database: ' . $e->getMessage()]]
                ], 422);
            }

            // If validation passes, return the validated data
            return response()->json([
                'success' => true,
                'message' => 'Configuration validated successfully!',
                'data' => $request->all()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during validation: ' . $e->getMessage()
            ], 500);
        }
    }    public function showInstall()
    {
        return view('install.run');
    }

    public function install(Request $request)
    {
        // Enable detailed error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $debug_messages = [];

        try {
            $debug_messages[] = "=== INSTALLATION STARTED ===";
            $debug_messages[] = "Request method: " . $request->method();
            $debug_messages[] = "Request headers: " . json_encode($request->headers->all());
            $debug_messages[] = "Request data received: " . json_encode($request->all());
            $debug_messages[] = "Request input count: " . count($request->all());

            // Log this info
            \Log::info('Installation request details', [
                'method' => $request->method(),
                'all_data' => $request->all(),
                'input_count' => count($request->all())
            ]);

            // Check if we have any data at all
            if (count($request->all()) == 0) {
                $debug_messages[] = "ERROR: No input data received";
                return response()->json([
                    'success' => false,
                    'message' => 'No installation data received. Please go back and fill in the administrator form.',
                    'debug' => $debug_messages
                ], 400);
            }

            // STEP 1: Validate input
            $debug_messages[] = "STEP 1: Validating input...";
            $validator = Validator::make($request->all(), [
                'site_name' => 'required|string|max:255',
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255',
                'admin_password' => 'required|min:8',
                'db_connection' => 'required',
                'db_host' => 'required',
                'db_port' => 'required',
                'db_name' => 'required',
                'db_username' => 'required',
            ], [
                'site_name.required' => 'Application name is required.',
                'admin_name.required' => 'Admin name is required.',
                'admin_email.required' => 'Admin email is required.',
                'admin_email.email' => 'Please enter a valid email address.',
                'admin_password.required' => 'Admin password is required.',
                'admin_password.min' => 'Password must be at least 8 characters long.',
                'db_name.required' => 'Database name is required.',
                'db_connection.required' => 'Database connection type is required.',
                'db_host.required' => 'Database host is required.',
                'db_port.required' => 'Database port is required.',
                'db_username.required' => 'Database username is required.',
            ]);

            if ($validator->fails()) {
                $debug_messages[] = "VALIDATION FAILED: " . json_encode($validator->errors()->toArray());
                \Log::warning('Installation validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input data.',
                    'errors' => $validator->errors(),
                    'debug' => $debug_messages
                ], 422);
            }
            $debug_messages[] = "✓ Input validation passed";

            // STEP 2: Update .env file
            $debug_messages[] = "STEP 2: Updating environment file...";
            $this->updateEnvironmentFile($request, $debug_messages);
            $debug_messages[] = "✓ Environment file updated successfully";

            // STEP 3: Clear caches
            $debug_messages[] = "STEP 3: Clearing configuration cache...";
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $debug_messages[] = "✓ Caches cleared successfully";

            // STEP 4: Test database connection
            $debug_messages[] = "STEP 4: Testing database connection...";
            $this->testDatabaseConnectionAfterEnvUpdate($request, $debug_messages);
            $debug_messages[] = "✓ Database connection test passed";

            // STEP 5: Handle migrations intelligently
            $debug_messages[] = "STEP 5: Checking and running migrations...";

            try {
                // Check for existing tables that might conflict
                $conflictingTables = $this->checkForConflictingTables();

                if (count($conflictingTables) > 0) {
                    $debug_messages[] = "Found existing tables: " . implode(', ', $conflictingTables);
                    $debug_messages[] = "Running fresh migration to handle existing tables...";

                    // Run fresh migration to clean up and recreate
                    Artisan::call('migrate:fresh', ['--force' => true]);
                    $debug_messages[] = "✓ Fresh migrations completed successfully";
                } else {
                    $debug_messages[] = "No conflicting tables found, running normal migration...";
                    Artisan::call('migrate', ['--force' => true]);
                    $debug_messages[] = "✓ Migrations completed successfully";
                }

            } catch (\Exception $e) {
                $debug_messages[] = "Migration error: " . $e->getMessage();

                // If migration fails due to existing tables, try migrate:fresh
                if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Base table') !== false) {
                    $debug_messages[] = "Detected table conflict, attempting fresh migration...";
                    try {
                        Artisan::call('migrate:fresh', ['--force' => true]);
                        $debug_messages[] = "✓ Fresh migration resolved the conflict";
                    } catch (\Exception $freshException) {
                        $debug_messages[] = "Fresh migration also failed: " . $freshException->getMessage();
                        throw new \Exception("Database migration failed. Please ensure the database is empty or has proper permissions. Error: " . $freshException->getMessage());
                    }
                } else {
                    throw $e;
                }
            }

            // STEP 6: Create admin user
            $debug_messages[] = "STEP 6: Creating admin user...";
            $adminUser = $this->createAdminUser($request, $debug_messages);
            $debug_messages[] = "✓ Admin user created: " . $adminUser->email;

            // STEP 7: Create installed file
            $debug_messages[] = "STEP 7: Creating installed file...";
            File::put(storage_path('installed'), date('Y-m-d H:i:s'));
            $debug_messages[] = "✓ Installed file created";

            $debug_messages[] = "=== INSTALLATION COMPLETED SUCCESSFULLY ===";

            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully!',
                'admin_user' => [
                    'name' => $adminUser->name,
                    'email' => $adminUser->email
                ],
                'debug' => $debug_messages
            ]);

        } catch (\Exception $e) {
            $debug_messages[] = "=== INSTALLATION FAILED ===";
            $debug_messages[] = "Error: " . $e->getMessage();
            $debug_messages[] = "File: " . $e->getFile();
            $debug_messages[] = "Line: " . $e->getLine();
            $debug_messages[] = "Exception Class: " . get_class($e);

            // Log the full error
            \Log::error('Installation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'debug' => $debug_messages,
                'errors' => [
                    'installation' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    private function updateEnvironmentFile($request, &$debug_messages)
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        $debug_messages[] = "Env path: " . $envPath;
        $debug_messages[] = "Env example path: " . $envExamplePath;

        // Copy .env.example to .env if it doesn't exist
        if (!File::exists($envPath)) {
            $debug_messages[] = ".env file does not exist, creating from .env.example";
            if (!File::exists($envExamplePath)) {
                throw new \Exception('.env.example file not found!');
            }
            File::copy($envExamplePath, $envPath);
            $debug_messages[] = ".env file created successfully";
        } else {
            $debug_messages[] = ".env file already exists";
        }

        // Read the current .env content
        $envContent = File::get($envPath);
        $debug_messages[] = "Current .env content length: " . strlen($envContent);

        // Define all the updates we need to make
        $updates = [
            'APP_NAME' => '"' . addslashes($request->site_name) . '"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $request->site_url ?: 'http://localhost:8000',

            'DB_CONNECTION' => $request->db_connection,
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_name,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ? '"' . addslashes($request->db_password) . '"' : '""',

            // Set proper drivers after installation
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ];        $debug_messages[] = "Updates to apply: " . json_encode($updates);

        // Apply each update
        foreach ($updates as $key => $value) {
            $debug_messages[] = "Processing key: {$key} = {$value}";

            // Check if the key exists in the file
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Replace existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
                $debug_messages[] = "Replaced existing key: {$key}";
            } else {
                // Append new key
                $envContent .= "\n{$key}={$value}";
                $debug_messages[] = "Appended new key: {$key}";
            }
        }

        // Write the updated content back to .env
        File::put($envPath, $envContent);

        // Verify the file was written
        if (!File::exists($envPath)) {
            throw new \Exception('Failed to write .env file');
        }

        $newContent = File::get($envPath);
        $debug_messages[] = "New .env content length: " . strlen($newContent);
        $debug_messages[] = "Environment file update completed";
    }

    private function testDatabaseConnectionAfterEnvUpdate($request, &$debug_messages)
    {
        $debug_messages[] = "Testing database connection with new config...";
        $debug_messages[] = "DB Host: " . $request->db_host;
        $debug_messages[] = "DB Port: " . $request->db_port;
        $debug_messages[] = "DB Name: " . $request->db_name;
        $debug_messages[] = "DB Username: " . $request->db_username;

        // Reload the configuration
        config([
            'database.connections.mysql.host' => $request->db_host,
            'database.connections.mysql.port' => $request->db_port,
            'database.connections.mysql.database' => $request->db_name,
            'database.connections.mysql.username' => $request->db_username,
            'database.connections.mysql.password' => $request->db_password,
        ]);

        // Purge and reconnect
        DB::purge('mysql');
        DB::reconnect('mysql');

        // Test connection
        try {
            $pdo = DB::connection('mysql')->getPdo();
            $debug_messages[] = "Database connection successful!";
            $debug_messages[] = "Database version: " . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {
            $debug_messages[] = "Database connection failed: " . $e->getMessage();
            throw $e;
        }
    }

    private function createAdminUser($request, &$debug_messages)
    {
        $debug_messages[] = "Creating admin user with data: " . json_encode([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'is_admin' => true
        ]);

        // Check if User model exists
        if (!class_exists('App\Models\User')) {
            throw new \Exception('User model not found!');
        }

        // Check if admin user already exists
        $existingUser = \App\Models\User::where('email', $request->admin_email)->first();
        if ($existingUser) {
            $debug_messages[] = "Admin user already exists, updating...";
            $existingUser->update([
                'name' => $request->admin_name,
                'password' => Hash::make($request->admin_password),
                'is_admin' => true,
            ]);
            return $existingUser;
        }

        // Create new admin user
        $user = new \App\Models\User();
        $user->name = $request->admin_name;
        $user->email = $request->admin_email;
        $user->password = Hash::make($request->admin_password);
        $user->is_admin = true;
        $user->email_verified_at = now();
        $user->save();

        $debug_messages[] = "Admin user created successfully: " . $user->email;
        return $user;
    }

    private function checkForConflictingTables()
    {
        $conflictingTables = [];

        // Common Laravel tables that might exist
        $tablesToCheck = [
            'users',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'password_reset_tokens',
            'sessions',
            'migrations'
        ];

        try {
            foreach ($tablesToCheck as $table) {
                if (Schema::hasTable($table)) {
                    $conflictingTables[] = $table;
                }
            }
        } catch (\Exception $e) {
            // If we can't check tables, assume there might be conflicts
            // This could happen with permission issues
        }

        return $conflictingTables;
    }

    public function complete()
    {
        return view('install.complete');
    }
}
