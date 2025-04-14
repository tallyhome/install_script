<?php
/**
 * AJAX handler for license verification
 */

session_start();

// Include necessary files
require_once '../includes/functions.php';
require_once '../includes/LicenceValidator.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create log directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log request
$requestLog = [
    'time' => date('Y-m-d H:i:s'),
    'request' => $_POST,
    'server' => $_SERVER
];
file_put_contents($logDir . '/ajax_requests.log', json_encode($requestLog, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Check if request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get license key
$licenseKey = isset($_POST['license_key']) ? trim($_POST['license_key']) : '';

if (empty($licenseKey)) {
    echo json_encode([
        'status' => false,
        'message' => 'License key not provided'
    ]);
    exit;
}

// API configuration
$apiUrl = 'https://licence.myvcard.fr';
$apiKey = 'sk_wuRFNJ7fI6CaMzJptdfYhzAGW3DieKwC';
$apiSecret = 'sk_3ewgI2dP0zPyLXlHyDT1qYbzQny6H2hb';

// Create license validator instance
$validator = new LicenceValidator($apiUrl, $apiKey, $apiSecret);

try {
    // Verify license
    $result = $validator->verifyLicence($licenseKey);
    
    // Log result
    $responseLog = [
        'time' => date('Y-m-d H:i:s'),
        'license_key' => $licenseKey,
        'result' => $result
    ];
    file_put_contents($logDir . '/ajax_responses.log', json_encode($responseLog, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // If license is valid, store information in session
    if ($result['status']) {
        $_SESSION['license_key'] = $licenseKey;
        $_SESSION['license_valid'] = true;
        $_SESSION['license_expiry'] = $result['data']['expiry_date'] ?? null;
        $_SESSION['license_token'] = $result['data']['token'] ?? null;
        $_SESSION['license_project'] = $result['data']['project'] ?? null;
    }
    
    // Return result
    echo json_encode([
        'status' => $result['status'],
        'message' => $result['message'],
        'expiry_date' => $result['data']['expiry_date'] ?? null,
        'token' => $result['data']['token'] ?? null,
        'project' => $result['data']['project'] ?? null
    ]);
} catch (Exception $e) {
    // Log error
    file_put_contents($logDir . '/ajax_errors.log', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    
    // Return error
    echo json_encode([
        'status' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
