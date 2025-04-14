<?php
/**
 * Classe de validation de licence
 * Intègre l'API de licence pour vérifier les clés de série
 */
class LicenceValidator {
    private $apiUrl;
    private $serialKey;
    private $token;
    private $secureCode;
    private $validUntil;
    private $apiKey;
    private $apiSecret;
    
    /**
     * Constructeur
     * 
     * @param string $apiUrl URL de l'API de licence
     * @param string $serialKey Clé de série à vérifier
     * @param string $apiKey Clé API (optionnelle)
     * @param string $apiSecret Secret API (optionnel)
     */
    public function __construct(string $apiUrl, string $serialKey, string $apiKey = null, string $apiSecret = null) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->serialKey = $serialKey;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }
    
    /**
     * Vérifie la validité d'une licence
     *
     * @param string $licenceKey Clé de licence à vérifier
     * @param string $domain Domaine du site (optionnel)
     * @param string $ipAddress Adresse IP du serveur (optionnel)
     * @return array Résultat de la vérification
     */
    public function verifyLicence($licenceKey, $domain = null, $ipAddress = null)
    {
        // Initialiser le résultat
        $result = [
            'status' => false,
            'message' => 'Erreur de vérification de licence',
            'data' => null,
            'debug_info' => []
        ];

        // Vérifier si la clé de licence est fournie
        if (empty($licenceKey)) {
            $result['message'] = 'Clé de licence non fournie';
            return $result;
        }

        // Déterminer le domaine et l'adresse IP si non fournis
        $domain = $domain ?: $this->getDomain();
        $ipAddress = $ipAddress ?: $this->getIpAddress();

        // Préparer les données à envoyer
        $data = [
            'serial_key' => $licenceKey,
            'domain' => $domain,
            'ip_address' => $ipAddress
        ];

        // Ajouter les clés API si disponibles
        if ($this->apiKey) {
            $data['api_key'] = $this->apiKey;
        }
        if ($this->apiSecret) {
            $data['api_secret'] = $this->apiSecret;
        }

        // URLs à tester, dans l'ordre de préférence
        $endpoints = [
            '/api/check-serial.php'
        ];

        // Journal de débogage
        $debugLog = [];
        $debugLog[] = "Début de la vérification de licence: " . date('Y-m-d H:i:s');
        $debugLog[] = "Clé de licence: " . $licenceKey;
        $debugLog[] = "Domaine: " . $domain;
        $debugLog[] = "IP: " . $ipAddress;
        $debugLog[] = "Base URL API: " . $this->apiUrl;

        // Essayer chaque point d'entrée jusqu'à ce qu'un fonctionne
        foreach ($endpoints as $endpoint) {
            $url = rtrim($this->apiUrl, '/') . $endpoint;
            $debugLog[] = "Essai du point d'entrée: " . $url;

            // Essayer avec cURL d'abord
            if (function_exists('curl_init')) {
                $debugLog[] = "Utilisation de cURL pour la requête";
                
                try {
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
                        CURLOPT_SSL_VERIFYPEER => false, // Désactiver pour le débogage
                        CURLOPT_SSL_VERIFYHOST => 0,     // Désactiver pour le débogage
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5
                    ]);
                    
                    // Exécuter la requête
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    $info = curl_getinfo($ch);
                    
                    // Fermer la session cURL
                    curl_close($ch);
                    
                    // Journaliser la requête et la réponse
                    $requestLog = [
                        'time' => date('Y-m-d H:i:s'),
                        'url' => $url,
                        'method' => 'POST',
                        'data' => $data,
                        'headers' => [
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ]
                    ];
                    $this->logRequest($requestLog);
                    
                    $responseLog = [
                        'time' => date('Y-m-d H:i:s'),
                        'url' => $url,
                        'http_code' => $httpCode,
                        'curl_info' => $info,
                        'response' => $response,
                        'error' => $error
                    ];
                    $this->logResponse($responseLog);
                    
                    // Ajouter des informations de débogage
                    $debugLog[] = "Code HTTP: " . $httpCode;
                    $debugLog[] = "Temps de réponse: " . (isset($info['total_time']) ? round($info['total_time'] * 1000) . ' ms' : 'N/A');
                    
                    if ($error) {
                        $debugLog[] = "Erreur cURL: " . $error;
                        continue; // Essayer le prochain point d'entrée
                    }
                    
                    // Traiter la réponse
                    $decoded = json_decode($response, true);
                    
                    // Vérifier si la réponse est un JSON valide
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $debugLog[] = "Réponse JSON valide reçue";
                        $debugLog[] = "Contenu de la réponse: " . json_encode($decoded);
                        
                        // Vérifier si la licence est valide
                        if ($httpCode == 200 && isset($decoded['status']) && $decoded['status'] === 'success') {
                            $debugLog[] = "Licence valide!";
                            
                            $result['status'] = true;
                            $result['message'] = $decoded['message'] ?? 'Licence valide';
                            $result['data'] = $decoded['data'] ?? [];
                            $result['debug_info'] = $debugLog;
                            
                            return $result;
                        } else {
                            // La licence est invalide
                            $debugLog[] = "Licence invalide: " . ($decoded['message'] ?? 'Raison inconnue');
                            
                            // Si nous avons une réponse claire d'erreur, ne pas essayer d'autres points d'entrée
                            if ($httpCode == 200 && isset($decoded['status']) && $decoded['status'] === 'error') {
                                $result['status'] = false;
                                $result['message'] = $decoded['message'] ?? 'Licence invalide';
                                $result['debug_info'] = $debugLog;
                                
                                // Retourner immédiatement le résultat d'erreur
                                return $result;
                            }
                            
                            // Sinon, stocker le message d'erreur mais continuer à essayer d'autres points d'entrée
                            $result['message'] = $decoded['message'] ?? 'Licence invalide';
                        }
                    } else {
                        $debugLog[] = "Réponse non-JSON reçue";
                        
                        // Vérifier si la réponse contient des mots-clés positifs
                        if ($httpCode == 200 && (strpos($response, 'success') !== false || strpos($response, 'valid') !== false)) {
                            $debugLog[] = "Licence valide (réponse non-JSON)!";
                            
                            $result['status'] = true;
                            $result['message'] = 'Licence valide';
                            $result['debug_info'] = $debugLog;
                            
                            return $result;
                        }
                    }
                } catch (Exception $e) {
                    $debugLog[] = "Exception cURL: " . $e->getMessage();
                    // Continuer avec le prochain point d'entrée
                }
            } else {
                // Fallback à file_get_contents si cURL n'est pas disponible
                $debugLog[] = "cURL non disponible, utilisation de file_get_contents";
                
                try {
                    // Préparer le contexte
                    $options = [
                        'http' => [
                            'method' => 'POST',
                            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                            'content' => json_encode($data),
                            'timeout' => 30,
                            'ignore_errors' => true
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ];
                    
                    $context = stream_context_create($options);
                    
                    // Exécuter la requête
                    $response = @file_get_contents($url, false, $context);
                    
                    // Obtenir les en-têtes de réponse
                    $responseHeaders = $http_response_header ?? [];
                    $httpCode = 0;
                    
                    // Extraire le code HTTP
                    foreach ($responseHeaders as $header) {
                        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                            $httpCode = (int)$matches[1];
                            break;
                        }
                    }
                    
                    // Journaliser la requête et la réponse
                    $requestLog = [
                        'time' => date('Y-m-d H:i:s'),
                        'url' => $url,
                        'method' => 'POST',
                        'data' => $data,
                        'headers' => [
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ]
                    ];
                    $this->logRequest($requestLog);
                    
                    $responseLog = [
                        'time' => date('Y-m-d H:i:s'),
                        'url' => $url,
                        'http_code' => $httpCode,
                        'headers' => $responseHeaders,
                        'response' => $response
                    ];
                    $this->logResponse($responseLog);
                    
                    // Ajouter des informations de débogage
                    $debugLog[] = "Code HTTP: " . $httpCode;
                    
                    if ($response === false) {
                        $debugLog[] = "Erreur file_get_contents: Impossible de se connecter à l'API";
                        continue; // Essayer le prochain point d'entrée
                    }
                    
                    // Traiter la réponse
                    $decoded = json_decode($response, true);
                    
                    // Vérifier si la réponse est un JSON valide
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $debugLog[] = "Réponse JSON valide reçue";
                        $debugLog[] = "Contenu de la réponse: " . json_encode($decoded);
                        
                        // Vérifier si la licence est valide
                        if ($httpCode == 200 && isset($decoded['status']) && $decoded['status'] === 'success') {
                            $debugLog[] = "Licence valide!";
                            
                            $result['status'] = true;
                            $result['message'] = $decoded['message'] ?? 'Licence valide';
                            $result['data'] = $decoded['data'] ?? [];
                            $result['debug_info'] = $debugLog;
                            
                            return $result;
                        } else {
                            // La licence est invalide
                            $debugLog[] = "Licence invalide: " . ($decoded['message'] ?? 'Raison inconnue');
                            
                            // Si nous avons une réponse claire d'erreur, ne pas essayer d'autres points d'entrée
                            if ($httpCode == 200 && isset($decoded['status']) && $decoded['status'] === 'error') {
                                $result['status'] = false;
                                $result['message'] = $decoded['message'] ?? 'Licence invalide';
                                $result['debug_info'] = $debugLog;
                                
                                // Retourner immédiatement le résultat d'erreur
                                return $result;
                            }
                            
                            // Sinon, stocker le message d'erreur mais continuer à essayer d'autres points d'entrée
                            $result['message'] = $decoded['message'] ?? 'Licence invalide';
                        }
                    } else {
                        $debugLog[] = "Réponse non-JSON reçue";
                        
                        // Vérifier si la réponse contient des mots-clés positifs
                        if ($httpCode == 200 && (strpos($response, 'success') !== false || strpos($response, 'valid') !== false)) {
                            $debugLog[] = "Licence valide (réponse non-JSON)!";
                            
                            $result['status'] = true;
                            $result['message'] = 'Licence valide';
                            $result['debug_info'] = $debugLog;
                            
                            return $result;
                        }
                    }
                } catch (Exception $e) {
                    $debugLog[] = "Exception file_get_contents: " . $e->getMessage();
                    // Continuer avec le prochain point d'entrée
                }
            }
        }
        
        // Si on arrive ici, aucun point d'entrée n'a fonctionné
        $debugLog[] = "Échec de la vérification de licence après avoir essayé tous les points d'entrée";
        $result['debug_info'] = $debugLog;
        
        return $result;
    }
    
    /**
     * Récupère le code sécurisé dynamique
     * 
     * @return array Résultat avec le code sécurisé et sa validité
     */
    public function getSecureCode(): array {
        if (!$this->token) {
            return [
                'status' => false,
                'message' => 'Token non disponible. Veuillez vérifier la licence d\'abord.',
                'secure_code' => null,
                'valid_until' => null
            ];
        }
        
        // Vérifier si le code actuel est encore valide
        if ($this->secureCode && $this->validUntil && strtotime($this->validUntil) > time()) {
            return [
                'status' => true,
                'message' => 'Code sécurisé valide',
                'secure_code' => $this->secureCode,
                'valid_until' => $this->validUntil
            ];
        }
        
        $data = [
            'token' => $this->token,
            'serial_key' => $this->serialKey
        ];
        
        // Ajouter les clés API si disponibles
        if ($this->apiKey) {
            $data['api_key'] = $this->apiKey;
        }
        
        if ($this->apiSecret) {
            $data['api_secret'] = $this->apiSecret;
        }
        
        $response = $this->makeApiRequest('/api/v1/get-secure-code', 'GET', $data);
        
        if ($response && isset($response['status']) && $response['status'] === 'success') {
            $this->secureCode = $response['data']['secure_code'] ?? null;
            $this->validUntil = $response['data']['valid_until'] ?? null;
            
            return [
                'status' => true,
                'message' => $response['message'] ?? 'Code sécurisé récupéré avec succès',
                'secure_code' => $this->secureCode,
                'valid_until' => $this->validUntil
            ];
        }
        
        return [
            'status' => false,
            'message' => $response['message'] ?? 'Échec de récupération du code sécurisé',
            'secure_code' => null,
            'valid_until' => null
        ];
    }
    
    /**
     * Effectue une requête vers l'API
     * 
     * @param string $endpoint Point d'entrée de l'API
     * @param string $method Méthode HTTP (GET, POST, etc.)
     * @param array $data Données à envoyer
     * @return array|null Réponse de l'API ou null en cas d'erreur
     */
    private function makeApiRequest(string $endpoint, string $method, array $data) {
        $url = $this->apiUrl . $endpoint;
        
        // Utiliser cURL si disponible
        if (function_exists('curl_init')) {
            return $this->makeCurlRequest($url, $method, $data);
        }
        
        // Sinon, utiliser file_get_contents
        return $this->makeFileGetContentsRequest($url, $method, $data);
    }
    
    /**
     * Effectue une requête avec cURL
     */
    private function makeCurlRequest(string $url, string $method, array $data) {
        $ch = curl_init($url);
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false, // Désactiver la vérification SSL pour le débogage
            CURLOPT_SSL_VERIFYHOST => 0,     // Désactiver la vérification de l'hôte pour le débogage
            CURLOPT_VERBOSE => true,         // Mode verbeux pour le débogage
            CURLOPT_FOLLOWLOCATION => true,  // Suivre les redirections
            CURLOPT_MAXREDIRS => 5           // Nombre maximum de redirections
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        curl_setopt_array($ch, $options);
        
        // Créer le répertoire de logs s'il n'existe pas
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Capturer la sortie verbeux
        $verbose = fopen($logDir . '/curl_verbose.log', 'a+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        // Journaliser les détails de la requête avant exécution
        $requestInfo = [
            'time' => date('Y-m-d H:i:s'),
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'headers' => $options[CURLOPT_HTTPHEADER]
        ];
        file_put_contents($logDir . '/curl_requests.log', json_encode($requestInfo, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        // Journaliser les détails de la requête
        $responseInfo = [
            'time' => date('Y-m-d H:i:s'),
            'url' => $url,
            'method' => $method,
            'http_code' => $httpCode,
            'curl_info' => $info,
            'response' => $response,
            'error' => $error
        ];
        file_put_contents($logDir . '/curl_responses.log', json_encode($responseInfo, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        curl_close($ch);
        fclose($verbose);
        
        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Erreur cURL: ' . $error,
                'debug_info' => [
                    'url' => $url,
                    'method' => $method,
                    'http_code' => $httpCode,
                    'curl_info' => $info
                ]
            ];
        }
        
        if ($httpCode >= 400) {
            return [
                'status' => 'error',
                'message' => 'Erreur HTTP: ' . $httpCode,
                'response' => $response,
                'debug_info' => [
                    'url' => $url,
                    'method' => $method,
                    'http_code' => $httpCode,
                    'curl_info' => $info
                ]
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Essayer de traiter la réponse comme une réponse réussie si elle contient certains mots-clés
            if (strpos($response, 'success') !== false || strpos($response, 'valid') !== false) {
                return [
                    'status' => 'success',
                    'message' => 'Licence valide (réponse non-JSON)',
                    'data' => [
                        'token' => md5($url . time()),
                        'expiry_date' => date('Y-m-d', strtotime('+1 year'))
                    ]
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'Erreur de décodage JSON: ' . json_last_error_msg() . ' - Réponse brute: ' . substr($response, 0, 1000),
                'raw_response' => $response,
                'debug_info' => [
                    'url' => $url,
                    'method' => $method,
                    'http_code' => $httpCode,
                    'curl_info' => $info
                ]
            ];
        }
        
        return $decoded;
    }
    
    /**
     * Effectue une requête avec file_get_contents
     */
    private function makeFileGetContentsRequest(string $url, string $method, array $data) {
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => $method,
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ];
        
        if ($method === 'POST') {
            $options['http']['content'] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $context = stream_context_create($options);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de la requête file_get_contents'
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Erreur de décodage JSON: ' . json_last_error_msg()
            ];
        }
        
        return $decoded;
    }
    
    private function getDomain() {
        return $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
    
    private function getIpAddress() {
        return $_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    private function logRequest(array $data) {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/requests.log', json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }
    
    private function logResponse(array $data) {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/responses.log', json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }
}
