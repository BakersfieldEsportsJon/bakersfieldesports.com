<?php
/**
 * Start.gg Configuration Manager
 * Handles secure storage and retrieval of API credentials
 */

class StartGGConfig {
    private $pdo;
    private $encryptionKey;

    /**
     * Constructor
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Encryption key should be stored in environment or secure config
        // For production, use: getenv('STARTGG_ENCRYPTION_KEY')
        $this->encryptionKey = $this->getEncryptionKey();
    }

    /**
     * Get encryption key from secure location
     * @return string
     */
    private function getEncryptionKey() {
        // Check environment variable first
        $key = getenv('STARTGG_ENCRYPTION_KEY');
        if ($key) {
            return $key;
        }

        // Fallback to config file (should be outside web root in production)
        $configFile = __DIR__ . '/../../config/encryption.key';
        if (file_exists($configFile)) {
            return trim(file_get_contents($configFile));
        }

        // Generate new key if none exists (first time setup)
        $key = bin2hex(random_bytes(32));

        // Try to save it
        $dir = dirname($configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($configFile, $key);
        chmod($configFile, 0600); // Restrict permissions

        return $key;
    }

    /**
     * Encrypt sensitive data
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    private function encrypt($data) {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     * @param string $data Encrypted data
     * @return string Decrypted data
     */
    private function decrypt($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }

    /**
     * Save API token (encrypted)
     * @param string $token API token
     * @return bool Success
     */
    public function saveApiToken($token) {
        try {
            $encrypted = $this->encrypt($token);

            $stmt = $this->pdo->prepare("
                UPDATE startgg_config
                SET api_token = ?, updated_at = NOW()
                WHERE id = 1
            ");

            return $stmt->execute([$encrypted]);
        } catch (PDOException $e) {
            error_log("Error saving API token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API token (decrypted)
     * @return string|null API token or null if not set
     */
    public function getApiToken() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT api_token FROM startgg_config WHERE id = 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['api_token'])) {
                return $this->decrypt($result['api_token']);
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error retrieving API token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get owner ID
     * @return string|null
     */
    public function getOwnerId() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT owner_id FROM startgg_config WHERE id = 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['owner_id'] : null;
        } catch (PDOException $e) {
            error_log("Error retrieving owner ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update sync settings
     * @param array $settings Settings to update
     * @return bool Success
     */
    public function updateSettings($settings) {
        try {
            $allowedFields = ['sync_interval', 'sync_enabled', 'oauth_client_id'];
            $updates = [];
            $params = [];

            foreach ($settings as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    // Encrypt OAuth client secret if provided
                    if ($field === 'oauth_client_secret' && !empty($value)) {
                        $value = $this->encrypt($value);
                    }
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $sql = "UPDATE startgg_config SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all configuration
     * @return array|null
     */
    public function getConfig() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM startgg_config WHERE id = 1
            ");
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config) {
                // Don't include encrypted fields in returned config
                unset($config['api_token']);
                unset($config['oauth_client_secret']);
            }

            return $config;
        } catch (PDOException $e) {
            error_log("Error retrieving config: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update last sync timestamp
     * @return bool
     */
    public function updateLastSync() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE startgg_config
                SET last_sync_at = NOW()
                WHERE id = 1
            ");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating last sync: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if sync is enabled
     * @return bool
     */
    public function isSyncEnabled() {
        $config = $this->getConfig();
        return $config && $config['sync_enabled'] == 1;
    }

    /**
     * Get sync interval in minutes
     * @return int
     */
    public function getSyncInterval() {
        $config = $this->getConfig();
        return $config ? (int)$config['sync_interval'] : 30;
    }

    /**
     * Test API connection
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function testConnection() {
        $token = $this->getApiToken();
        $ownerId = $this->getOwnerId();

        if (!$token) {
            return [
                'success' => false,
                'message' => 'API token not configured',
                'data' => null
            ];
        }

        if (!$ownerId) {
            return [
                'success' => false,
                'message' => 'Owner ID not configured',
                'data' => null
            ];
        }

        // Test with a simple query
        try {
            require_once __DIR__ . '/StartGGClient.php';
            $client = new StartGGClient($token);

            $query = 'query TestConnection { currentUser { id name } }';
            $result = $client->query($query);

            if (isset($result['data']['currentUser'])) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $result['data']['currentUser']
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid API response',
                'data' => $result
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
