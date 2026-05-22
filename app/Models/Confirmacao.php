<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Ponto.php';

class Confirmacao {
    private $conn;
    private $lastError = 'Erro inesperado ao confirmar presenca.';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function registrar($usuarioId, $pontoId) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $pontoModel = new Ponto();
        $viagemAtual = $pontoModel->buscarViagemAtual();
        if (!$viagemAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $stmtPonto = $this->conn->prepare('SELECT linha_id FROM pontos WHERE id = :id LIMIT 1');
        $stmtPonto->execute(['id' => $pontoId]);
        $linhaDoPonto = $stmtPonto->fetchColumn();

        if (!$linhaDoPonto || (int) $linhaDoPonto !== (int) $viagemAtual['linha_id']) {
            $this->lastError = 'Este ponto nao pertence a linha escolhida para a viagem de hoje.';
            return false;
        }

        $sql = "INSERT INTO confirmacoes (aluno_id, viagem_id, ponto_id, tipo)
                VALUES (:aluno_id, :viagem_id, :ponto_id, :tipo)";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'aluno_id' => $alunoId,
                'viagem_id' => $viagemAtual['id'],
                'ponto_id' => $pontoId,
                'tipo' => 'embarque',
            ]);
        } catch (PDOException $e) {
            $this->lastError = $this->isDuplicateKey($e)
                ? 'Sua presenca neste ponto ja foi confirmada para a viagem de hoje.'
                : 'Falha ao registrar a confirmacao no banco de dados.';

            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function buscarAlunoIdPorUsuario($usuarioId) {
        $stmt = $this->conn->prepare('SELECT id FROM alunos WHERE usuario_id = :usuario_id LIMIT 1');
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
?>
