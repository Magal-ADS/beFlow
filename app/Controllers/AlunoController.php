<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Ponto.php';
require_once __DIR__ . '/../Models/Confirmacao.php';

class AlunoController {
    private $alunoLinhaColumnExists;

    public function index() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $confirmacaoModel = new Confirmacao();
        $db = (new Database())->getConnection();
        $alunoAtual = $this->buscarAlunoAtual($db, (int) $_SESSION['usuario_id']);
        $contextoViagemAluno = $confirmacaoModel->buscarContextoAtual($_SESSION['usuario_id']);
        $estadoConfirmacao = $contextoViagemAluno['state'] ?? null;

        $pontoModel = new Ponto();
        $linhasDisponiveis = $pontoModel->buscarLinhasDaEmpresa((int) ($alunoAtual['empresa_id'] ?? 0));
        $linhaSelecionadaId = !empty($alunoAtual['linha_id']) ? (int) $alunoAtual['linha_id'] : null;
        $pontos = $linhaSelecionadaId ? $pontoModel->buscarPorLinha($linhaSelecionadaId) : [];

        $stmtAluno = $db->prepare("SELECT nome, telefone, email FROM usuarios WHERE id = :id LIMIT 1");
        $stmtAluno->execute(['id' => $_SESSION['usuario_id']]);
        $currentAluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [
            'nome' => $_SESSION['usuario_nome'] ?? 'Aluno',
            'telefone' => '',
            'email' => '',
        ];

        require_once __DIR__ . '/../Views/home_aluno.php';
    }

    public function confirmar() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessao expirada. Faca login novamente.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $confirmacaoModel = new Confirmacao();
        $acao = $data['acao'] ?? 'confirmar_ponto';
        $ok = false;
        $message = 'Operacao invalida.';

        if ($acao === 'confirmar_ponto') {
            if (!isset($data['ponto_id'])) {
                echo json_encode(['success' => false, 'message' => 'Dados invalidos.']);
                exit;
            }

            $ok = $confirmacaoModel->registrar($_SESSION['usuario_id'], $data['ponto_id']);
            $message = $ok ? 'Presenca confirmada com sucesso.' : $confirmacaoModel->getLastError();
        } elseif ($acao === 'cancelar_ponto') {
            $ok = $confirmacaoModel->cancelar($_SESSION['usuario_id']);
            $message = $ok ? 'Voce pode escolher outro ponto de embarque.' : $confirmacaoModel->getLastError();
        } elseif ($acao === 'informar_embarque') {
            $ok = $confirmacaoModel->informarEmbarque($_SESSION['usuario_id']);
            $message = $ok ? 'Embarque informado. Agora diga se vai voltar de onibus.' : $confirmacaoModel->getLastError();
        } elseif ($acao === 'retorno_sim') {
            $ok = $confirmacaoModel->informarRetorno($_SESSION['usuario_id'], true);
            $message = $ok ? 'Retorno de onibus confirmado com sucesso.' : $confirmacaoModel->getLastError();
        } elseif ($acao === 'retorno_nao') {
            $ok = $confirmacaoModel->informarRetorno($_SESSION['usuario_id'], false);
            $message = $ok ? 'Retorno sem onibus registrado com sucesso.' : $confirmacaoModel->getLastError();
        }

        $contextoAtual = $confirmacaoModel->buscarContextoAtual($_SESSION['usuario_id']);

        if (!$ok && $confirmacaoModel->getLastHttpStatus() === 400) {
            http_response_code(400);
            echo json_encode([
                'erro' => $confirmacaoModel->getLastError(),
                'state' => $contextoAtual['state'] ?? null,
                'trip' => $contextoAtual['trip'] ?? null,
                'retorno_planejado' => (bool) ($contextoAtual['retorno_planejado'] ?? false),
                'hora_volta' => $contextoAtual['hora_volta'] ?? null,
            ]);
            exit;
        }

        echo json_encode([
            'success' => $ok,
            'message' => $message,
            'state' => $contextoAtual['state'] ?? null,
            'trip' => $contextoAtual['trip'] ?? null,
            'retorno_planejado' => (bool) ($contextoAtual['retorno_planejado'] ?? false),
            'hora_volta' => $contextoAtual['hora_volta'] ?? null,
        ]);
        exit;
    }

    public function selecionarLinha() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $linhaId = isset($data['linha_id']) && $data['linha_id'] !== '' ? (int) $data['linha_id'] : null;
        $db = (new Database())->getConnection();
        $alunoAtual = $this->buscarAlunoAtual($db, (int) $_SESSION['usuario_id']);

        if (!$alunoAtual) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Aluno nao encontrado.']);
            exit;
        }

        if ($linhaId !== null) {
            $stmtLinha = $db->prepare('SELECT id, nome FROM linhas WHERE id = :id AND empresa_id = :empresa_id LIMIT 1');
            $stmtLinha->execute([
                'id' => $linhaId,
                'empresa_id' => $alunoAtual['empresa_id'],
            ]);
            $linhaSelecionada = $stmtLinha->fetch(PDO::FETCH_ASSOC);

            if (!$linhaSelecionada) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Linha invalida para este aluno.']);
                exit;
            }
        }

        if ($this->hasAlunoLinhaColumn($db)) {
            $stmt = $db->prepare('UPDATE alunos SET linha_id = :linha_id WHERE usuario_id = :usuario_id');
            $stmt->bindValue(':linha_id', $linhaId, $linhaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', (int) $_SESSION['usuario_id'], PDO::PARAM_INT);
            $stmt->execute();
        }

        $_SESSION['aluno_linha_id'] = $linhaId;

        $pontoModel = new Ponto();
        $pontos = $linhaId ? $pontoModel->buscarPorLinha($linhaId) : [];

        echo json_encode([
            'success' => true,
            'message' => $linhaId ? 'Linha selecionada com sucesso.' : 'Selecao de linha removida.',
            'selected_line_id' => $linhaId,
            'points' => $pontos,
        ]);
        exit;
    }

    public function checarStatusViagem() {
        header('Content-Type: application/json');

        $confirmacaoModel = new Confirmacao();
        $contexto = $confirmacaoModel->buscarContextoAtual($_SESSION['usuario_id'] ?? 0);
        $trip = $contexto['trip'] ?? null;

        echo json_encode([
            'status' => $trip['status'] ?? 'aguardando',
            'trip' => $trip,
            'state' => $contexto['state'] ?? null,
            'retorno_planejado' => (bool) ($contexto['retorno_planejado'] ?? false),
            'hora_volta' => $contexto['hora_volta'] ?? null,
        ]);
        exit;
    }

    private function buscarAlunoAtual(PDO $db, int $usuarioId) {
        $linhaSql = $this->hasAlunoLinhaColumn($db) ? 'a.linha_id' : 'NULL AS linha_id';
        $stmt = $db->prepare("
            SELECT a.id, {$linhaSql}, u.empresa_id
            FROM alunos a
            INNER JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.usuario_id = :usuario_id
            LIMIT 1
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);

        $aluno = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($aluno && array_key_exists('aluno_linha_id', $_SESSION)) {
            $aluno['linha_id'] = $_SESSION['aluno_linha_id'];
        }

        return $aluno;
    }

    private function hasAlunoLinhaColumn(PDO $db) {
        if ($this->alunoLinhaColumnExists !== null) {
            return $this->alunoLinhaColumnExists;
        }

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $stmt = $db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = CURRENT_SCHEMA()
                  AND table_name = 'alunos'
                  AND column_name = 'linha_id'
                LIMIT 1
            ");
        } else {
            $stmt = $db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'alunos'
                  AND column_name = 'linha_id'
                LIMIT 1
            ");
        }

        $stmt->execute();
        $this->alunoLinhaColumnExists = (bool) $stmt->fetchColumn();

        return $this->alunoLinhaColumnExists;
    }
}
?>
