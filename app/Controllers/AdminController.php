<?php
/**
 * BeFlow - Controlador Administrativo (AdminController.php)
 */

require_once __DIR__ . '/../../config/database.php';

class AdminController {
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin_empresa' && $_SESSION['tipo_usuario'] !== 'admin_geral')) {
            header("Location: /beFlow/login"); exit;
        }

        $db = (new Database())->getConnection();

        $stats = [
            'alunos'     => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'aluno'")->fetchColumn(),
            'motoristas' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'motorista'")->fetchColumn(),
            'pontos'     => $db->query("SELECT COUNT(*) FROM pontos")->fetchColumn(),
            'viagem_status' => $db->query("SELECT status FROM viagem_atual WHERE id = 1")->fetchColumn()
        ];

        require_once __DIR__ . '/../Views/admin_dashboard.php';
    }

    public function usuarios() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin_empresa' && $_SESSION['tipo_usuario'] !== 'admin_geral')) {
            header("Location: /beFlow/login"); exit;
        }

        $db = (new Database())->getConnection();
        $listaUsuarios = $db->query("SELECT * FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/admin_usuarios.php';
    }

    public function salvarUsuario() {
        header('Content-Type: application/json');
        
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo  = $_POST['tipo_usuario'] ?? 'aluno';
        $empresa_id = 1; 

        if (empty($nome) || empty($email) || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, empresa_id) 
                    VALUES (:nome, :email, :senha, :tipo, :empresa_id)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':empresa_id', $empresa_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuário cadastrado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar.']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23505 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro técnico: ' . $e->getMessage()]);
            }
        }
        exit;
    }

    // --- NOVO: MÉTODO PARA ATUALIZAR USUÁRIO EXISTENTE ---
    public function editarUsuario() {
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo  = $_POST['tipo_usuario'] ?? 'aluno';

        if (empty($id) || empty($nome) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'ID, Nome e E-mail são obrigatórios.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            
            // Se o admin digitou uma senha nova, a gente atualiza ela. Se não, atualiza só os outros dados.
            if (!empty($senha)) {
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, tipo_usuario = :tipo WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':senha', $senha);
            } else {
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, tipo_usuario = :tipo WHERE id = :id";
                $stmt = $db->prepare($sql);
            }
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro técnico: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- NOVO: MÉTODO PARA DELETAR USUÁRIO ---
    public function deletarUsuario() {
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuário removido da base de dados.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao deletar.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: Não foi possível excluir pois este usuário possui registros atrelados.']);
        }
        exit;
    }
}