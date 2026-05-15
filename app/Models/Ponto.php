<?php
require_once __DIR__ . '/../../config/database.php';

class Ponto {
    private $conn;
    private $driver;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->driver = $database->getDriver();
    }

    public function buscarPorLinha($linha_id) {
        $query = "SELECT * FROM pontos WHERE linha_id = :linha_id ORDER BY ordem_na_linha ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['linha_id' => $linha_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarTodos() {
        $query = "SELECT p.*, l.nome AS nome_linha
                  FROM pontos p
                  INNER JOIN linhas l ON l.id = p.linha_id
                  ORDER BY l.nome ASC, p.ordem_na_linha ASC";

        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPontosComContagem() {
        $viagemAtualId = $this->buscarViagemAtualId();

        if ($viagemAtualId) {
            $query = "SELECT p.*, l.nome AS nome_linha,
                             (
                                 SELECT COUNT(*)
                                 FROM confirmacoes c
                                 WHERE c.ponto_id = p.id
                                   AND c.viagem_id = :viagem_id
                                   AND c.tipo = 'embarque'
                             ) AS total_alunos
                      FROM pontos p
                      INNER JOIN linhas l ON l.id = p.linha_id
                      ORDER BY l.nome ASC, p.ordem_na_linha ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['viagem_id' => $viagemAtualId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $query = "SELECT p.*, l.nome AS nome_linha, 0 AS total_alunos
                  FROM pontos p
                  INNER JOIN linhas l ON l.id = p.linha_id
                  ORDER BY l.nome ASC, p.ordem_na_linha ASC";

        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarStatusViagem($status, $motoristaId = null) {
        $viagemAtualId = $this->buscarViagemAtualId($motoristaId);

        if (!$viagemAtualId && $status === 'em_rota' && $motoristaId) {
            $viagemAtualId = $this->criarViagemDoDia($motoristaId);
        }

        if (!$viagemAtualId) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE viagens SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $status,
            'id' => $viagemAtualId,
        ]);
    }

    public function lerStatusViagem() {
        $query = $this->buildCurrentTripQuery();
        $stmt = $this->conn->query($query);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ? $resultado['status'] : 'aguardando';
    }

    public function buscarViagemAtualId($motoristaId = null) {
        $query = $this->buildCurrentTripQuery(true, $motoristaId);
        $stmt = $this->conn->prepare($query);
        $params = [];

        if ($motoristaId) {
            $params['motorista_id'] = $motoristaId;
        }

        $stmt->execute($params);

        return $stmt->fetchColumn() ?: null;
    }

    private function criarViagemDoDia($motoristaId) {
        // Busca a empresa do motorista
        $stmtEmpresa = $this->conn->prepare("SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmtEmpresa->execute(['id' => $motoristaId]);
        $empresaId = $stmtEmpresa->fetchColumn();

        if (!$empresaId) {
            return null;
        }

        // Busca o primeiro horário base disponível para a empresa do motorista
        $sqlHorario = "SELECT hb.id 
                       FROM horarios_base hb
                       INNER JOIN linhas l ON l.id = hb.linha_id
                       WHERE l.empresa_id = :empresa_id
                       LIMIT 1";
        $stmtHorario = $this->conn->prepare($sqlHorario);
        $stmtHorario->execute(['empresa_id' => $empresaId]);
        $horarioBaseId = $stmtHorario->fetchColumn();

        // Busca o primeiro veículo disponível para a empresa do motorista
        $stmtVeiculo = $this->conn->prepare("SELECT id FROM veiculo WHERE empresa_id = :empresa_id LIMIT 1");
        $stmtVeiculo->execute(['empresa_id' => $empresaId]);
        $veiculoId = $stmtVeiculo->fetchColumn();

        if (!$horarioBaseId || !$veiculoId) {
            return null;
        }

        if ($this->driver === 'mysql') {
            $dataAtualSql = 'CURDATE()';
            $sql = "
                INSERT INTO viagens (horario_base_id, motorista_id, veiculo_id, status, data_viagem)
                VALUES (:horario_base_id, :motorista_id, :veiculo_id, 'aguardando', {$dataAtualSql})
                ON DUPLICATE KEY UPDATE status = status
            ";
        } else {
            $dataAtualSql = 'CURRENT_DATE';
            $sql = "
                INSERT INTO viagens (horario_base_id, motorista_id, veiculo_id, status, data_viagem)
                VALUES (:horario_base_id, :motorista_id, :veiculo_id, 'aguardando', {$dataAtualSql})
                ON CONFLICT ON CONSTRAINT uniq_viagem_dia DO UPDATE SET status = viagens.status
            ";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'horario_base_id' => $horarioBaseId,
            'motorista_id' => $motoristaId,
            'veiculo_id' => $veiculoId,
        ]);

        return $this->buscarViagemAtualId($motoristaId);
    }

    private function buildCurrentTripQuery($onlyId = false, $motoristaId = null) {
        $dateExpression = $this->driver === 'mysql' ? 'CURDATE()' : 'CURRENT_DATE';
        $select = $onlyId ? 'id' : 'status';
        $motoristaSql = $motoristaId ? ' AND motorista_id = :motorista_id' : '';

        return "
            SELECT {$select}
            FROM viagens
            WHERE data_viagem = {$dateExpression}
              {$motoristaSql}
            ORDER BY
                CASE status
                    WHEN 'em_rota' THEN 0
                    WHEN 'aguardando' THEN 1
                    WHEN 'finalizada' THEN 2
                    ELSE 3
                END,
                id DESC
            LIMIT 1
        ";
    }
}
?>
