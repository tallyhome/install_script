<?php
/**
 * AJAX handler for saving admin account information
 */

session_start();

// Include utility functions
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if admin information is provided
if (!isset($_POST['project_url']) || !isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['confirm_password'])) {
    echo json_encode([
        'status' => false,
        'message' => 'All fields are required',
    ]);
    exit;
}

// Check if passwords match
if ($_POST['password'] !== $_POST['confirm_password']) {
    echo json_encode([
        'status' => false,
        'message' => 'Passwords do not match',
    ]);
    exit;
}

$adminConfig = [
    'project_url' => $_POST['project_url'],
    'email' => $_POST['email'],
    'password' => $_POST['password'],
];

// Store admin configuration in session
$_SESSION['admin_config'] = $adminConfig;
$_SESSION['admin_saved'] = true;

// Return success
echo json_encode([
    'status' => true,
    'message' => 'Admin account information saved',
]);
