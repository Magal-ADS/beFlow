<?php
require_once __DIR__ . '/../Models/Ponto.php';

class MotoristaController {
    private function requireMotoristaJson() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }
    }

    public function index() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosComContagem($_SESSION['usuario_id']);
        $viagemAtual = $pontoModel->buscarViagemAtual($_SESSION['usuario_id']);
        $linhasDisponiveis = $pontoModel->buscarLinhasDaEmpresa(1);

        require_once __DIR__ . '/../Views/home_motorista.php';
    }

    public function apiPontos() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        echo json_encode($pontoModel->buscarPontosComContagem($_SESSION['usuario_id']));
        exit;
    }

    public function configurarViagem() {
        $this->requireMotoristaJson();

        $linhaId = trim($_POST['linha_id'] ?? '');
        $numeroOnibus = trim($_POST['numero_onibus'] ?? '');

        if ($linhaId === '' || $numeroOnibus === '') {
            echo json_encode(['success' => false, 'message' => 'Selecione a linha e informe o numero do onibus.']);
            exit;
        }

        $pontoModel = new Ponto();
        $ok = $pontoModel->configurarViagemDoDia($_SESSION['usuario_id'], (int) $linhaId, $numeroOnibus);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Viagem do dia configurada com sucesso.' : 'Nao foi possivel configurar a viagem do dia.',
        ]);
        exit;
    }

    public function iniciarRota() {
        $this->requireMotoristaJson();

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('em_rota', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Rota iniciada com sucesso.' : 'Configure a linha do dia antes de iniciar a rota.',
        ]);
        exit;
    }

    public function finalizarRota() {
        $this->requireMotoristaJson();

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('finalizada', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Rota finalizada com sucesso.' : 'Nao foi possivel finalizar a rota.',
        ]);
        exit;
    }
}
?>
