<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';

class AdminController {
    public function index() {
        $this->requireAdminSession();

        $db = (new Database())->getConnection();
        $statusViagem = $db->query("SELECT status FROM viagens ORDER BY data_viagem DESC, id DESC LIMIT 1")->fetchColumn();

        $stats = [
            'alunos' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'aluno'")->fetchColumn(),
            'motoristas' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'motorista'")->fetchColumn(),
            'pontos' => $db->query("SELECT COUNT(*) FROM pontos")->fetchColumn(),
            'viagem_status' => $statusViagem ?: 'Sem viagens ativas',
        ];

        require_once __DIR__ . '/../Views/admin_dashboard.php';
    }

    public function usuarios() {
        $this->requireAdminSession();

        $usuarioModel = new Usuario();
        $listaUsuarios = $usuarioModel->buscarTodosComDadosDeAluno();

        require_once __DIR__ . '/../Views/admin_usuarios.php';
    }

    public function salvarUsuario() {
        $this->requireAdminSessionJson();

        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'senha' => trim($_POST['senha'] ?? ''),
            'tipo_usuario' => $_POST['tipo_usuario'] ?? 'aluno',
            'turno' => trim($_POST['turno'] ?? 'Nao informado'),
            'escola' => trim($_POST['escola'] ?? 'Nao informada'),
        ];

        if ($dados['nome'] === '' || $dados['email'] === '' || $dados['senha'] === '') {
            $this->jsonResponse(false, 'Preencha todos os campos obrigatórios.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->salvar($dados);
            $this->jsonResponse(true, 'Usuário cadastrado com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Este e-mail já está cadastrado.'
                : 'Erro técnico ao cadastrar o usuário.';

            $this->jsonResponse(false, $message);
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    public function editarUsuario() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'senha' => trim($_POST['senha'] ?? ''),
            'tipo_usuario' => $_POST['tipo_usuario'] ?? 'aluno',
            'turno' => trim($_POST['turno'] ?? 'Nao informado'),
            'escola' => trim($_POST['escola'] ?? 'Nao informada'),
        ];

        if ($id === '' || $dados['nome'] === '' || $dados['email'] === '') {
            $this->jsonResponse(false, 'ID, nome e e-mail são obrigatórios.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->atualizar($id, $dados);
            $this->jsonResponse(true, 'Usuário atualizado com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Já existe outro usuário com este e-mail.'
                : 'Erro técnico ao atualizar o usuário.';

            $this->jsonResponse(false, $message);
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    public function deletarUsuario() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID inválido.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->excluir($id);
            $this->jsonResponse(true, 'Usuário removido da base de dados.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Não foi possível excluir este usuário.');
        }
    }

    public function rotas() {
        $this->requireAdminSession();

        $db = (new Database())->getConnection();
        $linhas = $db->query("SELECT * FROM linhas WHERE empresa_id = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pontos = $db->query("SELECT * FROM pontos ORDER BY linha_id ASC, ordem_na_linha ASC")->fetchAll(PDO::FETCH_ASSOC);

        $linhas_com_pontos = [];
        foreach ($linhas as $linha) {
            $linha['pontos'] = array_values(array_filter($pontos, function ($ponto) use ($linha) {
                return (int) $ponto['linha_id'] === (int) $linha['id'];
            }));
            $linhas_com_pontos[] = $linha;
        }

        require_once __DIR__ . '/../Views/admin_rotas.php';
    }

    public function salvarLinha() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            $this->jsonResponse(false, 'Informe o nome da rota.');
        }

        try {
            $db = (new Database())->getConnection();

            if ($id !== '') {
                $stmt = $db->prepare("UPDATE linhas SET nome = :nome WHERE id = :id");
                $stmt->execute(['nome' => $nome, 'id' => $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO linhas (nome, empresa_id) VALUES (:nome, :empresa_id)");
                $stmt->execute(['nome' => $nome, 'empresa_id' => 1]);
            }

            $this->jsonResponse(true, 'Rota salva com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao salvar a rota.');
        }
    }

    public function deletarLinha() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID da rota inválido.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM linhas WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->jsonResponse(true, 'Rota removida com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover a rota.');
        }
    }

    public function salvarPonto() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        $linhaId = trim($_POST['linha_id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $ordem = trim($_POST['ordem'] ?? '');

        if ($linhaId === '' || $nome === '' || $latitude === '' || $longitude === '' || $ordem === '') {
            $this->jsonResponse(false, 'Preencha todos os dados do ponto.');
        }

        try {
            $db = (new Database())->getConnection();

            if ($id !== '') {
                $stmt = $db->prepare("
                    UPDATE pontos
                    SET linha_id = :linha_id, nome = :nome, latitude = :latitude, longitude = :longitude, ordem_na_linha = :ordem
                    WHERE id = :id
                ");
                $stmt->execute([
                    'linha_id' => $linhaId,
                    'nome' => $nome,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'ordem' => $ordem,
                    'id' => $id,
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO pontos (linha_id, nome, latitude, longitude, ordem_na_linha)
                    VALUES (:linha_id, :nome, :latitude, :longitude, :ordem)
                ");
                $stmt->execute([
                    'linha_id' => $linhaId,
                    'nome' => $nome,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'ordem' => $ordem,
                ]);
            }

            $this->jsonResponse(true, 'Ponto salvo com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao salvar o ponto.');
        }
    }

    public function deletarPonto() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID do ponto inválido.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM pontos WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->jsonResponse(true, 'Ponto removido com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover the ponto.');
        }
    }

    private function requireAdminSession() {
        if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo_usuario'], ['admin_empresa', 'admin_geral'], true)) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function requireAdminSessionJson() {
        header('Content-Type: application/json');
        $this->requireAdminSession();
    }

    private function jsonResponse($success, $message) {
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        exit;
    }

    private function isDuplicateKey(PDOException $e) {
        $message = $e->getMessage();
        $code = (string) $e->getCode();

        return in_array($code, ['23000', '23505'], true)
            || stripos($message, 'duplicate') !== false
            || stripos($message, 'unique') !== false;
    }
}
