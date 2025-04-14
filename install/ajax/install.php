<?php
/**
 * AJAX handler for final installation
 */

session_start();

// Include utility functions
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Créer le répertoire de logs s'il n'existe pas
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Journaliser le début de l'installation
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'function' => 'install',
    'session' => $_SESSION
];
file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Check if all required steps have been completed
if (!isset($_SESSION['license_verified']) || !$_SESSION['license_verified']) {
    echo json_encode([
        'status' => false,
        'message' => 'License verification is required',
    ]);
    exit;
}

if (!isset($_SESSION['db_tested']) || !$_SESSION['db_tested']) {
    echo json_encode([
        'status' => false,
        'message' => 'Database configuration is required',
    ]);
    exit;
}

if (!isset($_SESSION['admin_saved']) || !$_SESSION['admin_saved']) {
    echo json_encode([
        'status' => false,
        'message' => 'Admin account configuration is required',
    ]);
    exit;
}

// Perform installation steps
$installationSteps = [];

// Récupérer le type de projet
$projectType = $_SESSION['project_type'] ?? 'php';
$projectRoot = realpath(__DIR__ . '/../../../');

// 1. Vérifier et créer le fichier .env pour les projets Laravel si nécessaire
if ($projectType === 'laravel' && (!isset($_SESSION['has_env']) || !$_SESSION['has_env'])) {
    $envCreated = false;
    
    // Créer le fichier .env avec les informations de base de données
    $dbConfig = $_SESSION['db_config'] ?? [];
    $envData = [
        'APP_NAME' => 'Laravel',
        'APP_ENV' => 'production',
        'APP_KEY' => generateRandomKey(),
        'APP_DEBUG' => 'false',
        'APP_URL' => $_SESSION['admin_config']['project_url'] ?? 'http://localhost',
        
        'LOG_CHANNEL' => 'stack',
        'LOG_DEPRECATIONS_CHANNEL' => 'null',
        'LOG_LEVEL' => 'debug',
    ];
    
    // Ajouter les informations de base de données si disponibles
    if (!empty($dbConfig)) {
        $envData['DB_CONNECTION'] = 'mysql';
        $envData['DB_HOST'] = $dbConfig['host'] ?? '127.0.0.1';
        $envData['DB_PORT'] = $dbConfig['port'] ?? '3306';
        $envData['DB_DATABASE'] = $dbConfig['database'] ?? 'laravel';
        $envData['DB_USERNAME'] = $dbConfig['username'] ?? 'root';
        $envData['DB_PASSWORD'] = $dbConfig['password'] ?? '';
    }
    
    $envCreated = updateEnvFile($envData);
    $_SESSION['has_env'] = $envCreated;
    
    $installationSteps[] = [
        'step' => 'env_creation',
        'status' => $envCreated,
        'message' => $envCreated ? 'Environment file created successfully' : 'Failed to create environment file',
    ];
    
    // Journaliser la création du fichier .env
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'create_env_file',
        'status' => $envCreated ? 'success' : 'failed'
    ];
    file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// 2. Vérifier et installer vendor pour les projets Laravel si nécessaire
if ($projectType === 'laravel' && (!isset($_SESSION['has_vendor']) || !$_SESSION['has_vendor'])) {
    $vendorInstalled = false;
    
    // Installer les dépendances vendor
    $command = "cd {$projectRoot} && composer install --no-interaction";
    
    // Journaliser la commande
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'install_vendor',
        'command' => $command
    ];
    file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // Exécuter la commande
    exec($command, $output, $returnVar);
    
    // Vérifier si l'installation a réussi
    $vendorInstalled = ($returnVar === 0 && is_dir($projectRoot . '/vendor'));
    $_SESSION['has_vendor'] = $vendorInstalled;
    
    $installationSteps[] = [
        'step' => 'vendor_installation',
        'status' => $vendorInstalled,
        'message' => $vendorInstalled ? 'Vendor dependencies installed successfully' : 'Failed to install vendor dependencies',
        'output' => $output
    ];
    
    // Journaliser le résultat
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'install_vendor',
        'status' => $vendorInstalled ? 'success' : 'failed',
        'output' => $output
    ];
    file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// 3. Create admin user
$adminCreated = false;
if (isset($_SESSION['admin_config']) && is_array($_SESSION['admin_config'])) {
    $adminCreated = createAdminUser($_SESSION['admin_config']);
}
$installationSteps[] = [
    'step' => 'admin_creation',
    'status' => $adminCreated,
    'message' => $adminCreated ? 'Admin user created successfully' : 'Failed to create admin user',
];

// 4. For Laravel projects, run migrations if possible
$migrationsRun = false;
if ($projectType === 'laravel' && isset($_SESSION['has_vendor']) && $_SESSION['has_vendor']) {
    $command = "cd {$projectRoot} && php artisan migrate --force";
    
    // Journaliser la commande
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'run_migrations',
        'command' => $command
    ];
    file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    exec($command, $output, $returnVar);
    
    $migrationsRun = ($returnVar === 0);
    $installationSteps[] = [
        'step' => 'migrations',
        'status' => $migrationsRun,
        'message' => $migrationsRun ? 'Migrations run successfully' : 'Failed to run migrations',
        'output' => $output
    ];
    
    // Journaliser le résultat
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'function' => 'run_migrations',
        'status' => $migrationsRun ? 'success' : 'failed',
        'output' => $output
    ];
    file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // Exécuter les seeders si les migrations ont réussi
    if ($migrationsRun) {
        $command = "cd {$projectRoot} && php artisan db:seed --force";
        
        // Journaliser la commande
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'function' => 'run_seeders',
            'command' => $command
        ];
        file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        exec($command, $output, $returnVar);
        
        $seedersRun = ($returnVar === 0);
        $installationSteps[] = [
            'step' => 'seeders',
            'status' => $seedersRun,
            'message' => $seedersRun ? 'Database seeders run successfully' : 'Failed to run database seeders',
            'output' => $output
        ];
        
        // Journaliser le résultat
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'function' => 'run_seeders',
            'status' => $seedersRun ? 'success' : 'failed',
            'output' => $output
        ];
        file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }
}

// 5. Mark installation as complete
$installationCompleted = completeInstallation();
$installationSteps[] = [
    'step' => 'installation_completed',
    'status' => $installationCompleted,
    'message' => $installationCompleted ? 'Installation marked as complete' : 'Failed to mark installation as complete',
];

// Check if all steps were successful
$allSuccessful = true;
foreach ($installationSteps as $step) {
    if (!$step['status']) {
        $allSuccessful = false;
        break;
    }
}

// Journaliser le résultat final
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'function' => 'installation_completed',
    'status' => $allSuccessful ? 'success' : 'failed',
    'steps' => $installationSteps
];
file_put_contents($logDir . '/installation.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Return result
echo json_encode([
    'status' => $allSuccessful,
    'message' => $allSuccessful ? 'Installation completed successfully' : 'Installation failed',
    'steps' => $installationSteps,
    'project_type' => $projectType
]);
