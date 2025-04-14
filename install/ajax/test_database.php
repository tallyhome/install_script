<?php
/**
 * AJAX handler for database connection testing
 */

session_start();

// Include utility functions
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if database configuration is provided
if (!isset($_POST['host']) || !isset($_POST['port']) || !isset($_POST['database']) || !isset($_POST['username'])) {
    echo json_encode([
        'status' => false,
        'message' => 'All database fields are required',
    ]);
    exit;
}

$dbConfig = [
    'host' => $_POST['host'],
    'port' => $_POST['port'],
    'database' => $_POST['database'],
    'username' => $_POST['username'],
    'password' => $_POST['password'] ?? '',
];

// Test database connection
$result = testDatabaseConnection($dbConfig);

// Store database configuration in session if connection is successful
if ($result['status']) {
    $_SESSION['db_config'] = $dbConfig;
    $_SESSION['db_tested'] = true;
    
    // Update .env file if it exists
    if (isset($_SESSION['has_env']) && $_SESSION['has_env']) {
        $envData = [
            'DB_HOST' => $dbConfig['host'],
            'DB_PORT' => $dbConfig['port'],
            'DB_DATABASE' => $dbConfig['database'],
            'DB_USERNAME' => $dbConfig['username'],
            'DB_PASSWORD' => $dbConfig['password'],
        ];
        
        updateEnvFile($envData);
    }
}

// Return result
echo json_encode($result);
