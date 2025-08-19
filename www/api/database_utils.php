<?php
// -------------------------------------------------------------------
// database_utils.php
// -------------------------------------------------------------------
// Database utility functions for both MySQL and Supabase

require_once __DIR__ . '/db_connection.php';

class DatabaseUtils {
    private static $pdo;
    private static $db_type;
    
    public static function init() {
        global $pdo;
        self::$pdo = $pdo;
        self::$db_type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
    }
    
    /**
     * Get a user by username or email
     */
    public static function getUserByIdentifier($identifier) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare("
                SELECT id, username, email, password_hash, token
                FROM users
                WHERE username = :ident OR email = :ident
                LIMIT 1
            ");
            $stmt->execute([':ident' => $identifier]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Failed to get user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new user
     */
    public static function createUser($username, $email, $password_hash) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare("
                INSERT INTO users (username, email, password_hash)
                VALUES (:username, :email, :password_hash)
            ");
            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash
            ]);
            
            if ($result) {
                return self::$pdo->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Failed to create user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate a user token
     */
    public static function validateToken($token) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare('SELECT username FROM users WHERE token = ?');
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Failed to validate token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate a token from the api_tokens table and return the linked user row (username)
     */
    public static function validateApiToken($token) {
        self::init();

        try {
            $stmt = self::$pdo->prepare(
                'SELECT u.id AS id, u.username AS username FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW()) LIMIT 1'
            );
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Failed to validate api token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create an API token for a user with optional TTL in days. Returns token string or false.
     */
    public static function createApiToken($user_id, $ttl_days = 30) {
        self::init();

        try {
            // Ensure table exists
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(128) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL,
                INDEX(user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $token = bin2hex(random_bytes(32));
            $expires_at = null;
            if ($ttl_days > 0) {
                $expires_at = (new DateTime('+'.intval($ttl_days).' days'))->format('Y-m-d H:i:s');
            }

            $stmt = self::$pdo->prepare('INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, $token, $expires_at]);

            return $token;
        } catch (Exception $e) {
            error_log('Failed to create api token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * List API tokens for a given user_id
     */
    public static function listApiTokens($user_id) {
        self::init();

        try {
            $stmt = self::$pdo->prepare('SELECT id, token, created_at, expires_at FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC');
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Failed to list api tokens: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke an API token belonging to a user. Returns true on success.
     */
    public static function revokeApiToken($user_id, $token) {
        self::init();

        try {
            $stmt = self::$pdo->prepare('DELETE FROM api_tokens WHERE user_id = ? AND token = ?');
            $stmt->execute([$user_id, $token]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Failed to revoke api token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if username exists
     */
    public static function usernameExists($username) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Failed to check username: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get invite keys for a user
     */
    public static function getUserInvites($username) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare("SELECT `key`, `uses`, `active` FROM invitekeys WHERE username = :username");
            $stmt->execute(['username' => $username]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get user invites: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check invite key validity
     */
    public static function checkInviteKey($invite_key) {
        self::init();
        
        try {
            $stmt = self::$pdo->prepare("SELECT uses_remaining FROM invite_keys WHERE invite_key = ?");
            $stmt->execute([$invite_key]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return ['valid' => false, 'error' => 'Invite key not found'];
            }
            
            if ($row['uses_remaining'] <= 0) {
                return ['valid' => false, 'error' => 'Invite key has no remaining uses'];
            }
            
            return ['valid' => true];
        } catch (Exception $e) {
            error_log("Failed to check invite key: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Database error'];
        }
    }
    
    /**
     * Create a new invite key
     */
    public static function createInviteKey($invite_key, $created_by, $uses_remaining) {
        self::init();
        
        try {
            // Check if key already exists
            $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM invite_keys WHERE invite_key = ?");
            $stmt->execute([$invite_key]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Invite key already exists'];
            }
            
            // Insert new key
            $stmt = self::$pdo->prepare("
                INSERT INTO invite_keys (invite_key, created_by, uses_remaining)
                VALUES (:invite_key, :created_by, :uses_remaining)
            ");
            $result = $stmt->execute([
                ':invite_key' => $invite_key,
                ':created_by' => $created_by,
                ':uses_remaining' => $uses_remaining
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'invite_key' => $invite_key,
                    'created_by' => $created_by,
                    'uses_remaining' => $uses_remaining
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to create invite key'];
        } catch (Exception $e) {
            error_log("Failed to create invite key: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }
    
    /**
     * Get database type
     */
    public static function getDatabaseType() {
        return self::$db_type;
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        self::init();
        
        try {
            if (self::$db_type === 'supabase') {
                // For Supabase, try a simple query
                $stmt = self::$pdo->prepare("SELECT 1 as test");
                $stmt->execute();
                return $stmt->fetch() !== false;
            } else {
                // For MySQL, try a simple query
                $stmt = self::$pdo->prepare("SELECT 1 as test");
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        } catch (Exception $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            return false;
        }
    }
}
?>