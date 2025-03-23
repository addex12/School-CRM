<?php

use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if the application is already installed
if (file_exists(__DIR__ . '/storage/installed')) {
    header('HTTP/1.1 403 Forbidden');
    die('Application is already installed.');
}

try {
    // Run migrations
    Artisan::call('migrate', ['--force' => true]);

    // Seed the database
    Artisan::call('db:seed', ['--force' => true]);

    // Create an installed file to prevent reinstallation
    file_put_contents(__DIR__ . '/storage/installed', 'Installed on ' . date('Y-m-d H:i:s'));

    header('HTTP/1.1 200 OK');
    echo 'Installation completed successfully.';
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Installation failed: ' . $e->getMessage();
}

$kernel->terminate($request, $status);
<?php

/**
 * Installation Script for School CRM
 *
 * This script handles the initial setup of the School CRM application.
 * It checks for existing installation, runs database migrations,
 * seeds the database, and creates an installation marker file.
 *
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the root directory
define('ROOT_DIR', __DIR__);

// Check if the application is already installed
if (file_exists(ROOT_DIR . '/storage/installed')) {
    http_response_code(403);
    die('Application is already installed.');
}

// Check if the vendor folder exists
if (!file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('Error: Vendor folder not found. Please run "composer install" first.');
}

// Include the Composer autoloader
require ROOT_DIR . '/vendor/autoload.php';

// Check if the bootstrap/app.php exists
if (!file_exists(ROOT_DIR . '/bootstrap/app.php')) {
    http_response_code(500);
    die('Error: bootstrap/app.php not found. Please make sure it exists.');
}

// Create a new Laravel application instance
try {
    $app = require_once ROOT_DIR . '/bootstrap/app.php';
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to load bootstrap/app.php: ' . $e->getMessage());
}

// Get the kernel
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to get kernel: ' . $e->getMessage());
}

// Capture the request
try {
    $request = Illuminate\Http\Request::capture();
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to capture request: ' . $e->getMessage());
}

// Handle the request
try {
    $status = $kernel->handle($request);
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to handle request: ' . $e->getMessage());
}

try {
    // Run migrations
    $kernel->call('migrate', ['--force' => true]);
    $migrationOutput = $kernel->output();

    // Seed the database
    $kernel->call('db:seed', ['--force' => true]);
    $seederOutput = $kernel->output();

    // Create an installed file to prevent reinstallation
    file_put_contents(ROOT_DIR . '/storage/installed', 'Installed on ' . date('Y-m-d H:i:s'));

    http_response_code(200);
    echo "<h1>Installation completed successfully.</h1>";
    echo "<h2>Migration Output:</h2>";
    echo "<pre>$migrationOutput</pre>";
    echo "<h2>Seeder Output:</h2>";
    echo "<pre>$seederOutput</pre>";
} catch (Exception $e) {<?php

/**
 * Installation Script for School CRM
 *
 * This script handles the initial setup of the School CRM application.
 * It checks for existing installation, runs database migrations,
 * seeds the database, and creates an installation marker file.
 *
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the root directory
define('ROOT_DIR', __DIR__);

// Check if the application is already installed
if (file_exists(ROOT_DIR . '/storage/installed')) {
    http_response_code(403);
    die('Application is already installed.');
}

// Check if the vendor folder exists
if (!file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('Error: Vendor folder not found. Please run "composer install" first.');
}

// Include the Composer autoloader
require ROOT_DIR . '/vendor/autoload.php';

// Check if the bootstrap/app.php exists
if (!file_exists(ROOT_DIR . '/bootstrap/app.php')) {
    http_response_code(500);
    die('Error: bootstrap/app.php not found. Please make sure it exists.');
}

// Create a new Laravel application instance
try {
    $app = require_once ROOT_DIR . '/bootstrap/app.php';
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to load bootstrap/app.php: ' . $e->getMessage());
}

// Get the kernel
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to get kernel: ' . $e->getMessage());
}

// Capture the request
try {
    $request = Illuminate\Http\Request::capture();
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to capture request: ' . $e->getMessage());
}

// Handle the request
try {
    $status = $kernel->handle($request);
} catch (Exception $e) {
    http_response_code(500);
    die('Error: Failed to handle request: ' . $e->getMessage());
}

try {
    // Run migrations
    $kernel->call('migrate', ['--force' => true]);
    $migrationOutput = $kernel->output();

    // Seed the database
    $kernel->call('db:seed', ['--force' => true]);
    $seederOutput = $kernel->output();

    // Create an installed file to prevent reinstallation
    file_put_contents(ROOT_DIR . '/storage/installed', 'Installed on ' . date('Y-m-d H:i:s'));

    http_response_code(200);
    echo "<h1>Installation completed successfully.</h1>";
    echo "<h2>Migration Output:</h2>";
    echo "<pre>$migrationOutput</pre>";
    echo "<h2>Seeder Output:</h2>";
    echo "<pre>$seederOutput</pre>";
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>Installation failed:</h1>';
    echo '<p>Error: ' . $e->getMessage() . '</p>';
    echo '<h2>Stack Trace:</h2>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
} finally {
    $kernel->terminate($request, $status);<?php
    
    /**
     * Installation Script for School CRM
     *
     * This script handles the initial setup of the School CRM application.
     * It checks for existing installation, runs database migrations,
     * seeds the database, and creates an installation marker file.
     *
     * Developer: Adugna Gizaw
     * Email: gizawadugna@gmail.com
     * Phone: +251925582067
     */
    
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Define the root directory
    define('ROOT_DIR', __DIR__);
    
    // Check if the application is already installed
    if (file_exists(ROOT_DIR . '/storage/installed')) {
        http_response_code(403);
        die('Application is already installed.');
    }
    
    // Check if the vendor folder exists
    if (!file_exists(ROOT_DIR . '/vendor/autoload.php')) {
        http_response_code(500);
        die('Error: Vendor folder not found. Please run "composer install" first.');
    }
    
    // Include the Composer autoloader
    require ROOT_DIR . '/vendor/autoload.php';
    
    // Check if the bootstrap/app.php exists
    if (!file_exists(ROOT_DIR . '/bootstrap/app.php')) {
        http_response_code(500);
        die('Error: bootstrap/app.php not found. Please make sure it exists.');
    }
    
    // Create a new Laravel application instance
    try {
        $app = require_once ROOT_DIR . '/bootstrap/app.php';
    } catch (Exception $e) {
        http_response_code(500);
        die('Error: Failed to load bootstrap/app.php: ' . $e->getMessage());
    }
    
    // Get the kernel
    try {
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    } catch (Exception $e) {
        http_response_code(500);
        die('Error: Failed to get kernel: ' . $e->getMessage());
    }
    
    // Capture the request
    try {
        $request = Illuminate\Http\Request::capture();
    } catch (Exception $e) {
        http_response_code(500);
        die('Error: Failed to capture request: ' . $e->getMessage());
    }
    
    // Handle the request
    try {
        $status = $kernel->handle($request);
    } catch (Exception $e) {
        http_response_code(500);
        die('Error: Failed to handle request: ' . $e->getMessage());
    }
    
    try {
        // Run migrations
        $kernel->call('migrate', ['--force' => true]);
        $migrationOutput = $kernel->output();
    
        // Seed the database
        $kernel->call('db:seed', ['--force' => true]);
        $seederOutput = $kernel->output();
    
        // Create an installed file to prevent reinstallation
        file_put_contents(ROOT_DIR . '/storage/installed', 'Installed on ' . date('Y-m-d H:i:s'));
    
        http_response_code(200);
        echo "<h1>Installation completed successfully.</h1>";
        echo "<h2>Migration Output:</h2>";
        echo "<pre>$migrationOutput</pre>";
        echo "<h2>Seeder Output:</h2>";
        echo "<pre>$seederOutput</pre>";
    } catch (Exception $e) {
        http_response_code(500);
        echo '<h1>Installation failed:</h1>';
        echo '<p>Error: ' . $e->getMessage() . '</p>';
        echo '<h2>Stack Trace:</h2>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } finally {
        $kernel->terminate($request, $status);
    }
    
}

    http_response_code(500);
    echo '<h1>Installation failed:</h1>';
    echo '<p>Error: ' . $e->getMessage() . '</p>';
    echo '<h2>Stack Trace:</h2>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
} finally {
    $kernel->terminate($request, $status);
}
