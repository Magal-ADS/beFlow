<?php
require_once __DIR__ . '/../../config/database.php';

class Usuario {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function buscarPorEmail($email) {
        $query = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarTodosComDadosDeAluno() {
        $sql = "SELECT u.*, a.turno, a.escola
                FROM usuarios u
                LEFT JOIN alunos a ON a.usuario_id = u.id
                ORDER BY u.nome ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar($dados) {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, empresa_id)
                    VALUES (:nome, :email, :senha, :tipo, :empresa_id)";
            $stmt = $this->conn->prepare($sql);
            
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'senha' => $senhaHash,
                'tipo' => $dados['tipo_usuario'],
                'empresa_id' => $dados['empresa_id'] ?? 1,
            ]);

            $usuarioId = $this->conn->lastInsertId();

            if ($dados['tipo_usuario'] === 'aluno' && $usuarioId) {
                $this->salvarOuAtualizarAluno($usuarioId, $dados['turno'], $dados['escola']);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function atualizar($id, $dados) {
        try {
            $this->conn->beginTransaction();

            if (!empty($dados['senha'])) {
                $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, tipo_usuario = :tipo WHERE id = :id";
                $params = [
                    'nome' => $dados['nome'],
                    'email' => $dados['email'],
                    'senha' => $senhaHash,
                    'tipo' => $dados['tipo_usuario'],
                    'id' => $id
                ];
            } else {
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, tipo_usuario = :tipo WHERE id = :id";
                $params = [
                    'nome' => $dados['nome'],
                    'email' => $dados['email'],
                    'tipo' => $dados['tipo_usuario'],
                    'id' => $id
                ];
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            if ($dados['tipo_usuario'] === 'aluno') {
                $this->salvarOuAtualizarAluno($id, $dados['turno'], $dados['escola']);
            } else {
                $stmtDelete = $this->conn->prepare("DELETE FROM alunos WHERE usuario_id = :usuario_id");
                $stmtDelete->execute(['usuario_id' => $id]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function excluir($id) {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function salvarOuAtualizarAluno($usuarioId, $turno, $escola) {
        $stmtCheck = $this->conn->prepare("SELECT id FROM alunos WHERE usuario_id = :usuario_id LIMIT 1");
        $stmtCheck->execute(['usuario_id' => $usuarioId]);
        
        if ($stmtCheck->fetchColumn()) {
            $sql = "UPDATE alunos SET turno = :turno, escola = :escola WHERE usuario_id = :usuario_id";
        } else {
            $sql = "INSERT INTO alunos (usuario_id, turno, escola) VALUES (:usuario_id, :turno, :escola)";
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'usuario_id' => $usuarioId,
            'turno' => $turno,
            'escola' => $escola
        ]);
    }
}
