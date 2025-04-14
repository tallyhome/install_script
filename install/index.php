<?php
session_start();

// Check if installation is already completed
if (file_exists(__DIR__ . '/installed.lock')) {
    header('Location: ../');
    exit;
}

// Traiter les requêtes AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // Définir le type de contenu comme JSON
    header('Content-Type: application/json');
    
    // Inclure les fonctions utilitaires
    require_once 'includes/functions.php';
    
    // Traiter l'action demandée
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'detect_project':
                // Détecter le type de projet
                $projectInfo = detectProjectType();
                
                // Pour les projets Laravel, créer le fichier .env s'il n'existe pas
                if ($projectInfo['type'] === 'laravel' && !$projectInfo['has_env']) {
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
                }
                
                // Pour les projets Laravel, installer vendor s'il n'existe pas
                if ($projectInfo['type'] === 'laravel' && !$projectInfo['has_vendor']) {
                    $projectRoot = $projectInfo['project_root'];
                    $command = "cd {$projectRoot} && composer install --no-interaction";
                    
                    // Créer le répertoire de logs
                    $logDir = __DIR__ . '/logs';
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0755, true);
                    }
                    
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
                    
                    // Journaliser le résultat
                    $logData = [
                        'time' => date('Y-m-d H:i:s'),
                        'function' => 'install_vendor',
                        'status' => $vendorInstalled ? 'success' : 'failed',
                        'output' => $output
                    ];
                    file_put_contents($logDir . '/project_detection.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
                }
                
                // Pour les projets PHP simples, indiquer que .env et vendor ne sont pas nécessaires
                if ($projectInfo['type'] === 'php') {
                    $projectInfo['env_not_needed'] = true;
                    $projectInfo['vendor_not_needed'] = true;
                }
                
                // Définir si le projet est prêt pour l'étape suivante
                $projectInfo['ready_for_next'] = ($projectInfo['type'] === 'php') || 
                                                ($projectInfo['type'] === 'laravel' && $projectInfo['has_env'] && $projectInfo['has_vendor']);
                
                // Stocker les informations dans la session
                $_SESSION['project_type'] = $projectInfo['type'];
                $_SESSION['has_env'] = $projectInfo['has_env'];
                $_SESSION['has_vendor'] = $projectInfo['has_vendor'];
                
                echo json_encode($projectInfo);
                exit;
                
            // Autres actions AJAX peuvent être ajoutées ici
            
            default:
                echo json_encode(['status' => false, 'message' => 'Action non reconnue']);
                exit;
        }
    }
    
    echo json_encode(['status' => false, 'message' => 'Aucune action spécifiée']);
    exit;
}

// Initialize session variables if not set
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
}

// Include language files
require_once 'includes/language.php';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$translations = loadTranslations($lang);

// Include utility functions
require_once 'includes/functions.php';

// Handle step navigation
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : $_SESSION['install_step'];
if ($current_step < 1 || $current_step > 5) {
    $current_step = 1;
}
$_SESSION['install_step'] = $current_step;

// Include header
include 'includes/header.php';

// Include current step
switch ($current_step) {
    case 1:
        include 'steps/step1.php';
        break;
    case 2:
        include 'steps/step2.php';
        break;
    case 3:
        include 'steps/step3.php';
        break;
    case 4:
        include 'steps/step4.php';
        break;
    case 5:
        include 'steps/step5.php';
        break;
    default:
        include 'steps/step1.php';
}

// Include footer
include 'includes/footer.php';
?>
