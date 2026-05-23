<?php
require_once __DIR__ . '/../../config/database.php';

class Ponto {
    private $conn;
    private $driver;
    private $schemaColumnCache = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->driver = $database->getDriver();
    }

    public function buscarPorLinha($linhaId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, l.nome AS nome_linha, l.cor AS cor_linha
            FROM pontos p
            INNER JOIN linhas l ON l.id = p.linha_id
            WHERE p.linha_id = :linha_id
            ORDER BY p.ordem_na_linha ASC
        ");
        $stmt->execute(['linha_id' => $linhaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarTodos() {
        $query = "SELECT p.*, l.nome AS nome_linha, l.cor AS cor_linha
                  FROM pontos p
                  INNER JOIN linhas l ON l.id = p.linha_id
                  ORDER BY l.nome ASC, p.ordem_na_linha ASC";

        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarLinhasDaEmpresa($empresaId) {
        $stmt = $this->conn->prepare("SELECT id, nome, cor FROM linhas WHERE empresa_id = :empresa_id ORDER BY nome ASC");
        $stmt->execute(['empresa_id' => $empresaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarVeiculosDaEmpresa($empresaId) {
        $stmt = $this->conn->prepare("
            SELECT id, numero_identificador, placa
            FROM veiculo
            WHERE empresa_id = :empresa_id
            ORDER BY numero_identificador ASC, placa ASC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPontosComContagem($motoristaId = null) {
        $viagemAtual = $this->buscarViagemAtual($motoristaId);
        if (!$viagemAtual || empty($viagemAtual['linha_id'])) {
            return [];
        }

        $query = "SELECT p.*, l.nome AS nome_linha, l.cor AS cor_linha,
                         (
                             SELECT COUNT(*)
                             FROM confirmacoes c
                             WHERE c.ponto_id = p.id
                               AND c.viagem_id = :viagem_id
                               AND c.tipo = 'embarque'
                         ) AS total_alunos,
                         (
                             SELECT COUNT(*)
                             FROM confirmacoes c
                             WHERE c.ponto_id = p.id
                               AND c.viagem_id = :viagem_id
                               AND c.tipo = 'retorno_sim'
                         ) AS total_retorno
                  FROM pontos p
                  INNER JOIN linhas l ON l.id = p.linha_id
                  WHERE p.linha_id = :linha_id
                  ORDER BY p.ordem_na_linha ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            'viagem_id' => $viagemAtual['id'],
            'linha_id' => $viagemAtual['linha_id'],
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPontosDaViagemAtual() {
        $viagemAtual = $this->buscarViagemAtual();
        if (!$viagemAtual || empty($viagemAtual['linha_id'])) {
            return [];
        }

        return $this->buscarPorLinha((int) $viagemAtual['linha_id']);
    }

    public function buscarViagemAtual($motoristaId = null, $direcao = null) {
        $query = $this->buildTripQuery($motoristaId !== null, $direcao !== null);
        $params = [];

        if ($motoristaId !== null) {
            $params['motorista_id'] = $motoristaId;
        }

        if ($direcao !== null) {
            $params['direcao'] = $direcao;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);

        return $viagem ?: null;
    }

    public function buscarViagemAtualId($motoristaId = null, $direcao = null) {
        $viagem = $this->buscarViagemAtual($motoristaId, $direcao);
        return $viagem ? (int) $viagem['id'] : null;
    }

    public function buscarViagemPorId($viagemId) {
        $stmt = $this->conn->prepare($this->buildTripSelect() . ' WHERE v.id = :id LIMIT 1');
        $stmt->execute(['id' => $viagemId]);
        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);

        return $viagem ?: null;
    }

    public function buscarViagemDoDiaPorLinhaEDirecao($linhaId, $direcao) {
        if (!$this->hasTripDirectionColumn() && $direcao === 'volta') {
            return null;
        }

        $stmt = $this->conn->prepare(
            $this->buildTripSelect()
            . ' WHERE v.data_viagem = ' . $this->currentDateExpression()
            . ' AND v.linha_id = :linha_id'
            . ($this->hasTripDirectionColumn() ? ' AND v.direcao = :direcao' : '')
            . ' LIMIT 1'
        );
        $params = ['linha_id' => $linhaId];
        if ($this->hasTripDirectionColumn()) {
            $params['direcao'] = $direcao;
        }
        $stmt->execute($params);

        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);
        return $viagem ?: null;
    }

    public function configurarViagemDoDia($motoristaId, $linhaId, $veiculoId, $direcao) {
        $direcao = strtolower(trim((string) $direcao));
        if (!in_array($direcao, ['ida', 'volta'], true)) {
            return false;
        }

        if (!$this->hasTripDirectionColumn()) {
            $direcao = 'ida';
        }

        $empresaId = $this->buscarEmpresaDoUsuario($motoristaId);
        if ($empresaId <= 0) {
            return false;
        }

        if (!$this->linhaPertenceAEmpresa($linhaId, $empresaId) || !$this->veiculoPertenceAEmpresa($veiculoId, $empresaId)) {
            return false;
        }

        $horarioBaseId = $this->obterOuCriarHorarioBase($linhaId);
        $viagemExistente = $this->buscarViagemDoDiaPorLinhaEDirecao($linhaId, $direcao);

        if ($viagemExistente) {
            $sql = "
                UPDATE viagens
                SET horario_base_id = :horario_base_id,
                    linha_id = :linha_id,
                    motorista_id = :motorista_id,
                    veiculo_id = :veiculo_id,"
                    . ($this->hasTripDirectionColumn() ? "
                    direcao = :direcao," : '') . "
                    status = CASE WHEN status = 'finalizada' THEN 'aguardando' ELSE status END
                WHERE id = :id
            ";
            $params = [
                'horario_base_id' => $horarioBaseId,
                'linha_id' => $linhaId,
                'motorista_id' => $motoristaId,
                'veiculo_id' => $veiculoId,
                'id' => $viagemExistente['id'],
            ];
            if ($this->hasTripDirectionColumn()) {
                $params['direcao'] = $direcao;
            }

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        }

        $sql = $this->hasTripDirectionColumn()
            ? "
                INSERT INTO viagens (horario_base_id, linha_id, motorista_id, veiculo_id, direcao, status, data_viagem)
                VALUES (:horario_base_id, :linha_id, :motorista_id, :veiculo_id, :direcao, 'aguardando', {$this->currentDateExpression()})
            "
            : "
                INSERT INTO viagens (horario_base_id, linha_id, motorista_id, veiculo_id, status, data_viagem)
                VALUES (:horario_base_id, :linha_id, :motorista_id, :veiculo_id, 'aguardando', {$this->currentDateExpression()})
            ";

        $params = [
            'horario_base_id' => $horarioBaseId,
            'linha_id' => $linhaId,
            'motorista_id' => $motoristaId,
            'veiculo_id' => $veiculoId,
        ];
        if ($this->hasTripDirectionColumn()) {
            $params['direcao'] = $direcao;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function atualizarStatusViagem($status, $motoristaId = null) {
        $viagemAtual = $this->buscarViagemAtual($motoristaId);
        if (!$viagemAtual || empty($viagemAtual['linha_id']) || empty($viagemAtual['veiculo_id'])) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            if ($this->hasTripDirectionColumn() && $status === 'em_rota' && ($viagemAtual['direcao'] ?? 'ida') === 'volta') {
                $this->popularConfirmacoesDaVolta($viagemAtual);
            }

            $stmt = $this->conn->prepare("UPDATE viagens SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'id' => $viagemAtual['id'],
            ]);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function atualizarLocalizacaoViagemAtiva($motoristaId, $latitude, $longitude) {
        $viagemAtual = $this->buscarViagemAtual($motoristaId);
        if (!$viagemAtual) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE viagens
            SET latitude_atual = :latitude,
                longitude_atual = :longitude
            WHERE id = :id
        ");

        return $stmt->execute([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'id' => $viagemAtual['id'],
        ]);
    }

    public function lerStatusViagem() {
        $viagem = $this->buscarViagemAtual();
        return $viagem ? $viagem['status'] : 'aguardando';
    }

    private function buscarEmpresaDoUsuario($usuarioId) {
        $stmt = $this->conn->prepare("SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    private function linhaPertenceAEmpresa($linhaId, $empresaId) {
        $stmt = $this->conn->prepare("SELECT id FROM linhas WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
        $stmt->execute([
            'id' => $linhaId,
            'empresa_id' => $empresaId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function veiculoPertenceAEmpresa($veiculoId, $empresaId) {
        $stmt = $this->conn->prepare("SELECT id FROM veiculo WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
        $stmt->execute([
            'id' => $veiculoId,
            'empresa_id' => $empresaId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function obterOuCriarHorarioBase($linhaId) {
        $stmt = $this->conn->prepare("SELECT id FROM horarios_base WHERE linha_id = :linha_id ORDER BY id ASC LIMIT 1");
        $stmt->execute(['linha_id' => $linhaId]);
        $horarioBaseId = $stmt->fetchColumn();

        if ($horarioBaseId) {
            return (int) $horarioBaseId;
        }

        if ($this->hasHorarioIdaColumn()) {
            $insert = $this->conn->prepare("
                INSERT INTO horarios_base (linha_id, turno, hora_ida, hora_volta)
                VALUES (:linha_id, :turno, :hora_ida, :hora_volta)
            ");
            $insert->execute([
                'linha_id' => $linhaId,
                'turno' => 'Matutino',
                'hora_ida' => '06:30:00',
                'hora_volta' => '17:30:00',
            ]);
        } else {
            $insert = $this->conn->prepare("
                INSERT INTO horarios_base (linha_id, turno, hora_saida_garagem)
                VALUES (:linha_id, :turno, :hora_saida_garagem)
            ");
            $insert->execute([
                'linha_id' => $linhaId,
                'turno' => 'Matutino',
                'hora_saida_garagem' => '06:30:00',
            ]);
        }

        return (int) $this->conn->lastInsertId();
    }

    private function popularConfirmacoesDaVolta(array $viagemVolta) {
        $viagemIda = $this->buscarViagemDoDiaPorLinhaEDirecao((int) $viagemVolta['linha_id'], 'ida');
        if (!$viagemIda) {
            return;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO confirmacoes (aluno_id, viagem_id, ponto_id, tipo)
            SELECT c.aluno_id, :viagem_volta_id, c.ponto_id, 'embarque'
            FROM confirmacoes c
            WHERE c.viagem_id = :viagem_ida_id
              AND c.tipo = 'retorno_sim'
              AND NOT EXISTS (
                  SELECT 1
                  FROM confirmacoes existing
                  WHERE existing.aluno_id = c.aluno_id
                    AND existing.viagem_id = :viagem_volta_id
              )
        ");
        $stmt->execute([
            'viagem_volta_id' => $viagemVolta['id'],
            'viagem_ida_id' => $viagemIda['id'],
        ]);
    }

    private function currentDateExpression() {
        return $this->driver === 'mysql' ? 'CURDATE()' : 'CURRENT_DATE';
    }

    private function buildTripSelect() {
        $direcaoSelect = $this->hasTripDirectionColumn() ? 'v.direcao' : "'ida' AS direcao";
        $horaIdaSelect = $this->hasHorarioIdaColumn() ? 'hb.hora_ida' : 'hb.hora_saida_garagem AS hora_ida';
        $horaVoltaSelect = $this->hasHorarioVoltaColumn() ? 'hb.hora_volta' : 'hb.hora_saida_garagem AS hora_volta';

        return "
            SELECT v.*, l.nome AS nome_linha, l.cor AS cor_linha,
                   {$direcaoSelect},
                   hb.turno, {$horaIdaSelect}, {$horaVoltaSelect},
                   ve.numero_identificador AS veiculo_identificador,
                   ve.placa AS veiculo_placa
            FROM viagens v
            INNER JOIN linhas l ON l.id = v.linha_id
            INNER JOIN horarios_base hb ON hb.id = v.horario_base_id
            INNER JOIN veiculo ve ON ve.id = v.veiculo_id
        ";
    }

    private function buildTripQuery($withMotorista, $withDirecao) {
        $motoristaSql = $withMotorista ? ' AND v.motorista_id = :motorista_id' : '';
        $direcaoSql = ($withDirecao && $this->hasTripDirectionColumn()) ? ' AND v.direcao = :direcao' : '';
        $direcaoOrder = !$this->hasTripDirectionColumn()
            ? '0'
            : ($withMotorista
            ? "CASE v.direcao
                    WHEN 'volta' THEN 0
                    WHEN 'ida' THEN 1
                    ELSE 2
               END"
            : "CASE v.direcao
                    WHEN 'ida' THEN 0
                    WHEN 'volta' THEN 1
                    ELSE 2
               END");

        return $this->buildTripSelect() . "
            WHERE v.data_viagem = {$this->currentDateExpression()}
              {$motoristaSql}
              {$direcaoSql}
            ORDER BY
                CASE v.status
                    WHEN 'em_rota' THEN 0
                    WHEN 'aguardando_encerramento' THEN 1
                    WHEN 'aguardando' THEN 2
                    WHEN 'agendada' THEN 3
                    WHEN 'finalizada' THEN 4
                    ELSE 5
                END,
                {$direcaoOrder},
                v.id DESC
            LIMIT 1
        ";
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
