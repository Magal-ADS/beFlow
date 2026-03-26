<?php
require_once __DIR__ . '/../../config/database.php';

class Confirmacao {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function registrar($usuario_id, $ponto_id) {
        // Verifica se o aluno já confirmou presença hoje para não duplicar
        $data_hoje = date('Y-m-d');
        
        $query = "INSERT INTO CONFIRMACOES (usuario_id, ponto_id, data_confirmacao, hora_confirmacao) 
                  VALUES (:usuario_id, :ponto_id, :data, :hora)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':ponto_id', $ponto_id);
        $stmt->bindValue(':data', $data_hoje);
        $stmt->bindValue(':hora', date('H:i:s'));

        return $stmt->execute();
    }
}