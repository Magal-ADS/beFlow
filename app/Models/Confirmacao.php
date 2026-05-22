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

        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $stmtPonto = $this->conn->prepare('SELECT linha_id FROM pontos WHERE id = :id LIMIT 1');
        $stmtPonto->execute(['id' => $pontoId]);
        $linhaDoPonto = $stmtPonto->fetchColumn();

        if (!$linhaDoPonto || (int) $linhaDoPonto !== (int) $viagemAtual['linha_id']) {
            $this->lastError = 'Este ponto nao esta disponivel na rota ativa de hoje.';
            return false;
        }

        try {
            return $this->salvarEstado($alunoId, (int) $viagemAtual['id'], (int) $pontoId, 'embarque');
        } catch (PDOException $e) {
            $this->lastError = $this->isDuplicateKey($e)
                ? 'Sua presenca neste ponto ja foi confirmada para a viagem de hoje.'
                : 'Falha ao registrar a confirmacao no banco de dados.';

            return false;
        }
    }

    public function cancelar($usuarioId) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $estadoAtual = $this->buscarEstadoPorAlunoEViagem($alunoId, (int) $viagemAtual['id']);
        if (!$estadoAtual) {
            $this->lastError = 'Nenhuma presenca foi confirmada para cancelar.';
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM confirmacoes WHERE aluno_id = :aluno_id AND viagem_id = :viagem_id');
        return $stmt->execute([
            'aluno_id' => $alunoId,
            'viagem_id' => $viagemAtual['id'],
        ]);
    }

    public function informarEmbarque($usuarioId) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $estadoAtual = $this->buscarEstadoPorAlunoEViagem($alunoId, (int) $viagemAtual['id']);
        if (!$estadoAtual || $estadoAtual['tipo'] !== 'embarque') {
            $this->lastError = 'Confirme primeiro sua presenca em um ponto.';
            return false;
        }

        return $this->salvarEstado($alunoId, (int) $viagemAtual['id'], (int) $estadoAtual['ponto_id'], 'embarcado');
    }

    public function informarRetorno($usuarioId, $vaiVoltar) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        $estadoAtual = $this->buscarEstadoPorAlunoEViagem($alunoId, (int) $viagemAtual['id']);
        if (!$estadoAtual || !in_array($estadoAtual['tipo'], ['embarcado', 'retorno_sim', 'retorno_nao'], true)) {
            $this->lastError = 'Informe primeiro que voce ja esta no onibus.';
            return false;
        }

        $tipo = $vaiVoltar ? 'retorno_sim' : 'retorno_nao';
        return $this->salvarEstado($alunoId, (int) $viagemAtual['id'], (int) $estadoAtual['ponto_id'], $tipo);
    }

    public function buscarEstadoAtual($usuarioId) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            return null;
        }

        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual) {
            return null;
        }

        return $this->buscarEstadoPorAlunoEViagem($alunoId, (int) $viagemAtual['id']);
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function buscarAlunoIdPorUsuario($usuarioId) {
        $stmt = $this->conn->prepare('SELECT id FROM alunos WHERE usuario_id = :usuario_id LIMIT 1');
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchColumn() ?: null;
    }

    private function buscarViagemAtual() {
        $pontoModel = new Ponto();
        return $pontoModel->buscarViagemAtual();
    }

    private function buscarEstadoPorAlunoEViagem($alunoId, $viagemId) {
        $stmt = $this->conn->prepare("
            SELECT c.*, p.nome AS ponto_nome, l.nome AS linha_nome
            FROM confirmacoes c
            INNER JOIN pontos p ON p.id = c.ponto_id
            INNER JOIN linhas l ON l.id = p.linha_id
            WHERE c.aluno_id = :aluno_id AND c.viagem_id = :viagem_id
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            'aluno_id' => $alunoId,
            'viagem_id' => $viagemId,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function salvarEstado($alunoId, $viagemId, $pontoId, $tipo) {
        try {
            $this->conn->beginTransaction();

            $stmtDelete = $this->conn->prepare('DELETE FROM confirmacoes WHERE aluno_id = :aluno_id AND viagem_id = :viagem_id');
            $stmtDelete->execute([
                'aluno_id' => $alunoId,
                'viagem_id' => $viagemId,
            ]);

            $stmtInsert = $this->conn->prepare("
                INSERT INTO confirmacoes (aluno_id, viagem_id, ponto_id, tipo)
                VALUES (:aluno_id, :viagem_id, :ponto_id, :tipo)
            ");
            $stmtInsert->execute([
                'aluno_id' => $alunoId,
                'viagem_id' => $viagemId,
                'ponto_id' => $pontoId,
                'tipo' => $tipo,
            ]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
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
