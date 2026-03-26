<?php
class Database {
    private $host = "localhost";
    private $db_name = "beflow";
    private $username = "root"; /* Padrão do XAMPP */
    private $password = "";     /* Padrão do XAMPP é sem senha */
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erro na conexão: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>