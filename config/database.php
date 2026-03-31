<?php
class Database {
    private $driver;
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->loadEnv();

        $databaseUrl = getenv('DATABASE_URL') ?: '';
        $hasLocalConfig = (getenv('DB_HOST') ?: '') !== '' || (getenv('DB_NAME') ?: '') !== '';
        $preferDatabaseUrl = filter_var(getenv('DB_USE_DATABASE_URL') ?: 'false', FILTER_VALIDATE_BOOLEAN);

        if ($databaseUrl !== '' && (!$hasLocalConfig || $preferDatabaseUrl)) {
            $this->configureFromUrl($databaseUrl);
            return;
        }

        $this->configureFromLocalEnv();
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
    }

    private function configureFromUrl($databaseUrl) {
        $url = parse_url($databaseUrl);
        $scheme = strtolower($url['scheme'] ?? 'pgsql');

        $this->driver = in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true) ? 'pgsql' : 'mysql';
        $this->host = $url['host'] ?? '127.0.0.1';
        $this->port = $url['port'] ?? ($this->driver === 'pgsql' ? 5432 : 3306);
        $this->username = $url['user'] ?? '';
        $this->password = $url['pass'] ?? '';
        $this->db_name = ltrim($url['path'] ?? '', '/');
    }

    private function configureFromLocalEnv() {
        $driver = strtolower(getenv('DB_DRIVER') ?: getenv('DB_CONNECTION') ?: '');

        if ($driver === '') {
            $driver = ((string) getenv('DB_PORT')) === '5432' ? 'pgsql' : 'mysql';
        }

        $this->driver = in_array($driver, ['pgsql', 'postgres', 'postgresql'], true) ? 'pgsql' : 'mysql';
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->port = getenv('DB_PORT') ?: ($this->driver === 'pgsql' ? 5432 : 3306);
        $this->db_name = getenv('DB_NAME') ?: 'beflow';
        $this->username = getenv('DB_USER') ?: ($this->driver === 'pgsql' ? 'postgres' : 'root');
        $this->password = getenv('DB_PASS') ?: '';
    }

    public function getDriver() {
        return $this->driver;
    }

    public function getConfig() {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'dbname' => $this->db_name,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function getDsn($includeDatabase = true) {
        if ($this->driver === 'pgsql') {
            $dsn = "pgsql:host={$this->host};port={$this->port}";
            if ($includeDatabase && $this->db_name !== '') {
                $dsn .= ";dbname={$this->db_name}";
            }

            return $dsn;
        }

        $dsn = "mysql:host={$this->host};port={$this->port}";
        if ($includeDatabase && $this->db_name !== '') {
            $dsn .= ";dbname={$this->db_name}";
        }

        return $dsn . ";charset=utf8mb4";
    }

    public function createPdo($includeDatabase = true) {
        $pdo = new PDO($this->getDsn($includeDatabase), $this->username, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    public function getConnection() {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $this->conn = $this->createPdo(true);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                "Erro na conexão com o banco de dados ({$this->driver}): " . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $this->conn;
    }
}
?>
