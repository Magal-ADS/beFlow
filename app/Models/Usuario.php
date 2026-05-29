<?php
require_once __DIR__ . '/../../config/database.php';

class Usuario {
    private $conn;
    private $alunoLinhaColumnExists;

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

    public function buscarPorId($id) {
        $stmt = $this->conn->prepare("SELECT id, nome, email, telefone, tipo_usuario, empresa_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarTodosComDadosDeAluno() {
        $linhaSql = $this->hasAlunoLinhaColumn() ? 'a.linha_id' : 'NULL AS linha_id';
        $sql = "SELECT u.*, a.turno, a.escola, {$linhaSql}
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
                $this->salvarOuAtualizarAluno($usuarioId, $dados['turno'], $dados['escola'], $dados['linha_id'] ?? null);
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
                $this->salvarOuAtualizarAluno($id, $dados['turno'], $dados['escola'], $dados['linha_id'] ?? null);
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

    public function atualizarPerfil($id, $dados) {
        $usuarioAtual = $this->buscarPorId($id);
        if (!$usuarioAtual) {
            throw new RuntimeException('Usuario nao encontrado.');
        }

        $stmtEmail = $this->conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1");
        $stmtEmail->execute([
            'email' => $dados['email'],
            'id' => $id,
        ]);

        if ($stmtEmail->fetchColumn()) {
            throw new RuntimeException('Ja existe outro usuario com este e-mail.');
        }

        if (!empty($dados['senha'])) {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, senha = :senha WHERE id = :id";
            $params = [
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'id' => $id,
            ];
        } else {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone WHERE id = :id";
            $params = [
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'id' => $id,
            ];
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    private function salvarOuAtualizarAluno($usuarioId, $turno, $escola, $linhaId = null) {
        $stmtCheck = $this->conn->prepare("SELECT id FROM alunos WHERE usuario_id = :usuario_id LIMIT 1");
        $stmtCheck->execute(['usuario_id' => $usuarioId]);
        
        if ($stmtCheck->fetchColumn()) {
            $sql = $this->hasAlunoLinhaColumn()
                ? "UPDATE alunos SET turno = :turno, escola = :escola, linha_id = :linha_id WHERE usuario_id = :usuario_id"
                : "UPDATE alunos SET turno = :turno, escola = :escola WHERE usuario_id = :usuario_id";
        } else {
            $sql = $this->hasAlunoLinhaColumn()
                ? "INSERT INTO alunos (usuario_id, turno, escola, linha_id) VALUES (:usuario_id, :turno, :escola, :linha_id)"
                : "INSERT INTO alunos (usuario_id, turno, escola) VALUES (:usuario_id, :turno, :escola)";
        }

        $stmt = $this->conn->prepare($sql);
        $params = [
            'usuario_id' => $usuarioId,
            'turno' => $turno,
            'escola' => $escola,
        ];

        if ($this->hasAlunoLinhaColumn()) {
            $params['linha_id'] = $linhaId !== '' ? $linhaId : null;
        }

        return $stmt->execute($params);
    }

    private function hasAlunoLinhaColumn() {
        if ($this->alunoLinhaColumnExists !== null) {
            return $this->alunoLinhaColumnExists;
        }

        $driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $stmt = $this->conn->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = CURRENT_SCHEMA()
                  AND table_name = 'alunos'
                  AND column_name = 'linha_id'
                LIMIT 1
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'alunos'
                  AND column_name = 'linha_id'
                LIMIT 1
            ");
        }

        $stmt->execute();
        $this->alunoLinhaColumnExists = (bool) $stmt->fetchColumn();

        return $this->alunoLinhaColumnExists;
    }
}
