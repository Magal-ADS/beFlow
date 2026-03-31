<?php
require_once __DIR__ . '/../Models/Ponto.php';

class MotoristaController {
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            header('Location: /beFlow/login');
            exit;
        }

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosComContagem();

        require_once __DIR__ . '/../Views/home_motorista.php';
    }

    public function apiPontos() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        echo json_encode($pontoModel->buscarPontosComContagem());
        exit;
    }

    public function iniciarRota() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('em_rota', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Rota iniciada com sucesso.' : 'Nao foi possivel iniciar a rota.',
        ]);
        exit;
    }

    public function finalizarRota() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('finalizada', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Rota finalizada com sucesso.' : 'Nao foi possivel finalizar a rota.',
        ]);
        exit;
    }
}
