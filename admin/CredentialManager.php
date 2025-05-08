<?php
class CredentialManager {
    private $pdo;
    private $encryptionKey;
    
    public function __construct(PDO $pdo, string $encryptionKey) {
        $this->pdo = $pdo;
        $this->encryptionKey = $encryptionKey;
    }
    
    public function createConsent(int $userId, string $ip, string $userAgent): string {
        $token = bin2hex(random_bytes(32));
        $expires = (new DateTime('+30 days'))->format('Y-m-d H:i:s');
        
        $stmt = $this->pdo->prepare("
            INSERT INTO credential_consents 
            (user_id, ip_address, user_agent, consent_token, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $ip, $userAgent, $token, $expires]);
        
        return $token;
    }
    
    public function verifyConsent(string $token): ?int {
        $stmt = $this->pdo->prepare("
            SELECT user_id FROM credential_consents 
            WHERE consent_token = ? 
            AND expires_at > NOW() 
            AND revoked = 0
        ");
        $stmt->execute([$token]);
        return $stmt->fetchColumn();
    }
    
    public function storeCredentials(int $userId, int $consentId, array $credentials): bool {
        $this->pdo->beginTransaction();
        
        try {
            foreach ($credentials as $cred) {
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
                $encrypted = openssl_encrypt(
                    $cred['password'], 
                    'aes-256-gcm', 
                    $this->encryptionKey, 
                    0, 
                    $iv,
                    $tag
                );
                
                if ($encrypted === false) {
                    throw new Exception("Encryption failed");
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO stored_credentials 
                    (user_id, consent_id, domain, username, encrypted_password, iv) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $consentId,
                    parse_url($cred['url'], PHP_URL_HOST),
                    $cred['username'],
                    base64_encode($encrypted . $tag),
                    base64_encode($iv)
                ]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Credential storage error: " . $e->getMessage());
            return false;
        }
    }
    
    public function decryptPassword(string $encrypted, string $iv): ?string {
        $iv = base64_decode($iv);
        $data = base64_decode($encrypted);
        
        // Last 16 bytes are the GCM tag
        $tag = substr($data, -16);
        $encrypted = substr($data, 0, -16);
        
        $decrypted = openssl_decrypt(
            $encrypted, 
            'aes-256-gcm', 
            $this->encryptionKey, 
            0, 
            $iv,
            $tag
        );
        
        return $decrypted !== false ? $decrypted : null;
    }
    
    public function logAccess(int $adminId, ?int $credentialId, string $reason, string $ip): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO credential_access_logs 
            (admin_id, credential_id, access_reason, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$adminId, $credentialId, $reason, $ip]);
    }
    
    public function getCredentialsByUser(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT sc.*, cc.consent_given_at 
            FROM stored_credentials sc
            JOIN credential_consents cc ON sc.consent_id = cc.id
            WHERE sc.user_id = ?
            ORDER BY sc.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllCredentials(): array {
        $stmt = $this->pdo->query("
            SELECT sc.*, u.email, cc.consent_given_at 
            FROM stored_credentials sc
            JOIN users u ON sc.user_id = u.id
            JOIN credential_consents cc ON sc.consent_id = cc.id
            ORDER BY sc.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}