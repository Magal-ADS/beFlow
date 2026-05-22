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

    public function buscarPorLinha($linhaId) {
        $query = "SELECT * FROM pontos WHERE linha_id = :linha_id ORDER BY ordem_na_linha ASC";
        $stmt = $this->conn->prepare($query);
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

        $query = "SELECT p.*, l.nome AS nome_linha, l.cor AS cor_linha
                  FROM pontos p
                  INNER JOIN linhas l ON l.id = p.linha_id
                  WHERE p.linha_id = :linha_id
                  ORDER BY p.ordem_na_linha ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['linha_id' => $viagemAtual['linha_id']]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarViagemAtual($motoristaId = null) {
        $query = $this->buildCurrentTripQuery(false, $motoristaId);
        $stmt = $this->conn->prepare($query);
        $params = [];

        if ($motoristaId !== null) {
            $params['motorista_id'] = $motoristaId;
        }

        $stmt->execute($params);
        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);

        return $viagem ?: null;
    }

    public function buscarViagemAtualId($motoristaId = null) {
        $viagem = $this->buscarViagemAtual($motoristaId);
        return $viagem ? $viagem['id'] : null;
    }

    public function configurarViagemDoDia($motoristaId, $linhaId, $numeroOnibus) {
        $numeroOnibus = trim($numeroOnibus);
        if ($numeroOnibus === '') {
            return false;
        }

        $stmtEmpresa = $this->conn->prepare("SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmtEmpresa->execute(['id' => $motoristaId]);
        $empresaId = (int) $stmtEmpresa->fetchColumn();

        if ($empresaId <= 0) {
            return false;
        }

        $stmtLinha = $this->conn->prepare("SELECT id FROM linhas WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
        $stmtLinha->execute(['id' => $linhaId, 'empresa_id' => $empresaId]);
        $linhaValida = $stmtLinha->fetchColumn();

        if (!$linhaValida) {
            return false;
        }

        $horarioBaseId = $this->obterOuCriarHorarioBase($linhaId);
        $veiculoId = $this->obterOuCriarVeiculoBase($empresaId);
        $viagemAtual = $this->buscarViagemAtual($motoristaId);

        if ($viagemAtual) {
            $stmt = $this->conn->prepare("
                UPDATE viagens
                SET horario_base_id = :horario_base_id,
                    linha_id = :linha_id,
                    veiculo_id = :veiculo_id,
                    numero_onibus = :numero_onibus
                WHERE id = :id
            ");

            return $stmt->execute([
                'horario_base_id' => $horarioBaseId,
                'linha_id' => $linhaId,
                'veiculo_id' => $veiculoId,
                'numero_onibus' => $numeroOnibus,
                'id' => $viagemAtual['id'],
            ]);
        }

        if ($this->driver === 'mysql') {
            $sql = "
                INSERT INTO viagens (horario_base_id, linha_id, motorista_id, veiculo_id, numero_onibus, status, data_viagem)
                VALUES (:horario_base_id, :linha_id, :motorista_id, :veiculo_id, :numero_onibus, 'aguardando', CURDATE())
            ";
        } else {
            $sql = "
                INSERT INTO viagens (horario_base_id, linha_id, motorista_id, veiculo_id, numero_onibus, status, data_viagem)
                VALUES (:horario_base_id, :linha_id, :motorista_id, :veiculo_id, :numero_onibus, 'aguardando', CURRENT_DATE)
            ";
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'horario_base_id' => $horarioBaseId,
            'linha_id' => $linhaId,
            'motorista_id' => $motoristaId,
            'veiculo_id' => $veiculoId,
            'numero_onibus' => $numeroOnibus,
        ]);
    }

    public function atualizarStatusViagem($status, $motoristaId = null) {
        $viagemAtual = $this->buscarViagemAtual($motoristaId);
        if (!$viagemAtual || empty($viagemAtual['linha_id']) || empty($viagemAtual['numero_onibus'])) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE viagens SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $status,
            'id' => $viagemAtual['id'],
        ]);
    }

    public function lerStatusViagem() {
        $viagem = $this->buscarViagemAtual();
        return $viagem ? $viagem['status'] : 'aguardando';
    }

    private function obterOuCriarHorarioBase($linhaId) {
        $stmt = $this->conn->prepare("SELECT id FROM horarios_base WHERE linha_id = :linha_id ORDER BY id ASC LIMIT 1");
        $stmt->execute(['linha_id' => $linhaId]);
        $horarioBaseId = $stmt->fetchColumn();

        if ($horarioBaseId) {
            return (int) $horarioBaseId;
        }

        $insert = $this->conn->prepare("INSERT INTO horarios_base (linha_id, turno, hora_saida_garagem) VALUES (:linha_id, :turno, :hora)");
        $insert->execute([
            'linha_id' => $linhaId,
            'turno' => 'Matutino',
            'hora' => '06:30:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function obterOuCriarVeiculoBase($empresaId) {
        $stmt = $this->conn->prepare("SELECT id FROM veiculo WHERE empresa_id = :empresa_id ORDER BY id ASC LIMIT 1");
        $stmt->execute(['empresa_id' => $empresaId]);
        $veiculoId = $stmt->fetchColumn();

        if ($veiculoId) {
            return (int) $veiculoId;
        }

        $placa = 'BEF-' . str_pad((string) $empresaId, 4, '0', STR_PAD_LEFT);
        $insert = $this->conn->prepare("INSERT INTO veiculo (numero_identificador, placa, empresa_id) VALUES (:numero_identificador, :placa, :empresa_id)");
        $insert->execute([
            'numero_identificador' => 'FROTA-BASE',
            'placa' => $placa,
            'empresa_id' => $empresaId,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function buildCurrentTripQuery($onlyId = false, $motoristaId = null) {
        $dateExpression = $this->driver === 'mysql' ? 'CURDATE()' : 'CURRENT_DATE';
        $select = $onlyId
            ? 'v.id'
            : 'v.*, l.nome AS nome_linha, l.cor AS cor_linha';
        $motoristaSql = $motoristaId !== null ? ' AND v.motorista_id = :motorista_id' : '';

        return "
            SELECT {$select}
            FROM viagens v
            INNER JOIN linhas l ON l.id = v.linha_id
            WHERE v.data_viagem = {$dateExpression}
              {$motoristaSql}
            ORDER BY
                CASE v.status
                    WHEN 'em_rota' THEN 0
                    WHEN 'em_volta' THEN 1
                    WHEN 'aguardando_volta' THEN 2
                    WHEN 'aguardando' THEN 3
                    WHEN 'finalizada' THEN 4
                    ELSE 5
                END,
                v.id DESC
            LIMIT 1
        ";
    }
}
?>
