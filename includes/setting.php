<?php
function getSystemSetting($key, $default = null) {
    global $pdo;
    
    try {
        // Check if settings table exists
        $tableExists = $pdo->query("SHOW TABLES LIKE 'system_settings'")->rowCount() > 0;
        
        if (!$tableExists) return $default;
        
        $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['value'] : $default;
    } catch (PDOException $e) {
        error_log("Settings Error: " . $e->getMessage());
        return $default;
    }
}