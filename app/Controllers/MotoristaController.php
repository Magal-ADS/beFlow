<?php
// Requisita o Model de Ponto
require_once __DIR__ . '/../Models/Ponto.php';

class MotoristaController {
    
    // Método 1: Exibe a tela do mapa HTML
    public function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            header("Location: /beFlow/login");
            exit;
        }

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosComContagem();

        require_once __DIR__ . '/../Views/home_motorista.php';
    }

    // Método 2: O Auto-Refresh (API que o JS vai chamar a cada 10 segundos)
    public function apiPontos() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosComContagem();
        
        echo json_encode($pontos);
        exit;
    }

    // =========================================================================
    // NOVOS MÉTODOS DE CONTROLE DA VIAGEM (Para notificar o aluno)
    // =========================================================================

    // Método 3: Motorista Inicia a Rota (Muda o status para 'em_rota')
    public function iniciarRota() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        // Atualiza o banco avisando que o ônibus saiu!
        $pontoModel->atualizarStatusViagem('em_rota');
        
        echo json_encode(['success' => true, 'message' => 'Rota iniciada com sucesso.']);
        exit;
    }

    // Método 4: Motorista Finaliza a Rota (Muda o status para 'finalizada')
    public function finalizarRota() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        // Atualiza o banco avisando que a viagem acabou
        $pontoModel->atualizarStatusViagem('finalizada');

        // Retorna sucesso para o Front-end saber que deu tudo certo
        echo json_encode(['success' => true, 'message' => 'Rota finalizada com sucesso.']);
        exit;
    }
}
?>