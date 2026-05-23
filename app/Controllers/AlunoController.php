<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Ponto.php';
require_once __DIR__ . '/../Models/Confirmacao.php';

class AlunoController {
    public function index() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $confirmacaoModel = new Confirmacao();
        $contextoViagemAluno = $confirmacaoModel->buscarContextoAtual($_SESSION['usuario_id']);
        $estadoConfirmacao = $contextoViagemAluno['state'] ?? null;
        $viagemAtualAluno = $contextoViagemAluno['trip'] ?? null;

        $pontoModel = new Ponto();
        if ($viagemAtualAluno && !empty($viagemAtualAluno['linha_id'])) {
            $pontos = $pontoModel->buscarPorLinha((int) $viagemAtualAluno['linha_id']);
        } else {
            $pontos = $pontoModel->buscarPontosDaViagemAtual();
        }

        $db = (new Database())->getConnection();
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
}
?>
