<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Ponto.php';

class Confirmacao {
    private $conn;
    private $lastError = 'Erro inesperado ao confirmar presença.';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function registrar($usuario_id, $ponto_id) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuario_id);
        if (!$alunoId) {
            $this->lastError = 'O usuário autenticado não está vinculado a um aluno.';
            return false;
        }

        $pontoModel = new Ponto();
        $viagemId = $pontoModel->buscarViagemAtualId();
        if (!$viagemId) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $sql = "INSERT INTO confirmacoes (aluno_id, viagem_id, ponto_id, tipo)
                VALUES (:aluno_id, :viagem_id, :ponto_id, :tipo)";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'aluno_id' => $alunoId,
                'viagem_id' => $viagemId,
                'ponto_id' => $ponto_id,
                'tipo' => 'embarque',
            ]);
        } catch (PDOException $e) {
            $this->lastError = $this->isDuplicateKey($e)
                ? 'Sua presença neste ponto já foi confirmada para a viagem de hoje.'
                : 'Falha ao registrar a confirmação no banco de dados.';

            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function buscarAlunoIdPorUsuario($usuarioId) {
        $stmt = $this->conn->prepare("SELECT id FROM alunos WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchColumn() ?: null;
    }

    private function isDuplicateKey(PDOException $e) {
        $message = $e->getMessage();
        $code = (string) $e->getCode();

        return in_array($code, ['23000', '23505'], true)
            || stripos($message, 'duplicate') !== false
            || stripos($message, 'uniq_confirmacao') !== false;
    }
}
