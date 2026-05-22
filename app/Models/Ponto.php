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
        if ($motoristaId !== null) {
            $viagemDoMotorista = $this->buscarViagemDoMotorista($motoristaId);
            if (!$viagemDoMotorista) {
                return null;
            }

            $viagem = $this->buscarViagemDoDiaPorLinha((int) $viagemDoMotorista['linha_id']) ?: $viagemDoMotorista;
            if ($viagem && (int) $viagem['id'] !== (int) $viagemDoMotorista['id']) {
                $this->sincronizarViagensDuplicadas($viagem, $viagemDoMotorista, (int) $motoristaId);
                $viagem = $this->buscarViagemPorId((int) $viagem['id']) ?: $viagem;
            }

            return $this->normalizarStatusViagem($viagem);
        }

        $query = $this->buildCurrentTripQuery(false, null);
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->normalizarStatusViagem($viagem ?: null);
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
        $viagemDoMotorista = $this->buscarViagemDoMotorista($motoristaId);
        $viagemDaLinha = $this->buscarViagemDoDiaPorLinha($linhaId);

        if ($viagemDaLinha) {
            if ($viagemDoMotorista && (int) $viagemDoMotorista['id'] !== (int) $viagemDaLinha['id']) {
                $this->sincronizarViagensDuplicadas($viagemDaLinha, $viagemDoMotorista, (int) $motoristaId);
                $viagemDaLinha = $this->buscarViagemPorId((int) $viagemDaLinha['id']) ?: $viagemDaLinha;
                $viagemDoMotorista = null;
            }

            $stmt = $this->conn->prepare("
                UPDATE viagens
                SET horario_base_id = :horario_base_id,
                    linha_id = :linha_id,
                    motorista_id = :motorista_id,
                    veiculo_id = :veiculo_id,
                    numero_onibus = :numero_onibus,
                    status = CASE WHEN status = 'finalizada' THEN 'aguardando' ELSE status END
                WHERE id = :id
            ");

            $ok = $stmt->execute([
                'horario_base_id' => $horarioBaseId,
                'linha_id' => $linhaId,
                'motorista_id' => $motoristaId,
                'veiculo_id' => $veiculoId,
                'numero_onibus' => $numeroOnibus,
                'id' => $viagemDaLinha['id'],
            ]);

            return $ok;
        }

        if ($viagemDoMotorista) {
            $stmt = $this->conn->prepare("
                UPDATE viagens
                SET horario_base_id = :horario_base_id,
                    linha_id = :linha_id,
                    veiculo_id = :veiculo_id,
                    numero_onibus = :numero_onibus,
                    status = CASE WHEN status = 'finalizada' THEN 'aguardando' ELSE status END
                WHERE id = :id
            ");

            return $stmt->execute([
                'horario_base_id' => $horarioBaseId,
                'linha_id' => $linhaId,
                'veiculo_id' => $veiculoId,
                'numero_onibus' => $numeroOnibus,
                'id' => $viagemDoMotorista['id'],
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

    private function buscarViagemDoMotorista($motoristaId) {
        $query = $this->buildCurrentTripQuery(false, $motoristaId);
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['motorista_id' => $motoristaId]);

        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);
        return $viagem ?: null;
    }

    private function buscarViagemDoDiaPorLinha($linhaId) {
        $dateExpression = $this->driver === 'mysql' ? 'CURDATE()' : 'CURRENT_DATE';
        $stmt = $this->conn->prepare("
            SELECT v.*, l.nome AS nome_linha, l.cor AS cor_linha,
                   (
                       SELECT COUNT(*)
                       FROM confirmacoes c
                       WHERE c.viagem_id = v.id
                   ) AS total_confirmacoes
            FROM viagens v
            INNER JOIN linhas l ON l.id = v.linha_id
            WHERE v.data_viagem = {$dateExpression}
              AND v.linha_id = :linha_id
            ORDER BY
                total_confirmacoes DESC,
                CASE v.status
                    WHEN 'em_rota' THEN 0
                    WHEN 'em_volta' THEN 1
                    WHEN 'aguardando_encerramento' THEN 2
                    WHEN 'aguardando_volta' THEN 3
                    WHEN 'aguardando' THEN 4
                    WHEN 'finalizada' THEN 5
                    ELSE 6
                END,
                v.id ASC
            LIMIT 1
        ");
        $stmt->execute(['linha_id' => $linhaId]);

        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);
        return $viagem ?: null;
    }

    private function buscarViagemPorId($viagemId) {
        $stmt = $this->conn->prepare("
            SELECT v.*, l.nome AS nome_linha, l.cor AS cor_linha
            FROM viagens v
            INNER JOIN linhas l ON l.id = v.linha_id
            WHERE v.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $viagemId]);

        $viagem = $stmt->fetch(PDO::FETCH_ASSOC);
        return $viagem ?: null;
    }

    private function arquivarViagemDuplicada($viagemId) {
        $stmtContagem = $this->conn->prepare('SELECT COUNT(*) FROM confirmacoes WHERE viagem_id = :viagem_id');
        $stmtContagem->execute(['viagem_id' => $viagemId]);
        $totalConfirmacoes = (int) $stmtContagem->fetchColumn();

        if ($totalConfirmacoes === 0) {
            $stmtDelete = $this->conn->prepare('DELETE FROM viagens WHERE id = :id');
            $stmtDelete->execute(['id' => $viagemId]);
            return;
        }

        $stmt = $this->conn->prepare("UPDATE viagens SET status = 'finalizada' WHERE id = :id");
        $stmt->execute(['id' => $viagemId]);
    }

    private function sincronizarViagensDuplicadas(array $viagemCanonica, array $viagemDoMotorista, $motoristaId) {
        if ((int) $viagemCanonica['id'] === (int) $viagemDoMotorista['id']) {
            return;
        }

        $statusSincronizado = $this->normalizarStatusValor($viagemDoMotorista['status'] ?? '');
        $statusCanonico = $this->normalizarStatusValor($viagemCanonica['status'] ?? '');

        if ($statusSincronizado === 'aguardando' && $statusCanonico !== 'aguardando') {
            $statusSincronizado = $statusCanonico;
        }

        try {
            $this->conn->beginTransaction();

            $stmtDeleteDuplicadas = $this->conn->prepare("
                DELETE FROM confirmacoes origem
                USING confirmacoes destino
                WHERE origem.viagem_id = :origem_viagem_id
                  AND destino.viagem_id = :destino_viagem_id
                  AND origem.aluno_id = destino.aluno_id
                  AND origem.ponto_id = destino.ponto_id
                  AND origem.tipo = destino.tipo
            ");
            $stmtDeleteDuplicadas->execute([
                'origem_viagem_id' => $viagemDoMotorista['id'],
                'destino_viagem_id' => $viagemCanonica['id'],
            ]);

            $stmtMove = $this->conn->prepare("
                UPDATE confirmacoes
                SET viagem_id = :destino_viagem_id
                WHERE viagem_id = :origem_viagem_id
            ");
            $stmtMove->execute([
                'destino_viagem_id' => $viagemCanonica['id'],
                'origem_viagem_id' => $viagemDoMotorista['id'],
            ]);

            $stmtDeleteTrip = $this->conn->prepare('DELETE FROM viagens WHERE id = :id');
            $stmtDeleteTrip->execute(['id' => $viagemDoMotorista['id']]);

            $stmtUpdate = $this->conn->prepare("
                UPDATE viagens
                SET motorista_id = :motorista_id,
                    horario_base_id = :horario_base_id,
                    veiculo_id = :veiculo_id,
                    numero_onibus = :numero_onibus,
                    status = :status
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'motorista_id' => $motoristaId,
                'horario_base_id' => $viagemDoMotorista['horario_base_id'],
                'veiculo_id' => $viagemDoMotorista['veiculo_id'],
                'numero_onibus' => $viagemDoMotorista['numero_onibus'],
                'status' => $statusSincronizado,
                'id' => $viagemCanonica['id'],
            ]);

            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        }
    }

    private function normalizarStatusViagem($viagem) {
        if (!$viagem) {
            return null;
        }

        $viagem['status'] = $this->normalizarStatusValor($viagem['status'] ?? '');

        return $viagem;
    }

    private function normalizarStatusValor($status) {
        if ($status === 'aguardando_volta') {
            return 'aguardando_encerramento';
        }

        if ($status === 'em_volta') {
            return 'em_rota';
        }

        return $status;
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
                    WHEN 'aguardando_encerramento' THEN 2
                    WHEN 'aguardando_volta' THEN 3
                    WHEN 'aguardando' THEN 4
                    WHEN 'finalizada' THEN 5
                    ELSE 6
                END,
                v.id DESC
            LIMIT 1
        ";
    }
}
?>
