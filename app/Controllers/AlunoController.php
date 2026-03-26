<?php
// Requisita os Models necessários para lidar com pontos e confirmações
require_once __DIR__ . '/../Models/Ponto.php';
require_once __DIR__ . '/../Models/Confirmacao.php';

class AlunoController {
    
    /**
     * Método principal para exibir a tela do mapa (Home do Aluno)
     */
    public function index() {
        // Inicia a sessão para checar quem está acessando
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Proteção de Rota: Se não for aluno, chuta pro login
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
            header("Location: /beFlow/login");
            exit;
        }

        // Instancia o Model de Ponto para buscar as paradas no banco
        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarTodos();

        // Carrega a View do Aluno e disponibiliza a variável $pontos
        require_once __DIR__ . '/../Views/home_aluno.php';
    }

    /**
     * Método para processar a confirmação de presença (via AJAX/Fetch)
     * Responde estritamente em JSON para o Front-end
     */
    public function confirmar() {
        // ESSENCIAL: Avisa ao navegador que a resposta é um JSON
        // Isso evita o erro de "Unexpected token <" no JavaScript
        header('Content-Type: application/json');

        // Garante que a sessão está ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado antes de processar
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
            exit;
        }

        // Recebe os dados JSON enviados pelo JavaScript
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        // Valida se o ID do ponto chegou certinho
        if (isset($data['ponto_id'])) {
            $ponto_id = $data['ponto_id'];
            $usuario_id = $_SESSION['usuario_id'];

            // Instancia o Model de Confirmação
            $confirmacaoModel = new Confirmacao();
            
            // Tenta registrar no banco de dados
            if ($confirmacaoModel->registrar($usuario_id, $ponto_id)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Presença confirmada! O motorista já recebeu seu aviso.'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao salvar no banco. Verifique se você já não confirmou hoje.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Dados inválidos. Tente recarregar a página.'
            ]);
        }
        
        // Finaliza a execução para garantir que nenhum HTML "suje" o JSON
        exit;
    }

    // =========================================================================
    // NOVO MÉTODO: O celular do aluno vai bater aqui para saber onde o ônibus tá
    // =========================================================================
    public function checarStatusViagem() {
        header('Content-Type: application/json');
        
        // Instancia o PontoModel que já requisitamos lá no topo do arquivo
        $pontoModel = new Ponto(); 
        
        // Puxa o status atual lá do banco de dados (aquela tabela VIAGEM_ATUAL)
        $status = $pontoModel->lerStatusViagem();
        
        // Devolve o status no formato que o JavaScript consegue ler
        echo json_encode(['status' => $status]);
        exit;
    }
}
?>