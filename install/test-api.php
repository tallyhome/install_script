<?php
/**
 * Script de test pour vérifier la connexion à l'API de licence et la validation des clés
 */

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer le répertoire de logs s'il n'existe pas
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Configuration de l'API
$apiUrl = 'https://licence.myvcard.fr';
$apiKey = 'sk_wuRFNJ7fI6CaMzJptdfYhzAGW3DieKwC';
$apiSecret = 'sk_3ewgI2dP0zPyLXlHyDT1qYbzQny6H2hb';

// Clé de licence à tester (à remplacer par votre clé)
$licenseKey = isset($_POST['license_key']) ? $_POST['license_key'] : '';

// Fonction pour tester la connexion à l'API
function testApiConnection($apiUrl) {
    global $logDir;
    
    echo "<h3>Test de connexion à l'API</h3>";
    echo "<p>URL de l'API: {$apiUrl}</p>";
    
    // Tester avec cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        // Journaliser la réponse
        $responseInfo = [
            'time' => date('Y-m-d H:i:s'),
            'url' => $apiUrl,
            'http_code' => $httpCode,
            'curl_info' => $info,
            'response' => $response,
            'error' => $error
        ];
        file_put_contents($logDir . '/api_connection_test.log', json_encode($responseInfo, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        if ($response === false) {
            echo "<div class='alert alert-danger'>Erreur de connexion: " . htmlspecialchars($error) . "</div>";
            return false;
        }
        
        if ($httpCode >= 400) {
            echo "<div class='alert alert-danger'>Erreur HTTP: " . htmlspecialchars($httpCode) . "</div>";
            return false;
        }
        
        echo "<div class='alert alert-success'>Connexion réussie (HTTP Code: {$httpCode})</div>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . (strlen($response) > 500 ? '...' : '') . "</pre>";
        return true;
    } else {
        echo "<div class='alert alert-warning'>cURL n'est pas disponible. Utilisation de file_get_contents.</div>";
        
        // Tester avec file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            echo "<div class='alert alert-danger'>Erreur de connexion avec file_get_contents</div>";
            return false;
        }
        
        echo "<div class='alert alert-success'>Connexion réussie avec file_get_contents</div>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . (strlen($response) > 500 ? '...' : '') . "</pre>";
        return true;
    }
}

// Fonction pour tester la vérification de licence
function testLicenseVerification($apiUrl, $licenseKey, $apiKey, $apiSecret) {
    global $logDir;
    
    if (empty($licenseKey)) {
        echo "<div class='alert alert-warning'>Veuillez entrer une clé de licence pour la tester</div>";
        return false;
    }
    
    echo "<h3>Test de vérification de la clé de licence</h3>";
    echo "<p>Clé de licence: {$licenseKey}</p>";
    
    // Tester différents points d'entrée de l'API
    $endpoints = [
        '/api/v1/check-serial',
        '/api/check-serial.php',
        '/api/v1/verify-license',
        '/api/verify-license',
        '/api/check-license.php'
    ];
    
    $success = false;
    $responseData = null;
    
    foreach ($endpoints as $endpoint) {
        echo "<h4>Test avec le point d'entrée: {$endpoint}</h4>";
        
        $url = rtrim($apiUrl, '/') . $endpoint;
        
        // Préparer les données
        $data = [
            'serial_key' => $licenseKey,
            'domain' => $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
            'ip_address' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        
        // Ajouter les clés API si disponibles
        if ($apiKey) {
            $data['api_key'] = $apiKey;
        }
        
        if ($apiSecret) {
            $data['api_secret'] = $apiSecret;
        }
        
        // Tester avec cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            curl_close($ch);
            
            // Journaliser la réponse
            $responseInfo = [
                'time' => date('Y-m-d H:i:s'),
                'url' => $url,
                'data' => $data,
                'http_code' => $httpCode,
                'curl_info' => $info,
                'response' => $response,
                'error' => $error
            ];
            file_put_contents($logDir . '/license_verification_test.log', json_encode($responseInfo, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            
            if ($response === false) {
                echo "<div class='alert alert-danger'>Erreur de connexion: " . htmlspecialchars($error) . "</div>";
                continue;
            }
            
            if ($httpCode >= 400) {
                echo "<div class='alert alert-danger'>Erreur HTTP: " . htmlspecialchars($httpCode) . "</div>";
                continue;
            }
            
            echo "<div class='alert alert-info'>Réponse HTTP: {$httpCode}</div>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? '...' : '') . "</pre>";
            
            // Essayer de décoder la réponse JSON
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseData = $decoded;
                
                if (isset($decoded['status']) && ($decoded['status'] === 'success' || $decoded['status'] === true)) {
                    echo "<div class='alert alert-success'>Licence valide!</div>";
                    $success = true;
                    break;
                } else {
                    echo "<div class='alert alert-danger'>Licence invalide: " . htmlspecialchars($decoded['message'] ?? 'Raison inconnue') . "</div>";
                }
            } else {
                // Vérifier si la réponse contient des mots-clés positifs
                if (strpos($response, 'success') !== false || strpos($response, 'valid') !== false) {
                    echo "<div class='alert alert-success'>Licence valide (réponse non-JSON)!</div>";
                    $success = true;
                    break;
                } else {
                    echo "<div class='alert alert-warning'>Réponse non-JSON reçue</div>";
                }
            }
        } else {
            echo "<div class='alert alert-warning'>cURL n'est pas disponible. Utilisation de file_get_contents.</div>";
            
            // Tester avec file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode($data),
                    'timeout' => 30,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                echo "<div class='alert alert-danger'>Erreur de connexion avec file_get_contents</div>";
                continue;
            }
            
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? '...' : '') . "</pre>";
            
            // Essayer de décoder la réponse JSON
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseData = $decoded;
                
                if (isset($decoded['status']) && ($decoded['status'] === 'success' || $decoded['status'] === true)) {
                    echo "<div class='alert alert-success'>Licence valide!</div>";
                    $success = true;
                    break;
                } else {
                    echo "<div class='alert alert-danger'>Licence invalide: " . htmlspecialchars($decoded['message'] ?? 'Raison inconnue') . "</div>";
                }
            } else {
                // Vérifier si la réponse contient des mots-clés positifs
                if (strpos($response, 'success') !== false || strpos($response, 'valid') !== false) {
                    echo "<div class='alert alert-success'>Licence valide (réponse non-JSON)!</div>";
                    $success = true;
                    break;
                } else {
                    echo "<div class='alert alert-warning'>Réponse non-JSON reçue</div>";
                }
            }
        }
    }
    
    if ($success && $responseData) {
        echo "<h4>Détails de la licence</h4>";
        echo "<ul>";
        if (isset($responseData['data']['expiry_date'])) {
            echo "<li>Date d'expiration: " . htmlspecialchars($responseData['data']['expiry_date']) . "</li>";
        }
        if (isset($responseData['data']['token'])) {
            echo "<li>Token: " . htmlspecialchars($responseData['data']['token']) . "</li>";
        }
        echo "</ul>";
    }
    
    return $success;
}

// Afficher le formulaire HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de l'API de licence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
            max-height: 300px;
        }
        .alert {
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test de l'API de licence</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Configuration</h2>
            </div>
            <div class="card-body">
                <p><strong>URL de l'API:</strong> <?php echo htmlspecialchars($apiUrl); ?></p>
                <p><strong>Clé API:</strong> <?php echo htmlspecialchars($apiKey); ?></p>
                <p><strong>Secret API:</strong> <?php echo htmlspecialchars(substr($apiSecret, 0, 5) . '...'); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Tester une clé de licence</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="license_key" class="form-label">Clé de licence</label>
                        <input type="text" class="form-control" id="license_key" name="license_key" value="<?php echo htmlspecialchars($licenseKey); ?>" placeholder="Entrez votre clé de licence">
                    </div>
                    <button type="submit" class="btn btn-primary">Tester</button>
                </form>
            </div>
        </div>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Résultats des tests</h2>
            </div>
            <div class="card-body">
                <div class="test-results">
                    <?php
                    $apiConnected = testApiConnection($apiUrl);
                    echo "<hr>";
                    if ($apiConnected) {
                        testLicenseVerification($apiUrl, $licenseKey, $apiKey, $apiSecret);
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">Logs</h2>
            </div>
            <div class="card-body">
                <p>Les logs détaillés sont disponibles dans le répertoire <code><?php echo htmlspecialchars($logDir); ?></code>.</p>
                <ul>
                    <li><code>api_connection_test.log</code> - Tests de connexion à l'API</li>
                    <li><code>license_verification_test.log</code> - Tests de vérification de licence</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
