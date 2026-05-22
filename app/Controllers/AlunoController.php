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

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosDaViagemAtual();
        $confirmacaoModel = new Confirmacao();
        $estadoConfirmacao = $confirmacaoModel->buscarEstadoAtual($_SESSION['usuario_id']);
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

        echo json_encode([
            'success' => $ok,
            'message' => $message,
            'state' => $confirmacaoModel->buscarEstadoAtual($_SESSION['usuario_id']),
        ]);
        exit;
    }

    public function checarStatusViagem() {
        header('Content-Type: application/json');

        $pontoModel = new Ponto();
        $status = $pontoModel->lerStatusViagem();

        echo json_encode(['status' => $status]);
        exit;
    }
}
