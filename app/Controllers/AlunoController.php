<?php
require_once __DIR__ . '/../Models/Ponto.php';
require_once __DIR__ . '/../Models/Confirmacao.php';

class AlunoController {
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
            header('Location: /beFlow/login');
            exit;
        }

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarTodos();

        require_once __DIR__ . '/../Views/home_aluno.php';
    }

    public function confirmar() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessao expirada. Faca login novamente.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['ponto_id'])) {
            echo json_encode(['success' => false, 'message' => 'Dados invalidos.']);
            exit;
        }

        $confirmacaoModel = new Confirmacao();
        $ok = $confirmacaoModel->registrar($_SESSION['usuario_id'], $data['ponto_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? 'Presenca confirmada. O motorista ja recebeu seu aviso.'
                : $confirmacaoModel->getLastError(),
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
