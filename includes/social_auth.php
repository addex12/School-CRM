<?php
require_once 'includes/db.php'; // Database connection
require_once 'vendor/autoload.php'; // Require Composer autoloader
require_once 'config.php'; // Your existing config file

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Tool\BearerTokenResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
// Social login configuration
$socialConfig = [
    'google' => [
        'clientId'     => 'YOUR_GOOGLE_CLIENT_ID',
        'clientSecret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirectUri'  => 'https://crm.flipperschool.com/login.php?provider=google', // Update this to match the Google API Console
    ]
];

/**
 * Handle social login process
 * @param string $providerName Name of the provider (google, facebook, linkedin)
 * @return array User data
 * @throws Exception If authentication fails
 */
function handleSocialLogin($providerName) {
    global $socialConfig, $pdo;

    if (!array_key_exists($providerName, $socialConfig)) {
        throw new Exception("Invalid provider");
    }

    $provider = initializeProvider($providerName);
    $scope = getProviderScope($providerName);

    // Handle the OAuth flow
    if (!isset($_GET['code'])) {
        // Step 1: Get authorization code
        $authUrl = $provider->getAuthorizationUrl(['scope' => $scope, 'state' => bin2hex(random_bytes(16))]);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: ' . $authUrl);
        exit;
    } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        // Invalid state
        unset($_SESSION['oauth2state']);
        throw new Exception("Invalid state");
    } else {
        // Step 2: Get access token and user details
        $userData = fetchUserData($provider, $providerName);

        // Step 3: Check if the email exists in the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        $user = $stmt->fetch();

        if (!$user) {
            // Notify the user that their email is not registered
            throw new Exception("Your email address is not registered in our system. Please contact support.");
        }

        return $userData;
    }
}

/**
 * Initialize the OAuth provider
 * @param string $providerName
 * @return object
 */
function initializeProvider($providerName) {
    global $socialConfig;

    if ($providerName === 'google') {
        return new Google($socialConfig['google']);
    }

    throw new Exception("Provider not supported");
}

/**
 * Get the required scope for the provider
 * @param string $providerName
 * @return array
 */
function getProviderScope($providerName) {
    if ($providerName === 'google') {
        return ['email', 'profile'];
    }

    throw new Exception("Provider not supported");
}

/**
 * Fetch user data from the provider
 * @param object $provider
 * @param string $providerName
 * @return array
 */
function fetchUserData($provider, $providerName) {
    try {
        $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        $user = $provider->getResourceOwner($token);

        $userData = [
            'id'    => $user->getId(),
            'name'  => $user->getName(),
            'email' => $user->getEmail(),
            'photo' => $user->toArray()['picture'] ?? null
        ];

        if (empty($userData['email'])) {
            throw new Exception("Email address is required but not provided by the provider");
        }

        return $userData;
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        throw new Exception("Authentication failed: " . $e->getMessage());
    }
}

/**
 * Check if a social user exists in our database
 * @param string $email User email
 * @return array|false User data if exists, false otherwise
 */
function checkSocialUserExists($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Create a new user from social login
 * @param array $userData User data from provider
 * @return int New user ID
 */
function createSocialUser($userData) {
    global $pdo;
    
    // Generate a random password
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (username, email, password, role_id, created_at, social_provider, social_id) 
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    $username = generateUsername($userData['name']);
    $role_id = 4; // Default role (User)
    
    $stmt->execute([
        $username,
        $userData['email'],
        $password,
        $role_id,
        $_GET['provider'],
        $userData['id']
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Generate a unique username from full name
 * @param string $fullName
 * @return string
 */
function generateUsername($fullName) {
    global $pdo;
    
    $base = preg_replace('/[^a-z0-9]/i', '', strtolower($fullName));
    $username = $base;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            return $username;
        }
        
        $username = $base . $counter;
        $counter++;
    }
}