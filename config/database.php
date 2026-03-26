<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    // O construtor é chamado automaticamente sempre que você faz um "new Database()"
    public function __construct() {
        // Caminho para o arquivo .env (volta uma pasta a partir de 'config' para a raiz do projeto)
        $envFile = __DIR__ . '/../.env';
        
        // Lê o arquivo .env se ele existir
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignora linhas vazias ou comentários (que começam com #)
                if ($line === '' || $line[0] === '#') continue;
                
                // Pega a chave e o valor separados por "="
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Coloca na memória do PHP
                    if (!getenv($key)) {
                        putenv("$key=$value");
                    }
                }
            }
        }

        // Puxa as variáveis da memória (lidas do .env) 
        // Se não encontrar, usa os padrões do XAMPP como fallback (plano B)
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->db_name = getenv('DB_NAME') ?: 'beflow';
        $this->username = getenv('DB_USER') ?: 'root';
        
        // A senha no XAMPP é vazia, então precisamos tratar com cuidado para ele aceitar string vazia
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // Adicionado charset=utf8mb4 para suportar acentuação e emojis corretamente
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            // Em produção real, a gente salvaria isso num arquivo de log em vez de exibir na tela
            echo "Erro na conexão com o banco de dados: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>