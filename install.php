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
