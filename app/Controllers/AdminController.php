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

        // CORREÇÃO: Busca o status na nova tabela 'viagens' e não mais na antiga 'viagem_atual'
        $statusViagem = $db->query("SELECT status FROM viagens ORDER BY id DESC LIMIT 1")->fetchColumn();

        $stats = [
            'alunos'     => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'aluno'")->fetchColumn(),
            'motoristas' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'motorista'")->fetchColumn(),
            'pontos'     => $db->query("SELECT COUNT(*) FROM pontos")->fetchColumn(),
            'viagem_status' => $statusViagem ?: 'Sem viagens ativas'
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
        
        $nome  = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo  = $_POST['tipo_usuario'] ?? 'aluno';
        $empresa_id = 1; 
        
        // Futuros campos do modal
        $turno  = $_POST['turno'] ?? 'Não informado';
        $escola = $_POST['escola'] ?? 'Não informada';

        if (empty($nome) || empty($email) || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            
            // Inicia a transação (se der erro em uma tabela, ele desfaz a outra)
            $db->beginTransaction();

            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, empresa_id) 
                    VALUES (:nome, :email, :senha, :tipo, :empresa_id)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            // Pega o ID gerado para amarrar a herança
            $usuario_id = $db->lastInsertId();

            // Se for aluno, insere também na tabela de alunos
            if ($tipo === 'aluno' && $usuario_id) {
                $sqlAluno = "INSERT INTO alunos (usuario_id, turno, escola) VALUES (:uid, :turno, :escola)";
                $stmtAluno = $db->prepare($sqlAluno);
                $stmtAluno->execute([
                    ':uid'   => $usuario_id,
                    ':turno' => $turno,
                    ':escola'=> $escola
                ]);
            }

            // Confirma as inserções
            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'Usuário cadastrado com sucesso!']);
            
        } catch (PDOException $e) {
            $db->rollBack(); // Desfaz tudo em caso de erro
            if ($e->getCode() == 23505 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro técnico: ' . $e->getMessage()]);
            }
        }
        exit;
    }

    public function editarUsuario() {
        header('Content-Type: application/json');
        
        $id    = $_POST['id'] ?? '';
        $nome  = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo  = $_POST['tipo_usuario'] ?? 'aluno';
        
        $turno  = $_POST['turno'] ?? 'Não informado';
        $escola = $_POST['escola'] ?? 'Não informada';

        if (empty($id) || empty($nome) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'ID, Nome e E-mail são obrigatórios.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $db->beginTransaction();
            
            // 1. Atualiza a tabela usuários
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
            $stmt->execute();

            // 2. Atualiza a tabela alunos (Herança)
            if ($tipo === 'aluno') {
                // Verifica se já existe o registro de aluno
                $check = $db->prepare("SELECT id FROM alunos WHERE usuario_id = :id");
                $check->execute([':id' => $id]);
                
                if ($check->rowCount() > 0) {
                    $upd = $db->prepare("UPDATE alunos SET turno = :turno, escola = :escola WHERE usuario_id = :id");
                    $upd->execute([':turno' => $turno, ':escola' => $escola, ':id' => $id]);
                } else {
                    $ins = $db->prepare("INSERT INTO alunos (usuario_id, turno, escola) VALUES (:id, :turno, :escola)");
                    $ins->execute([':id' => $id, ':turno' => $turno, ':escola' => $escola]);
                }
            } else {
                // Se o admin editou um "Aluno" e transformou em "Motorista", apaga o vínculo da tabela alunos
                $del = $db->prepare("DELETE FROM alunos WHERE usuario_id = :id");
                $del->execute([':id' => $id]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro técnico: ' . $e->getMessage()]);
        }
        exit;
    }

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

            // Graças ao ON DELETE CASCADE do banco, se deletar o usuário, apaga o aluno junto!
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

    // ==========================================================
    // MÉTODOS DE ROTAS E PONTOS
    // ==========================================================

    public function rotas() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin_empresa' && $_SESSION['tipo_usuario'] !== 'admin_geral')) {
            header("Location: /beFlow/login"); exit;
        }

        $db = (new Database())->getConnection();
        
        // Busca todas as linhas da empresa
        $linhas = $db->query("SELECT * FROM linhas WHERE empresa_id = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        // Busca todos os pontos ordenados pela ordem na linha
        $pontos = $db->query("SELECT * FROM pontos ORDER BY linha_id ASC, ordem_na_linha ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Junta os pontos dentro das suas respectivas linhas para facilitar a exibição
        $linhas_com_pontos = [];
        foreach ($linhas as $linha) {
            $linha['pontos'] = array_filter($pontos, function($p) use ($linha) {
                return $p['linha_id'] == $linha['id'];
            });
            $linhas_com_pontos[] = $linha;
        }

        require_once __DIR__ . '/../Views/admin_rotas.php';
    }
}