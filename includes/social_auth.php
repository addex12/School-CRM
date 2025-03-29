<?php
require_once 'vendor/autoload.php'; // Require Composer autoloader
require_once 'config.php'; // Your existing config file

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use Happyr\LinkedIn\LinkedIn;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use League\OAuth2\Client\Provider\LinkedInResourceOwnerInterface;
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
        'redirectUri'  => 'http://gmail.com/login.php?provider=google',
    ],
    'facebook' => [
        'clientId'     => 'YOUR_FACEBOOK_APP_ID',
        'clientSecret' => 'YOUR_FACEBOOK_APP_SECRET',
        'redirectUri'  => 'http://facebook.com/login.php?provider=facebook',
        'graphApiVersion' => 'v12.0',
    ],
    'linkedin' => [
        'clientId'     => 'YOUR_LINKEDIN_CLIENT_ID',
        'clientSecret' => 'YOUR_LINKEDIN_CLIENT_SECRET',
        'redirectUri'  => 'http://crm.linkedin.com/login.php?provider=linkedin',
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
    
    // Initialize the provider
    switch ($providerName) {
        case 'google':
            $provider = new Google($socialConfig['google']);
            $scope = ['email', 'profile'];
            break;
            
        case 'facebook':
            $provider = new Facebook($socialConfig['facebook']);
            $scope = ['email', 'public_profile'];
            break;
            
        case 'linkedin':
            $provider = new LinkedIn($socialConfig['linkedin']['clientId'], $socialConfig['linkedin']['clientSecret']);
            $scope = ['r_liteprofile', 'r_emailaddress'];
            break;
            
        default:
            throw new Exception("Provider not supported");
    }
    
    // Handle the OAuth flow
    if (!isset($_GET['code'])) {
        // Step 1: Get authorization code
        $authUrl = $provider->getAuthorizationUrl([
            'scope' => $scope,
            'state' => bin2hex(random_bytes(16))
        ]);
        $_SESSION['oauth2state'] = $provider->getAuthorizationUrl(['scope' => $scope, 'state' => bin2hex(random_bytes(16))]);
        header('Location: ' . $authUrl);
        exit;
        
    } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        // Invalid state
        unset($_SESSION['oauth2state']);
        throw new Exception("Invalid state");
        
    } else {
        // Step 2: Get access token
        try {
            $token = $provider->getAccessToken();
            
            // Step 3: Get user details
            switch ($providerName) {
                case 'google':
                    $user = $provider->getResourceOwner($token);
                    $userData = [
                        'id'    => $user->getId(),
                        'name'  => $user->getName(),
                        'email' => $user->getEmail(),
                        'photo' => $user->toArray()['picture'] ?? null
                    ];
                    break;
                    
                case 'facebook':
                    $user = $provider->getResourceOwner($token);
                    $userData = [
                        'id'    => $user->getId(),
                        'name'  => $user->getName(),
                        'email' => $user->getEmail(),
                        'photo' => $user->toArray()['picture']['data']['url'] ?? null
                    ];
                    break;
                    
                case 'linkedin':
                    // LinkedIn requires separate API calls for email and profile
                    $profile = $provider->getResourceOwner($token);
                    $emailResponse = $provider->getAuthenticatedRequest(
                        'GET',
                        'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))',
                        $token
                    );
                    $emailData = $provider->getParsedResponse($emailResponse);
                    
                    $userData = [
                        'id'    => $profile->getId(),
                        'name'  => $profile->getFirstName() . ' ' . $profile->getLastName(),
                        'email' => $emailData['elements'][0]['handle~']['emailAddress'],
                        'photo' => null // LinkedIn doesn't provide photo in basic scope
                    ];
                    break;
            }
            
            // Verify we got an email (required)
            if (empty($userData['email'])) {
                throw new Exception("Email address is required but not provided by the provider");
            }
            
            return $userData;
            
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
        }
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