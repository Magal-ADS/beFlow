<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Ponto.php';

class Confirmacao {
    private $conn;
    private $lastError = 'Erro inesperado ao confirmar presenca.';
    private $lastHttpStatus = 200;
    private $driver;
    private $schemaColumnCache = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->driver = $database->getDriver();
    }

    public function registrar($usuarioId, $pontoId) {
        $this->resetError();

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
        $this->resetError();

        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $estadoAtual = $this->buscarEstadoAtual($usuarioId);
        if (!$estadoAtual) {
            $this->lastError = 'Nenhuma presenca foi confirmada para cancelar.';
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM confirmacoes WHERE aluno_id = :aluno_id AND viagem_id = :viagem_id');
        return $stmt->execute([
            'aluno_id' => $alunoId,
            'viagem_id' => $estadoAtual['viagem_id'],
        ]);
    }

    public function informarEmbarque($usuarioId) {
        $this->resetError();

        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $estadoAtual = $this->buscarEstadoAtual($usuarioId);
        if (!$estadoAtual) {
            $this->lastError = 'Nenhuma viagem ativa foi configurada para hoje.';
            return false;
        }

        if (($estadoAtual['viagem_status'] ?? '') !== 'em_rota') {
            $this->lastError = 'Ação bloqueada: O motorista ainda não iniciou a rota.';
            $this->lastHttpStatus = 400;
            return false;
        }

        if ($estadoAtual['tipo'] !== 'embarque') {
            $this->lastError = 'Confirme primeiro sua presenca em um ponto.';
            return false;
        }

        return $this->salvarEstado($alunoId, (int) $estadoAtual['viagem_id'], (int) $estadoAtual['ponto_id'], 'embarcado');
    }

    public function informarRetorno($usuarioId, $vaiVoltar) {
        $this->resetError();

        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            $this->lastError = 'O usuario autenticado nao esta vinculado a um aluno.';
            return false;
        }

        $estadoIda = $this->buscarEstadoPorDirecao($alunoId, 'ida');
        if (!$estadoIda) {
            $this->lastError = 'Nenhuma viagem de ida foi encontrada para registrar o retorno.';
            return false;
        }

        if (!in_array($estadoIda['tipo'], ['embarcado', 'retorno_sim', 'retorno_nao'], true)) {
            $this->lastError = 'Informe primeiro que voce ja esta no onibus.';
            return false;
        }

        $tipo = $vaiVoltar ? 'retorno_sim' : 'retorno_nao';
        $stmt = $this->conn->prepare('UPDATE confirmacoes SET tipo = :tipo WHERE id = :id');

        return $stmt->execute([
            'tipo' => $tipo,
            'id' => $estadoIda['id'],
        ]);
    }

    public function buscarEstadoAtual($usuarioId) {
        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if (!$alunoId) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT c.*, p.nome AS ponto_nome, l.nome AS linha_nome,
                   v.status AS viagem_status, " . $this->tripDirectionSelect('viagem_direcao') . ",
                   " . $this->horaIdaSelect() . ", " . $this->horaVoltaSelect() . "
            FROM confirmacoes c
            INNER JOIN viagens v ON v.id = c.viagem_id
            INNER JOIN pontos p ON p.id = c.ponto_id
            INNER JOIN linhas l ON l.id = p.linha_id
            INNER JOIN horarios_base hb ON hb.id = v.horario_base_id
            WHERE c.aluno_id = :aluno_id
              AND v.data_viagem = " . $this->currentDateExpression() . "
            ORDER BY
                CASE " . $this->tripDirectionOrderExpression() . "
                    WHEN 'volta' THEN 0
                    WHEN 'ida' THEN 1
                    ELSE 2
                END,
                CASE v.status
                    WHEN 'em_rota' THEN 0
                    WHEN 'aguardando' THEN 1
                    WHEN 'agendada' THEN 2
                    WHEN 'aguardando_encerramento' THEN 3
                    WHEN 'finalizada' THEN 4
                    ELSE 5
                END,
                c.created_at DESC,
                c.id DESC
            LIMIT 1
        ");
        $stmt->execute(['aluno_id' => $alunoId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarContextoAtual($usuarioId) {
        $estadoAtual = $this->buscarEstadoAtual($usuarioId);
        $retornoPlanejado = false;
        $horaVolta = null;
        $viagem = null;

        if ($estadoAtual) {
            $viagem = (new Ponto())->buscarViagemPorId((int) $estadoAtual['viagem_id']);
        }

        $alunoId = $this->buscarAlunoIdPorUsuario($usuarioId);
        if ($alunoId) {
            $estadoIda = $this->buscarEstadoPorDirecao($alunoId, 'ida');
            if ($estadoIda) {
                $retornoPlanejado = ($estadoIda['tipo'] ?? '') === 'retorno_sim';
                $horaVolta = $estadoIda['hora_volta'] ?? null;

                if (!$viagem) {
                    $viagem = (new Ponto())->buscarViagemPorId((int) $estadoIda['viagem_id']);
                }
            }
        }

        if (!$viagem) {
            $viagem = (new Ponto())->buscarViagemAtual();
        }

        if (!$horaVolta && $viagem) {
            $horaVolta = $viagem['hora_volta'] ?? null;
        }

        return [
            'state' => $estadoAtual,
            'trip' => $viagem,
            'retorno_planejado' => $retornoPlanejado,
            'hora_volta' => $horaVolta,
        ];
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function getLastHttpStatus() {
        return $this->lastHttpStatus;
    }

    private function resetError() {
        $this->lastError = 'Erro inesperado ao confirmar presenca.';
        $this->lastHttpStatus = 200;
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

    private function buscarEstadoPorDirecao($alunoId, $direcao) {
        $stmt = $this->conn->prepare("
            SELECT c.*, p.nome AS ponto_nome, l.nome AS linha_nome,
                   v.status AS viagem_status, " . $this->tripDirectionSelect('viagem_direcao') . ",
                   " . $this->horaIdaSelect() . ", " . $this->horaVoltaSelect() . "
            FROM confirmacoes c
            INNER JOIN viagens v ON v.id = c.viagem_id
            INNER JOIN pontos p ON p.id = c.ponto_id
            INNER JOIN linhas l ON l.id = p.linha_id
            INNER JOIN horarios_base hb ON hb.id = v.horario_base_id
            WHERE c.aluno_id = :aluno_id
              AND v.data_viagem = " . $this->currentDateExpression() . "
              " . ($this->hasTripDirectionColumn() ? 'AND v.direcao = :direcao' : '') . "
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT 1
        ");
        $params = ['aluno_id' => $alunoId];
        if ($this->hasTripDirectionColumn()) {
            $params['direcao'] = $direcao;
        }
        $stmt->execute($params);

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

    private function currentDateExpression() {
        return $this->driver === 'mysql' ? 'CURDATE()' : 'CURRENT_DATE';
    }

    private function hasTripDirectionColumn() {
        return $this->hasColumn('viagens', 'direcao');
    }

    private function hasHorarioIdaColumn() {
        return $this->hasColumn('horarios_base', 'hora_ida');
    }

    private function hasHorarioVoltaColumn() {
        return $this->hasColumn('horarios_base', 'hora_volta');
    }

    private function tripDirectionSelect($alias) {
        return $this->hasTripDirectionColumn() ? "v.direcao AS {$alias}" : "'ida' AS {$alias}";
    }

    private function tripDirectionOrderExpression() {
        return $this->hasTripDirectionColumn() ? 'v.direcao' : "'ida'";
    }

    private function horaIdaSelect() {
        return $this->hasHorarioIdaColumn() ? 'hb.hora_ida' : 'hb.hora_saida_garagem AS hora_ida';
    }

    private function horaVoltaSelect() {
        return $this->hasHorarioVoltaColumn() ? 'hb.hora_volta' : 'hb.hora_saida_garagem AS hora_volta';
    }

    private function hasColumn($table, $column) {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaColumnCache)) {
            return $this->schemaColumnCache[$cacheKey];
        }

        if ($this->driver === 'pgsql') {
            $stmt = $this->conn->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = CURRENT_SCHEMA()
                  AND table_name = :table
                  AND column_name = :column
                LIMIT 1
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table
                  AND column_name = :column
                LIMIT 1
            ");
        }

        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $exists = (bool) $stmt->fetchColumn();
        $this->schemaColumnCache[$cacheKey] = $exists;

        return $exists;
    }
}
?>
