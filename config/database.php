<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    if (!getenv(trim($key))) putenv(trim($key) . '=' . trim($value));
                }
            }
        }

        // Pega a URL do Heroku se existir
        $databaseUrl = getenv('DATABASE_URL');

        if ($databaseUrl) {
            $url = parse_url($databaseUrl);
            $this->host = $url["host"];
            $this->port = $url["port"] ?? 5432;
            $this->username = $url["user"] ?? '';
            $this->password = $url["pass"] ?? '';
            $this->db_name = ltrim($url["path"], "/");
        } else {
            // Fallback para Postgres Local
            $this->host = getenv('DB_HOST') ?: '127.0.0.1';
            $this->port = getenv('DB_PORT') ?: 5432;
            $this->db_name = getenv('DB_NAME') ?: 'beflow';
            $this->username = getenv('DB_USER') ?: 'postgres';
            $this->password = getenv('DB_PASS') ?: 'sua_senha_local';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // A mágica acontece aqui: mudamos de mysql para pgsql
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erro na conexão com o banco de dados (Postgres): " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>