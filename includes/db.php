<?php
/**
 * Database Abstraction Layer
 * Supports both MySQL (via mysqli) and SQLite (via PDO)
 */

class Database {
    private $connection;
    private $type; // 'mysqli' or 'sqlite'
    
    public function __construct($type, $connection) {
        $this->type = $type;
        $this->connection = $connection;
    }
    
    public function query($sql) {
        if ($this->type === 'sqlite') {
            return new SQLiteResult($this->connection, $sql);
        }
        return $this->connection->query($sql);
    }
    
    public function real_escape_string($str) {
        if ($this->type === 'sqlite') {
            // PDO::quote() adds surrounding quotes; strip them to match mysqli behaviour
            return substr($this->connection->quote($str), 1, -1);
        }
        return $this->connection->real_escape_string($str);
    }
    
    public function insert_id() {
        if ($this->type === 'sqlite') {
            return $this->connection->lastInsertRowID();
        }
        return $this->connection->insert_id;
    }
}

/**
 * SQLite Result Wrapper (mimics mysqli_result)
 */
class SQLiteResult {
    private $pdo;
    private $statement;
    private $rows;
    private $currentRow = 0;
    
    public function __construct($pdo, $sql) {
        $this->pdo = $pdo;
        $this->statement = $pdo->prepare($sql);
        $this->statement->execute();
        $this->rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function fetch_assoc() {
        if (isset($this->rows[$this->currentRow])) {
            return $this->rows[$this->currentRow++];
        }
        return null;
    }
    
    public function fetch_row() {
        if (isset($this->rows[$this->currentRow])) {
            return array_values($this->rows[$this->currentRow++]);
        }
        return null;
    }
    
    public function __get($name) {
        if ($name === 'num_rows') return count($this->rows);
        return null;
    }

    public function num_rows() {
        return count($this->rows);
    }
}

/**
 * Create database connection based on configuration
 */
function createDatabaseConnection() {
    $useSQLite = getenv('USE_SQLITE') ?: (defined('USE_SQLITE') && USE_SQLITE);
    
    if ($useSQLite) {
        $dbPath = getenv('SQLITE_PATH') ?: (defined('SQLITE_PATH') ? SQLITE_PATH : __DIR__ . '/../data/faucet.db');
        
        // Create data directory if it doesn't exist
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Create SQLite database and tables if they don't exist
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS faucet_settings (
                id INTEGER PRIMARY KEY,
                name TEXT,
                value TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_user_list (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                address TEXT UNIQUE,
                ip_address TEXT,
                balance REAL DEFAULT 0,
                joined INTEGER,
                last_activity INTEGER,
                referred_by INTEGER DEFAULT 0,
                last_claim INTEGER DEFAULT 0,
                claim_cryptokey TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_payouts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                amount REAL,
                currency TEXT,
                address TEXT,
                status TEXT,
                date INTEGER,
                txid TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_spaces (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                space TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                content TEXT,
                timestamp_created INTEGER
            );
            CREATE TABLE IF NOT EXISTS faucet_banned_address (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                address TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_banned_ip (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT
            );
            CREATE TABLE IF NOT EXISTS faucet_addon_list (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                directory_name TEXT,
                enabled INTEGER
            );
            CREATE TABLE IF NOT EXISTS faucet_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                userid INTEGER,
                type TEXT,
                amount REAL,
                timestamp INTEGER
            );
        ");
        
        // Insert default spaces if empty
        $spacesCount = $pdo->query("SELECT COUNT(*) FROM faucet_spaces")->fetchColumn();
        if ($spacesCount == 0) {
            $stmt = $pdo->prepare("INSERT INTO faucet_spaces (id, name, space) VALUES (?, ?, ?)");
            $stmt->execute([1, 'space_top', 'Space on the top']);
            $stmt->execute([2, 'space_left', 'Space on the left side']);
            $stmt->execute([3, 'space_right', 'Space on the right side']);
        }

        // Insert default settings if empty (IDs must match what admin.php queries by numeric id)
        $count = $pdo->query("SELECT COUNT(*) FROM faucet_settings")->fetchColumn();
        if ($count == 0) {
            $defaults = [
                [1,  'faucet_name',               'Simple Faucet Script'],
                [5,  'timer',                      '60'],
                [6,  'min_reward',                 '1'],
                [7,  'max_reward',                 '100'],
[11, 'claim_enabled',              'yes'],
                [12, 'admin_username',             'admin'],
                [13, 'admin_password',             '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'],
                [14, 'vpn_shield',                 'no'],
                [15, 'referral_percent',           '0'],
                [16, 'reverse_proxy',              'no'],
                [17, 'admin_login',                ''],
                [19, 'faucetpay_api_token',        ''],
                [22, 'iphub_api_key',              ''],
                [23, 'min_withdrawal_gateway',     '1'],
                [25, 'bootswatch_theme',           ''],
                [26, 'hcaptcha_pub_key',           ''],
                [27, 'hcaptcha_sec_key',           ''],
                [28, 'faucet_currency',            'Bitcoin'],
            ];

            $stmt = $pdo->prepare("INSERT INTO faucet_settings (id, name, value) VALUES (?, ?, ?)");
            foreach ($defaults as $row) {
                $stmt->execute($row);
            }
        }
        
        return new Database('sqlite', $pdo);
    }
    
    // MySQL connection (original)
    $dbHost = getenv('DB_HOST') ?: "localhost";
    $dbUser = getenv('DB_USER') ?: "";
    $dbPW = getenv('DB_PASS') ?: "";
    $dbName = getenv('DB_NAME') ?: "";
    
    $mysqli = mysqli_connect($dbHost, $dbUser, $dbPW, $dbName);
    
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit;
    }
    
    return new Database('mysqli', $mysqli);
}
