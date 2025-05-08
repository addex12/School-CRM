<?php
require_once __DIR__ . '/../vendor/autoload.php';

class VersionManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCurrentVersion(): string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'app_version' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : '0.0.0';
    }

    public function setCurrentVersion(string $version)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, setting_group) 
            VALUES ('app_version', ?, 'general') 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$version]);
    }

    public function addChangeLog(string $description)
    {
        $stmt = $this->pdo->prepare("INSERT INTO change_log (version, description) VALUES (?, ?)");
        $stmt->execute([$this->getCurrentVersion(), $description]);
    }

    public function getChangeLogs(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM change_log ORDER BY changed_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
