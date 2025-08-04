<?php
// -------------------------------------------------------------------
// db_connection.php
// -------------------------------------------------------------------
// Secure database connection using environment variables
// Supports both MySQL and Supabase databases

// Load bootstrap (includes autoloader and environment variables)
require_once __DIR__ . '/../bootstrap.php';

// Database type configuration
$db_type = strtolower($_ENV['DB_TYPE'] ?? 'mysql');

// Define invite key constant
define('VALID_INVITE_KEY', $_ENV['VALID_INVITE_KEY'] ?? 'default-invite-key');

if ($db_type === 'supabase') {
    // -------------------------------------------------------------------
    // SUPABASE CONFIGURATION
    // -------------------------------------------------------------------
    
    // Supabase configuration from environment variables
    $supabase_url = $_ENV['SUPABASE_URL'] ?? '';
    $supabase_key = $_ENV['SUPABASE_ANON_KEY'] ?? '';
    
    if (empty($supabase_url) || empty($supabase_key)) {
        error_log("Supabase configuration missing: URL or ANON_KEY not set");
        http_response_code(500);
        exit("Database configuration error.");
    }
    
    // Define constants for Supabase
    define('DB_TYPE', 'supabase');
    define('SUPABASE_URL', $supabase_url);
    define('SUPABASE_ANON_KEY', $supabase_key);
    
    try {
        // Initialize Supabase client
        use RafaelWendel\PhpSupabase\SupabaseClient;
        $supabase = new SupabaseClient($supabase_url, $supabase_key);
        
        // Create a PDO-like wrapper for Supabase to maintain compatibility
        $pdo = new SupabasePDOWrapper($supabase);
        
    } catch (Exception $e) {
        error_log("Supabase connection failed: " . $e->getMessage());
        http_response_code(500);
        exit("Database connection failed.");
    }
    
} else {
    // -------------------------------------------------------------------
    // MYSQL CONFIGURATION (DEFAULT)
    // -------------------------------------------------------------------
    
    // Database configuration from environment variables
    $host   = $_ENV['DB_HOST'] ?? 'localhost';
    $port   = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'defaultdb';
    $user   = $_ENV['DB_USER'] ?? 'root';
    $pass   = $_ENV['DB_PASS'] ?? '';
    
    // Define constants for backward compatibility
    define('DB_TYPE', 'mysql');
    define('DB_HOST', $host);
    define('DB_PORT', $port);
    define('DB_NAME', $dbname);
    define('DB_USER', $user);
    define('DB_PASS', $pass);
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // If you have Aiven's CA cert (ca.pem), uncomment & adjust:
        // PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/db/ca.pem',
    ];
    
    // Define constants for PDO options
    define('DB_DSN', $dsn);
    define('DB_OPTIONS', $options);
    
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        exit("Database connection failed.");
    }
}

// -------------------------------------------------------------------
// SUPABASE PDO WRAPPER CLASS
// -------------------------------------------------------------------
// This class provides a PDO-like interface for Supabase operations
// to maintain compatibility with existing code

class SupabasePDOWrapper {
    private $supabase;
    private $lastInsertId = null;
    
    public function __construct($supabase) {
        $this->supabase = $supabase;
    }
    
    public function prepare($sql) {
        return new SupabaseStatement($this->supabase, $sql, $this);
    }
    
    public function lastInsertId() {
        return $this->lastInsertId;
    }
    
    public function setLastInsertId($id) {
        $this->lastInsertId = $id;
    }
}

class SupabaseStatement {
    private $supabase;
    private $sql;
    private $pdo_wrapper;
    private $params = [];
    
    public function __construct($supabase, $sql, $pdo_wrapper) {
        $this->supabase = $supabase;
        $this->sql = $sql;
        $this->pdo_wrapper = $pdo_wrapper;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        
        // Parse SQL and convert to Supabase operations
        $sql_lower = strtolower(trim($this->sql));
        
        try {
            if (strpos($sql_lower, 'select') === 0) {
                return $this->executeSelect();
            } elseif (strpos($sql_lower, 'insert') === 0) {
                return $this->executeInsert();
            } elseif (strpos($sql_lower, 'update') === 0) {
                return $this->executeUpdate();
            } elseif (strpos($sql_lower, 'delete') === 0) {
                return $this->executeDelete();
            }
            
            throw new Exception("Unsupported SQL operation");
            
        } catch (Exception $e) {
            error_log("Supabase query failed: " . $e->getMessage());
            throw new PDOException("Database query failed: " . $e->getMessage());
        }
    }
    
    private function executeSelect() {
        // Parse table name and conditions from SQL
        $table = $this->extractTableName($this->sql);
        $conditions = $this->extractWhereConditions($this->sql);
        $columns = $this->extractSelectColumns($this->sql);
        
        $query = $this->supabase->from($table);
        
        if ($columns !== '*') {
            $query = $query->select($columns);
        }
        
        // Apply WHERE conditions
        foreach ($conditions as $condition) {
            $query = $query->eq($condition['column'], $condition['value']);
        }
        
        // Apply LIMIT if present
        if (strpos(strtolower($this->sql), 'limit 1') !== false) {
            $query = $query->limit(1);
        }
        
        $response = $query->execute();
        return new SupabaseResult($response);
    }
    
    private function executeInsert() {
        $table = $this->extractTableName($this->sql);
        $data = $this->extractInsertData($this->sql);
        
        $response = $this->supabase->from($table)->insert($data)->execute();
        
        // Set last insert ID if available
        if (isset($response[0]['id'])) {
            $this->pdo_wrapper->setLastInsertId($response[0]['id']);
        }
        
        return true;
    }
    
    private function executeUpdate() {
        $table = $this->extractTableName($this->sql);
        $data = $this->extractUpdateData($this->sql);
        $conditions = $this->extractWhereConditions($this->sql);
        
        $query = $this->supabase->from($table);
        
        foreach ($conditions as $condition) {
            $query = $query->eq($condition['column'], $condition['value']);
        }
        
        $response = $query->update($data)->execute();
        return true;
    }
    
    private function executeDelete() {
        $table = $this->extractTableName($this->sql);
        $conditions = $this->extractWhereConditions($this->sql);
        
        $query = $this->supabase->from($table);
        
        foreach ($conditions as $condition) {
            $query = $query->eq($condition['column'], $condition['value']);
        }
        
        $response = $query->delete()->execute();
        return true;
    }
    
    private function extractTableName($sql) {
        // Simple regex to extract table name - this is a basic implementation
        if (preg_match('/(?:from|into|update)\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        throw new Exception("Could not extract table name from SQL");
    }
    
    private function extractSelectColumns($sql) {
        if (preg_match('/select\s+(.*?)\s+from/i', $sql, $matches)) {
            return trim($matches[1]);
        }
        return '*';
    }
    
    private function extractWhereConditions($sql) {
        $conditions = [];
        
        // Extract WHERE clause
        if (preg_match('/where\s+(.+?)(?:\s+(?:order|group|limit|$))/i', $sql, $matches)) {
            $where_clause = $matches[1];
            
            // Parse conditions - basic implementation for common patterns
            if (preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $where_clause, $condition_matches, PREG_SET_ORDER)) {
                foreach ($condition_matches as $match) {
                    $column = $match[1];
                    $param_name = $match[2];
                    
                    // Find parameter value
                    $param_key = ':' . $param_name;
                    if (isset($this->params[$param_key])) {
                        $conditions[] = [
                            'column' => $column,
                            'value' => $this->params[$param_key]
                        ];
                    } elseif (isset($this->params[0])) {
                        // Positional parameter
                        $conditions[] = [
                            'column' => $column,
                            'value' => $this->params[0]
                        ];
                    }
                }
            }
        }
        
        return $conditions;
    }
    
    private function extractInsertData($sql) {
        // Extract column names and values from INSERT statement
        $data = [];
        
        if (preg_match('/insert\s+into\s+\w+\s*\((.*?)\)\s*values\s*\((.*?)\)/i', $sql, $matches)) {
            $columns = array_map('trim', explode(',', $matches[1]));
            $placeholders = array_map('trim', explode(',', $matches[2]));
            
            foreach ($columns as $i => $column) {
                $column = trim($column, '`');
                $placeholder = $placeholders[$i];
                
                if (isset($this->params[$placeholder])) {
                    $data[$column] = $this->params[$placeholder];
                }
            }
        }
        
        return $data;
    }
    
    private function extractUpdateData($sql) {
        // Extract SET clause from UPDATE statement
        $data = [];
        
        if (preg_match('/set\s+(.*?)\s+where/i', $sql, $matches)) {
            $set_clause = $matches[1];
            
            if (preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $set_clause, $set_matches, PREG_SET_ORDER)) {
                foreach ($set_matches as $match) {
                    $column = $match[1];
                    $param_name = $match[2];
                    
                    $param_key = ':' . $param_name;
                    if (isset($this->params[$param_key])) {
                        $data[$column] = $this->params[$param_key];
                    }
                }
            }
        }
        
        return $data;
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        // This method will be called on the result object
        return false;
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        // This method will be called on the result object
        return [];
    }
    
    public function fetchColumn($column_number = 0) {
        // This method will be called on the result object
        return false;
    }
}

class SupabaseResult {
    private $data;
    private $position = 0;
    
    public function __construct($data) {
        $this->data = is_array($data) ? $data : [];
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        if ($this->position < count($this->data)) {
            return $this->data[$this->position++];
        }
        return false;
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        return $this->data;
    }
    
    public function fetchColumn($column_number = 0) {
        if (!empty($this->data)) {
            $first_row = $this->data[0];
            if (is_array($first_row)) {
                $values = array_values($first_row);
                return isset($values[$column_number]) ? $values[$column_number] : false;
            }
        }
        return false;
    }
}

?>