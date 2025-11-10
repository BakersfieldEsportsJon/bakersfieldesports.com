<?php
require_once __DIR__ . '/AdminLogger.php';

class User implements \JsonSerializable {
    private $id;
    private $username;
    private $email;
    private static $pdo;

    public function __construct($id, $username, $email) {
        if (is_array($id) && isset($id['id'])) {
            $this->id = $id['id'] ?? 0;
            $this->username = $id['username'] ?? '';
            $this->email = $id['email'] ?? '';
        } else {
            $this->id = $id;
            $this->username = $username;
            $this->email = $email;
        }
    }

    public static function initializeDB(): void {
        require_once __DIR__ . '/db_config.php';
        
        error_log("DB Configuration - DSN: " . DB_DSN);
        error_log("DB Configuration - Engine: " . DB_ENGINE);
        error_log("DB Configuration - User: " . DB_USER);
        error_log("DB Configuration - Host: " . DB_HOST);
        error_log("DB Configuration - Name: " . DB_NAME);
        
        try {
            error_log("Attempting PDO connection...");
            self::$pdo = new PDO(
                DB_DSN,
                DB_ENGINE === 'mysql' ? DB_USER : null,
                DB_ENGINE === 'mysql' ? DB_PASS : null,
                DB_OPTIONS
            );
            error_log("PDO connection successful");
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            AdminLogger::logError('database', "Database connection failed during initialization", [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function authenticate(string $username, string $password): ?User {
        if (!self::$pdo) {
            error_log("Initializing database connection");
            error_log("DB_HOST: " . DB_HOST);
            error_log("DB_NAME: " . DB_NAME);
            error_log("DB_USER: " . DB_USER);
            error_log("DB_PASS length: " . strlen(DB_PASS));
            error_log("DB_DSN: " . DB_DSN);
            self::initializeDB();
        }

        try {
            error_log("Starting authentication for username: " . $username);
            error_log("Preparing SQL statement");
            $stmt = self::$pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) {
                $error = self::$pdo->errorInfo()[2];
                error_log("Statement preparation failed: " . $error);
                AdminLogger::logError('database', "Statement preparation failed", [
                    'error' => $error,
                    'query' => "SELECT id, username, email, password FROM users WHERE username = ? LIMIT 1"
                ]);
                throw new RuntimeException('Prepare failed: ' . $error);
            }
            
            error_log("Executing SQL statement");
            if (!$stmt->execute([$username])) {
                $error = $stmt->errorInfo()[2];
                AdminLogger::logError('database', "Statement execution failed", [
                    'error' => $error,
                    'username' => $username
                ]);
                throw new RuntimeException('Execute failed: ' . $error);
            }
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Query result: " . print_r($userData, true));
            
            if (!is_array($userData) || !isset($userData['password'], $userData['id'], $userData['username'], $userData['email'])) {
                error_log("Invalid or missing user data");
                AdminLogger::logError('data', "Invalid user data or missing required fields", [
                    'username' => $username,
                    'fields_received' => array_keys($userData ?? [])
                ]);
                return null;
            }

            error_log("Attempting password verification");
            error_log("Stored password hash: " . ($userData['password'] ?? 'null'));
            
            if (password_verify($password, $userData['password'] ?? '') && password_needs_rehash($userData['password'], PASSWORD_ARGON2ID)) {
                error_log("Password verified but needs rehash");
                // Rehash with Argon2ID if needed
                $newHash = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = self::$pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newHash, $userData['id']]);
            }
            
            error_log("Final password verification attempt");
            if (password_verify($password, $userData['password'] ?? '')) {
                error_log("Password verified successfully");
                error_log("Creating user object");
                return new User(
                    (int)$userData['id'],
                    $userData['username'],
                    $userData['email']
                );
            }
            error_log("Password verification failed");
            return null;
        } catch (RuntimeException $e) {
            error_log("Authentication error: " . $e->getMessage());
            AdminLogger::logError('authentication', "Authentication process failed", [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return null;
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function __serialize(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email
        ];
    }

    public function __unserialize(array $data): void {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
    }

    public function jsonSerialize(): array {
        return $this->__serialize();
    }
}
