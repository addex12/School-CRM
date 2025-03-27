<?php
require_once 'config.php';

function getSystemSetting($key, $default = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Settings Error: " . $e->getMessage());
        return $default;
    }
}
?>