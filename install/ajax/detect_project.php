<?php
/**
 * AJAX handler for project detection
 */

// Démarrer la session
session_start();

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure les fonctions utilitaires
require_once '../includes/functions.php';

// Créer le répertoire de logs
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Détecter le type de projet
$projectInfo = detectProjectType();

// Journaliser la détection
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'function' => 'detect_project',
    'project_info' => $projectInfo
];
file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Stocker les informations dans la session
$_SESSION['project_type'] = $projectInfo['type'];
$_SESSION['has_env'] = $projectInfo['has_env'];
$_SESSION['has_vendor'] = $projectInfo['has_vendor'];

// Pour les projets Laravel, créer le fichier .env s'il n'existe pas
$envCreated = false;
$vendorInstalled = false;

if ($projectInfo['type'] === 'laravel') {
    // Créer le fichier .env s'il n'existe pas
    if (!$projectInfo['has_env']) {
        $envData = [
            'APP_NAME' => 'Laravel',
            'APP_ENV' => 'local',
            'APP_KEY' => generateRandomKey(),
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost',
            
            'LOG_CHANNEL' => 'stack',
            'LOG_DEPRECATIONS_CHANNEL' => 'null',
            'LOG_LEVEL' => 'debug',
            
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'laravel',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ];
        
        $envCreated = updateEnvFile($envData);
        $projectInfo['has_env'] = $envCreated;
        $projectInfo['env_created'] = $envCreated;
        
        // Mettre à jour la session
        $_SESSION['has_env'] = $envCreated;
        $_SESSION['env_created'] = $envCreated;
        
        // Journaliser la création du fichier .env
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'function' => 'create_env_file',
            'status' => $envCreated ? 'success' : 'failed'
        ];
        file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }
    
    // Installer les dépendances vendor si elles sont absentes
    if (!$projectInfo['has_vendor']) {
        $projectRoot = $projectInfo['project_root'];
        $command = "cd {$projectRoot} && composer install --no-interaction";
        
        // Journaliser la commande
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'function' => 'install_vendor',
            'command' => $command
        ];
        file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        // Exécuter la commande
        exec($command, $output, $returnVar);
        
        // Vérifier si l'installation a réussi
        $vendorInstalled = ($returnVar === 0 && is_dir($projectRoot . '/vendor'));
        $projectInfo['has_vendor'] = $vendorInstalled;
        $projectInfo['vendor_installed'] = $vendorInstalled;
        
        // Mettre à jour la session
        $_SESSION['has_vendor'] = $vendorInstalled;
        $_SESSION['vendor_installed'] = $vendorInstalled;
        
        // Journaliser le résultat
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'function' => 'install_vendor',
            'status' => $vendorInstalled ? 'success' : 'failed',
            'output' => $output
        ];
        file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }
}

// Définir si le projet est prêt pour l'étape suivante
// Pour un projet PHP simple, toujours prêt
// Pour Laravel, vérifier si .env existe
$projectInfo['ready_for_next'] = ($projectInfo['type'] === 'php') || 
                                 ($projectInfo['type'] === 'laravel' && $projectInfo['has_env']);

// Définir le type de contenu
header('Content-Type: application/json');

// Ajouter des informations de débogage
$projectInfo['debug'] = [
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
    'current_dir' => __DIR__,
    'project_root_exists' => is_dir($projectInfo['project_root']),
    'project_root_readable' => is_readable($projectInfo['project_root']),
    'project_root_writable' => is_writable($projectInfo['project_root']),
    'session_id' => session_id()
];

// Renvoyer la réponse
echo json_encode($projectInfo);

/**
 * Met à jour le fichier .env avec les données fournies
 *
 * @param array $envData Données à insérer dans le fichier .env
 * @return bool Succès de la mise à jour
 */
function updateEnvFile($envData) {
    $projectRoot = $_SESSION['project_root'];
    $envPath = $projectRoot . '/.env';
    
    // Créer le fichier .env s'il n'existe pas
    if (!file_exists($envPath)) {
        $content = '';
        foreach ($envData as $key => $value) {
            $content .= "{$key}={$value}\n";
        }
        file_put_contents($envPath, $content);
        return true;
    }
    
    // Mettre à jour le fichier .env existant
    $content = file_get_contents($envPath);
    foreach ($envData as $key => $value) {
        $content = preg_replace("/{$key}=.*/", "{$key}={$value}", $content);
    }
    file_put_contents($envPath, $content);
    return true;
}

/**
 * Génère une clé aléatoire pour l'application
 *
 * @return string Clé aléatoire
 */
function generateRandomKey() {
    $key = '';
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+{}[]:;<>?,./';
    for ($i = 0; $i < 32; $i++) {
        $key .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $key;
}
