<?php
// Requisita a conexão com o banco de dados
require_once __DIR__ . '/../../config/database.php';

class Ponto {
    private $conn;

    public function __construct() {
        // Inicializa a conexão via PDO
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Busca todos os pontos de uma linha específica
     */
    public function buscarPorLinha($linha_id) {
        $query = "SELECT * FROM PONTOS 
                  WHERE linha_id = :linha_id 
                  ORDER BY ordem_na_linha ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':linha_id', $linha_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos os pontos e o nome da linha correspondente
     */
    public function buscarTodos() {
        $query = "SELECT p.*, l.nome as nome_linha 
                  FROM PONTOS p 
                  INNER JOIN LINHAS l ON p.linha_id = l.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NOVO MÉTODO: Busca pontos com a contagem de alunos confirmados HOJE
     * Essencial para a tela do motorista (RF3)
     */
    public function buscarPontosComContagem() {
        // Pegamos a data atual do servidor para filtrar as confirmações
        $data_hoje = date('Y-m-d');

        // Esta query faz um LEFT JOIN com a tabela de confirmações, 
        // mas apenas para os registros do dia atual.
        $query = "SELECT p.*, l.nome as nome_linha, 
                  (SELECT COUNT(*) FROM CONFIRMACOES c 
                   WHERE c.ponto_id = p.id AND c.data_confirmacao = :data_hoje) as total_alunos
                  FROM PONTOS p 
                  INNER JOIN LINHAS l ON p.linha_id = l.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_hoje', $data_hoje);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // NOVAS FUNÇÕES DE ESTADO DA VIAGEM (Para notificar o aluno)
    // =========================================================================

    /**
     * Atualiza o status atual da viagem no banco de dados
     * Status esperados: 'aguardando', 'em_rota', 'finalizada'
     */
    public function atualizarStatusViagem($status) {
        $query = "UPDATE VIAGEM_ATUAL SET status = :status WHERE id = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    /**
     * Lê o status atual da viagem (para o celular do aluno saber se o ônibus saiu)
     */
    public function lerStatusViagem() {
        $query = "SELECT status FROM VIAGEM_ATUAL WHERE id = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não achar nada no banco, assume por padrão que está 'aguardando'
        return $resultado ? $resultado['status'] : 'aguardando';
    }
}
?>