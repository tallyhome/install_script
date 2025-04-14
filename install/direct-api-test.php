<?php
/**
 * Test direct de l'API de licence
 * Ce script teste directement l'API sans passer par la classe LicenceValidator
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer le répertoire de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Configuration de l'API
$apiUrl = 'https://licence.myvcard.fr';
$apiKey = 'sk_wuRFNJ7fI6CaMzJptdfYhzAGW3DieKwC';
$apiSecret = 'sk_3ewgI2dP0zPyLXlHyDT1qYbzQny6H2hb';

// Point d'entrée de l'API qui fonctionne selon les logs précédents
$endpoint = '/api/check-serial.php';
$url = rtrim($apiUrl, '/') . $endpoint;

// Fonction pour tester une clé de licence
function testLicenseKey($url, $licenseKey, $apiKey, $apiSecret) {
    // Préparer les données
    $data = [
        'serial_key' => $licenseKey,
        'domain' => $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
        'ip_address' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'api_key' => $apiKey,
        'api_secret' => $apiSecret
    ];
    
    // Initialiser cURL
    $ch = curl_init($url);
    
    // Configurer cURL
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_VERBOSE => true
    ]);
    
    // Capturer les informations détaillées
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    // Obtenir les informations détaillées
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    // Fermer la session cURL
    curl_close($ch);
    
    // Décoder la réponse
    $decoded = json_decode($response, true);
    
    // Préparer le résultat
    $result = [
        'license_key' => $licenseKey,
        'url' => $url,
        'http_code' => $httpCode,
        'curl_info' => $info,
        'response_raw' => $response,
        'response_decoded' => $decoded,
        'error' => $error,
        'verbose_log' => $verboseLog
    ];
    
    return $result;
}

// Tester avec différentes clés de licence
$testKeys = [
    'TEST-VALID-KEY1-XXXX', // Clé de test valide (à remplacer par une vraie clé si disponible)
    'INVALID-KEY-TEST-XXXX', // Clé de test invalide
    isset($_GET['key']) ? $_GET['key'] : '' // Clé fournie via l'URL
];

// Afficher la page HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test direct de l'API de licence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 300px;
            overflow: auto;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test direct de l'API de licence</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Configuration</h2>
            </div>
            <div class="card-body">
                <p><strong>URL de l'API:</strong> <?php echo htmlspecialchars($url); ?></p>
                <p><strong>Clé API:</strong> <?php echo htmlspecialchars($apiKey); ?></p>
                <p><strong>Secret API:</strong> <?php echo htmlspecialchars(substr($apiSecret, 0, 5) . '...'); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Tester une clé de licence</h2>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="mb-3">
                        <label for="key" class="form-label">Clé de licence</label>
                        <input type="text" class="form-control" id="key" name="key" value="<?php echo htmlspecialchars($_GET['key'] ?? ''); ?>" placeholder="Entrez une clé de licence à tester">
                    </div>
                    <button type="submit" class="btn btn-primary">Tester</button>
                </form>
            </div>
        </div>
        
        <?php foreach ($testKeys as $key): ?>
            <?php if (!empty($key)): ?>
                <?php $result = testLicenseKey($url, $key, $apiKey, $apiSecret); ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Résultat pour la clé: <?php echo htmlspecialchars($key); ?></h3>
                    </div>
                    <div class="card-body">
                        <h4>Résumé</h4>
                        <ul>
                            <li><strong>Code HTTP:</strong> <?php echo $result['http_code']; ?></li>
                            <li><strong>Statut:</strong> 
                                <?php if ($result['http_code'] == 200 && isset($result['response_decoded']['status']) && $result['response_decoded']['status'] === 'success'): ?>
                                    <span class="badge bg-success">Licence valide</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Licence invalide</span>
                                <?php endif; ?>
                            </li>
                            <li><strong>Message:</strong> <?php echo htmlspecialchars($result['response_decoded']['message'] ?? 'N/A'); ?></li>
                        </ul>
                        
                        <h4>Réponse brute</h4>
                        <pre><?php echo htmlspecialchars($result['response_raw']); ?></pre>
                        
                        <h4>Réponse décodée</h4>
                        <pre><?php echo htmlspecialchars(json_encode($result['response_decoded'], JSON_PRETTY_PRINT) ?: 'N/A'); ?></pre>
                        
                        <?php if (!empty($result['error'])): ?>
                            <h4>Erreur cURL</h4>
                            <pre class="text-danger"><?php echo htmlspecialchars($result['error']); ?></pre>
                        <?php endif; ?>
                        
                        <h4>Détails de la requête</h4>
                        <pre><?php echo htmlspecialchars(json_encode($result['curl_info'], JSON_PRETTY_PRINT)); ?></pre>
                        
                        <h4>Log détaillé</h4>
                        <pre><?php echo htmlspecialchars($result['verbose_log']); ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</body>
</html>
