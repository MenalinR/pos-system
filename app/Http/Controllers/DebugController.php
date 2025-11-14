<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function test()
    {
        echo "<h1>Debug Test Page</h1>";
        
        // Test basic PHP
        echo "<h2>PHP Version: " . phpversion() . "</h2>";
        
        // Test Laravel
        echo "<h2>Laravel Version: " . app()->version() . "</h2>";
        
        // Test database connection
        try {
            \DB::connection()->getPdo();
            echo "<p style='color: green;'>✓ Database Connected</p>";
        } catch (\Exception $e) {
            echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
        }
        
        // Test file permissions
        $paths = [
            '.env' => base_path('.env'),
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        
        foreach ($paths as $name => $path) {
            if (file_exists($path)) {
                $writable = is_writable($path);
                $color = $writable ? 'green' : 'red';
                echo "<p style='color: $color;'>" . ($writable ? '✓' : '✗') . " $name is " . ($writable ? 'writable' : 'NOT writable') . "</p>";
            } else {
                echo "<p style='color: orange;'>? $name does not exist</p>";
            }
        }
        
        die();
    }
}