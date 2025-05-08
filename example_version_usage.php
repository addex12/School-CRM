<?php
require_once __DIR__ . '/src/VersionManager.php';

$pdo = new PDO('mysql:host=localhost;dbname=school_crm', 'username', 'password');
$versionManager = new VersionManager($pdo);

$cmd = $argv[1] ?? null;

switch ($cmd) {
    case 'get-version':
        echo $versionManager->getCurrentVersion() . PHP_EOL;
        break;
    case 'set-version':
        $newVersion = $argv[2] ?? null;
        if (!$newVersion) {
            echo "Usage: php example_version_usage.php set-version <version>\n";
            exit(1);
        }
        $versionManager->setCurrentVersion($newVersion);
        echo "Version updated to $newVersion\n";
        break;
    case 'add-log':
        $desc = $argv[2] ?? null;
        if (!$desc) {
            echo "Usage: php example_version_usage.php add-log <description>\n";
            exit(1);
        }
        $versionManager->addChangeLog($desc);
        echo "Change log added.\n";
        break;
    case 'list-logs':
        foreach ($versionManager->getChangeLogs() as $log) {
            echo "[{$log['changed_at']}] v{$log['version']}: {$log['description']}" . PHP_EOL;
        }
        break;
    default:
        echo "Usage:\n";
        echo "  php example_version_usage.php get-version\n";
        echo "  php example_version_usage.php set-version <version>\n";
        echo "  php example_version_usage.php add-log <description>\n";
        echo "  php example_version_usage.php list-logs\n";
        break;
}
