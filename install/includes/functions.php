<?php
/**
 * Utility functions for the installation wizard
 */

// Include required classes
require_once __DIR__ . '/LicenceValidator.php';

/**
 * Verify license key
 *
 * @param string $licenseKey License key to verify
 * @return array Result with status, message and additional data
 */
function verifyLicense($licenseKey) {
    // Initialize response
    $response = [
        'status' => false,
        'message' => '',
        'expiry_date' => '',
    ];
    
    // Create log directory if it doesn't exist
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Log verification attempt
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'verifyLicense',
        'license_key' => $licenseKey
    ];
    file_put_contents($logDir . '/license_function.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // Test mode is completely disabled
    $testMode = false;
    
    try {
        // API configuration
        $apiUrl = 'https://licence.myvcard.fr';
        $apiKey = 'sk_wuRFNJ7fI6CaMzJptdfYhzAGW3DieKwC';
        $apiSecret = 'sk_3ewgI2dP0zPyLXlHyDT1qYbzQny6H2hb';
        
        // Create license validator
        $validator = new LicenceValidator($apiUrl, $apiKey, $apiSecret);
        
        // Verify license
        $result = $validator->verifyLicence($licenseKey);
        
        // Log API response
        file_put_contents($logDir . '/license_function.log', "API RESPONSE: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        // Format response
        $response['status'] = $result['status'];
        $response['message'] = $result['message'];
        
        // Add additional data if available
        if ($result['status'] && isset($result['data'])) {
            $response['expiry_date'] = $result['data']['expiry_date'] ?? '';
            $response['token'] = $result['data']['token'] ?? '';
            $response['secure_code'] = $result['data']['secure_code'] ?? '';
            $response['valid_until'] = $result['data']['valid_until'] ?? '';
        }
        
    } catch (Exception $e) {
        // Log error
        file_put_contents($logDir . '/license_function.log', "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
        
        // Set error response
        $response['status'] = false;
        $response['message'] = 'Error verifying license: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Detect project type (PHP, Laravel, React)
 * 
 * @return array Project information
 */
function detectProjectType() {
    $projectRoot = realpath(__DIR__ . '/../../');
    
    // Stocker le chemin du projet dans la session pour une utilisation ultérieure
    $_SESSION['project_root'] = $projectRoot;
    
    $result = [
        'type' => 'php',
        'has_env' => false,
        'has_vendor' => false,
        'project_root' => $projectRoot,
        'ready_for_next' => true
    ];
    
    // Créer le répertoire de logs
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Journaliser le début de la détection
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'detectProjectType',
        'project_root' => $projectRoot
    ];
    file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // Check for Laravel
    if (file_exists($projectRoot . '/artisan') && is_dir($projectRoot . '/routes') && file_exists($projectRoot . '/routes/web.php')) {
        $result['type'] = 'laravel';
        
        // Vérifier si .env existe
        $result['has_env'] = file_exists($projectRoot . '/.env');
        
        // Vérifier si vendor existe
        $result['has_vendor'] = is_dir($projectRoot . '/vendor');
        
        // Pour Laravel, vérifier si .env existe pour passer à l'étape suivante
        $result['ready_for_next'] = $result['has_env'];
    }
    
    // Check for React
    if (file_exists($projectRoot . '/package.json')) {
        $packageJson = file_get_contents($projectRoot . '/package.json');
        if (strpos($packageJson, '"react"') !== false) {
            $result['type'] = 'react';
        }
    }
    
    // Journaliser le résultat de la détection
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'detectProjectType',
        'result' => $result
    ];
    file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    return $result;
}

/**
 * Create or update .env file
 * 
 * @param array $data Key-value pairs to write to .env
 * @return bool Success status
 */
function updateEnvFile($data) {
    $projectRoot = realpath(__DIR__ . '/../../');
    $envPath = $projectRoot . '/.env';
    
    // If .env exists, read it
    $envContent = '';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
    }
    
    // Update or add each variable
    foreach ($data as $key => $value) {
        // Escape any quotes
        $value = str_replace('"', '\"', $value);
        
        // Check if the key exists
        if (preg_match("/^{$key}=/m", $envContent)) {
            // Replace existing value
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $envContent);
        } else {
            // Add new value
            $envContent .= PHP_EOL . "{$key}=\"{$value}\"";
        }
    }
    
    // Write back to .env
    return file_put_contents($envPath, $envContent) !== false;
}

/**
 * Test database connection
 * 
 * @param array $dbConfig Database configuration
 * @return array Response with status and message
 */
function testDatabaseConnection($dbConfig) {
    $response = [
        'status' => false,
        'message' => '',
    ];
    
    try {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ];
        
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        $response['status'] = true;
        $response['message'] = 'Database connection successful';
    } catch (PDOException $e) {
        $response['message'] = 'Database connection failed: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Generate a random key for Laravel
 * 
 * @return string Base64 encoded random key
 */
function generateRandomKey() {
    return 'base64:' . base64_encode(random_bytes(32));
}

/**
 * Create admin user
 * 
 * @param array $userData User data (email, password)
 * @return bool Success status
 */
function createAdminUser($userData) {
    $projectRoot = realpath(__DIR__ . '/../../');
    $projectType = detectProjectType();
    
    if ($projectType['type'] === 'laravel') {
        // For Laravel, use Artisan command
        $email = escapeshellarg($userData['email']);
        $password = escapeshellarg($userData['password']);
        $name = escapeshellarg('Admin');
        
        $command = "cd {$projectRoot} && php artisan tinker --execute=\"\$user = new \\App\\Models\\User(); \$user->name = {$name}; \$user->email = {$email}; \$user->password = bcrypt({$password}); \$user->save();\"";
        
        exec($command, $output, $returnVar);
        
        return $returnVar === 0;
    } else {
        // For non-Laravel projects, create a simple user file
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        $userData['created_at'] = date('Y-m-d H:i:s');
        
        $userDataJson = json_encode($userData);
        $adminFile = $projectRoot . '/admin_user.json';
        
        return file_put_contents($adminFile, $userDataJson) !== false;
    }
}

/**
 * Mark installation as complete
 * 
 * @return bool Success status
 */
function completeInstallation() {
    $lockFile = __DIR__ . '/../installed.lock';
    $content = 'Installation completed on ' . date('Y-m-d H:i:s');
    
    return file_put_contents($lockFile, $content) !== false;
}
