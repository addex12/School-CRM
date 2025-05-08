<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/CredentialManager.php';

header('Content-Type: application/json');

// Initialize credential manager with encryption key from config
$credentialManager = new CredentialManager($pdo, 'adugna');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            handlePostRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handlePostRequest() {
    global $credentialManager;
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid input');
    }
    
    // Check for consent token
    if (empty($data['consent_token'])) {
        throw new Exception('Consent token required');
    }
    
    $userId = $credentialManager->verifyConsent($data['consent_token']);
    if (!$userId) {
        throw new Exception('Invalid or expired consent');
    }
    
    // Get consent record
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM nconsent WHERE consent_token = ?");
    $stmt->execute([$data['consent_token']]);
    $consentId = $stmt->fetchColumn();
    
    if (!$credentialManager->storeCredentials(
        $userId,
        $consentId,
        $data['credentials']
    )) {
        throw new Exception('Failed to store credentials');
    }
    
    echo json_encode(['success' => true]);
}