<?php
/**
 * Test de connexion à la base de données
 */

// Démarrer la session
session_start();

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer le répertoire de logs
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Initialiser la réponse
$response = [
    'status' => false,
    'message' => '',
];

// Vérifier si les données nécessaires sont présentes
if (!isset($_POST['host']) || !isset($_POST['database']) || !isset($_POST['username'])) {
    $response['message'] = 'Paramètres manquants';
    echo json_encode($response);
    exit;
}

// Récupérer les données
$host = $_POST['host'];
$database = $_POST['database'];
$username = $_POST['username'];
$password = $_POST['password'] ?? '';

// Journaliser la tentative de connexion
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'function' => 'test_db',
    'host' => $host,
    'database' => $database,
    'username' => $username,
];
file_put_contents($logDir . '/db_connection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

try {
    // Tester la connexion
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Connexion réussie
    $response['status'] = true;
    $response['message'] = 'Connexion réussie à la base de données';
    
    // Sauvegarder les informations de connexion dans la session
    $_SESSION['db_config'] = [
        'host' => $host,
        'database' => $database,
        'username' => $username,
        'password' => $password,
    ];
    
    // Marquer la connexion comme testée
    $_SESSION['db_tested'] = true;
    
} catch (PDOException $e) {
    // Erreur de connexion
    $response['status'] = false;
    $response['message'] = 'Erreur de connexion : ' . $e->getMessage();
    
    // Journaliser l'erreur
    file_put_contents($logDir . '/db_connection.log', "ERREUR: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    // Réinitialiser le statut de test
    $_SESSION['db_tested'] = false;
}

// Renvoyer la réponse
header('Content-Type: application/json');
echo json_encode($response);
