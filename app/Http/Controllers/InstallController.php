<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class InstallController extends Controller
{
    public function index()
    {
        return redirect()->route('install.requirements');
    }

    public function requirements()
    {
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

    public function administrator()
    {
        return view('install.administrator');
    }

    public function showInstall()
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

            // STEP 1: Validate input
            $debug_messages[] = "STEP 1: Validating input...";
            $validator = Validator::make($request->all(), [
                'site_name' => 'required|string|max:255',
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email',
                'admin_password' => 'required|min:8',
                'db_connection' => 'required',
                'db_host' => 'required',
                'db_port' => 'required',
                'db_name' => 'required',
                'db_username' => 'required',
                'db_password' => 'nullable',
            ]);

            if ($validator->fails()) {
                $debug_messages[] = "VALIDATION FAILED: " . json_encode($validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
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

            // STEP 5: Run migrations
            $debug_messages[] = "STEP 5: Running migrations...";
            $migrationOutput = Artisan::call('migrate', ['--force' => true]);
            $debug_messages[] = "✓ Migrations completed. Exit code: " . $migrationOutput;

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
            $debug_messages[] = "Trace: " . $e->getTraceAsString();

            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'debug' => $debug_messages
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
            'APP_URL' => 'http://localhost:8000',
            
            'DB_CONNECTION' => $request->db_connection,
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_name,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ? '"' . addslashes($request->db_password) . '"' : '""',
        ];

        $debug_messages[] = "Updates to apply: " . json_encode($updates);

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

    public function complete()
    {
        return view('install.complete');
    }
}