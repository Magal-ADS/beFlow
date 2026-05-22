<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';

class AdminController {
    private $allowedColors = ['azul', 'vermelha', 'amarela', 'verde'];

    public function index() {
        $this->requireAdminSession();

        $db = (new Database())->getConnection();
        $stmtAdmin = $db->prepare("SELECT nome, telefone, email FROM usuarios WHERE id = :id LIMIT 1");
        $stmtAdmin->execute(['id' => $_SESSION['usuario_id']]);
        $currentAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC) ?: [
            'nome' => $_SESSION['usuario_nome'] ?? 'Usuario',
            'telefone' => '',
            'email' => '',
        ];
        $statusViagem = $db->query("SELECT status FROM viagens ORDER BY data_viagem DESC, id DESC LIMIT 1")->fetchColumn();
        $usuariosTotal = (int) $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $motoristasTotal = (int) $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'motorista' AND empresa_id = 1")->fetchColumn();
        $linhas = $db->query("SELECT * FROM linhas WHERE empresa_id = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $linhasTotal = count($linhas);
        $onibusTotal = (int) $db->query("SELECT COUNT(*) FROM veiculo WHERE empresa_id = 1")->fetchColumn();
        $pontosTotal = (int) $db->query("SELECT COUNT(*) FROM pontos")->fetchColumn();
        $horariosTotal = (int) $db->query("SELECT COUNT(*) FROM horarios_base")->fetchColumn();
        $empresasTotal = (int) $db->query("SELECT COUNT(*) FROM empresa")->fetchColumn();
        $viagensTotal = (int) $db->query("SELECT COUNT(*) FROM viagens")->fetchColumn();
        $confirmacoesTotal = (int) $db->query("SELECT COUNT(*) FROM confirmacoes")->fetchColumn();
        $pontos = $db->query("SELECT * FROM pontos ORDER BY linha_id ASC, ordem_na_linha ASC")->fetchAll(PDO::FETCH_ASSOC);

        $linhasComPontos = [];
        foreach ($linhas as $linha) {
            $linha['pontos'] = array_values(array_filter($pontos, function ($ponto) use ($linha) {
                return (int) $ponto['linha_id'] === (int) $linha['id'];
            }));
            $linhasComPontos[] = $linha;
        }
        $linhas_com_pontos = $linhasComPontos;

        $stats = [
            'alunos' => $usuariosTotal,
            'usuarios' => $usuariosTotal,
            'motoristas' => $motoristasTotal,
            'linhas_onibus' => $linhasTotal . '/' . $onibusTotal,
            'pontos' => $pontosTotal,
            'horarios' => $horariosTotal,
            'empresas' => $empresasTotal,
            'viagem_status' => $statusViagem ?: 'Sem viagens ativas',
        ];

        $dashboardCards = [
            [
                'title' => 'Usuarios',
                'value' => (string) $usuariosTotal,
                'change' => 'Base atual do sistema',
                'icon_bg' => 'bg-blue-100',
                'icon_fg' => 'text-blue-600',
                'icon' => 'users',
            ],
            [
                'title' => 'Linhas e Onibus',
                'value' => $linhasTotal . '/' . $onibusTotal,
                'change' => 'Operacao cadastrada',
                'icon_bg' => 'bg-emerald-100',
                'icon_fg' => 'text-emerald-600',
                'icon' => 'bus',
            ],
            [
                'title' => 'Pontos de parada',
                'value' => (string) $pontosTotal,
                'change' => 'Mapa operacional',
                'icon_bg' => 'bg-violet-100',
                'icon_fg' => 'text-violet-600',
                'icon' => 'pin',
            ],
            [
                'title' => 'Horarios das linhas',
                'value' => (string) $horariosTotal,
                'change' => 'Agenda configurada',
                'icon_bg' => 'bg-amber-100',
                'icon_fg' => 'text-amber-600',
                'icon' => 'clock',
            ],
            [
                'title' => 'Empresas',
                'value' => (string) $empresasTotal,
                'change' => 'Cadastro institucional',
                'icon_bg' => 'bg-slate-200',
                'icon_fg' => 'text-slate-700',
                'icon' => 'building',
            ],
        ];

        $summarySeries = [
            ['label' => 'Usuarios', 'value' => $usuariosTotal],
            ['label' => 'Linhas', 'value' => $linhasTotal],
            ['label' => 'Onibus', 'value' => $onibusTotal],
            ['label' => 'Pontos', 'value' => $pontosTotal],
            ['label' => 'Horarios', 'value' => $horariosTotal],
            ['label' => 'Viagens', 'value' => $viagensTotal + $confirmacoesTotal],
        ];

        $peakPoint = $summarySeries[0];
        foreach ($summarySeries as $point) {
            if ($point['value'] > $peakPoint['value']) {
                $peakPoint = $point;
            }
        }

        $recentActivities = [];

        $latestUsers = $db->query("SELECT id, nome FROM usuarios ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($latestUsers as $user) {
            $recentActivities[] = [
                'type' => 'user',
                'avatar' => strtoupper(substr($user['nome'], 0, 1)),
                'text' => 'Novo usuario cadastrado ' . $user['nome'],
                'meta' => 'ID #' . $user['id'],
            ];
        }

        $latestLines = $db->query("SELECT id, nome FROM linhas ORDER BY id DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($latestLines as $line) {
            $recentActivities[] = [
                'type' => 'line',
                'avatar' => 'L',
                'text' => 'Linha atualizada ' . $line['nome'],
                'meta' => 'ID #' . $line['id'],
            ];
        }

        $latestStops = $db->query("SELECT id, nome FROM pontos ORDER BY id DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($latestStops as $stop) {
            $recentActivities[] = [
                'type' => 'stop',
                'avatar' => 'P',
                'text' => 'Novo ponto registrado ' . $stop['nome'],
                'meta' => 'ID #' . $stop['id'],
            ];
        }

        $recentActivities = array_slice($recentActivities, 0, 6);

        require_once __DIR__ . '/../Views/admin_dashboard.php';
    }

    public function usuarios() {
        $this->requireAdminSession();

        $usuarioModel = new Usuario();
        $listaUsuarios = $usuarioModel->buscarTodosComDadosDeAluno();
        $canManageAdminUsers = $this->currentUserCanManageAdminUsers();

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
            $this->jsonResponse(false, 'Preencha todos os campos obrigatorios.');
        }

        if (!$this->canAssignAdministrativeRole($dados['tipo_usuario'], $dados['email'])) {
            $this->jsonResponse(false, 'Usuarios administrativos so podem ser cadastrados com e-mail @beflow por um administrador @beflow.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->salvar($dados);
            $this->jsonResponse(true, 'Usuario cadastrado com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Este e-mail ja esta cadastrado.'
                : 'Erro tecnico ao cadastrar o usuario.';

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
            $this->jsonResponse(false, 'ID, nome e e-mail sao obrigatorios.');
        }

        $usuarioAtual = $this->buscarUsuarioPorId($id);
        if (!$usuarioAtual) {
            $this->jsonResponse(false, 'Usuario nao encontrado.');
        }

        $isPromocaoParaAdmin = !in_array($usuarioAtual['tipo_usuario'], ['admin_empresa', 'admin_geral'], true)
            && in_array($dados['tipo_usuario'], ['admin_empresa', 'admin_geral'], true);

        if ($isPromocaoParaAdmin && !$this->canAssignAdministrativeRole($dados['tipo_usuario'], $dados['email'])) {
            $this->jsonResponse(false, 'Usuarios administrativos so podem ser promovidos com e-mail @beflow por um administrador @beflow.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->atualizar($id, $dados);
            $this->jsonResponse(true, 'Usuario atualizado com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Ja existe outro usuario com este e-mail.'
                : 'Erro tecnico ao atualizar o usuario.';

            $this->jsonResponse(false, $message);
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    public function deletarUsuario() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID invalido.');
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->excluir($id);
            $this->jsonResponse(true, 'Usuario removido da base de dados.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Nao foi possivel excluir este usuario.');
        }
    }

    public function rotas() {
        $this->requireAdminSession();

        $db = (new Database())->getConnection();
        $linhas = $db->query("SELECT * FROM linhas WHERE empresa_id = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pontos = $db->query("SELECT * FROM pontos ORDER BY linha_id ASC, ordem_na_linha ASC")->fetchAll(PDO::FETCH_ASSOC);

        $linhasComPontos = [];
        foreach ($linhas as $linha) {
            $linha['pontos'] = array_values(array_filter($pontos, function ($ponto) use ($linha) {
                return (int) $ponto['linha_id'] === (int) $linha['id'];
            }));
            $linhasComPontos[] = $linha;
        }

        require_once __DIR__ . '/../Views/admin_rotas.php';
    }

    public function salvarLinha() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $cor = strtolower(trim($_POST['cor'] ?? 'azul'));

        if ($nome === '') {
            $this->jsonResponse(false, 'Informe o nome da linha.');
        }

        if (!in_array($cor, $this->allowedColors, true)) {
            $this->jsonResponse(false, 'Selecione uma cor valida para a linha.');
        }

        try {
            $db = (new Database())->getConnection();

            if ($id !== '') {
                $stmt = $db->prepare("UPDATE linhas SET nome = :nome, cor = :cor WHERE id = :id");
                $stmt->execute(['nome' => $nome, 'cor' => $cor, 'id' => $id]);
                $linhaId = (int) $id;
            } else {
                $stmt = $db->prepare("INSERT INTO linhas (nome, cor, empresa_id) VALUES (:nome, :cor, :empresa_id)");
                $stmt->execute(['nome' => $nome, 'cor' => $cor, 'empresa_id' => 1]);
                $linhaId = (int) $db->lastInsertId();
            }

            $this->ensureHorarioBase($db, $linhaId);
            $this->jsonResponse(true, 'Linha salva com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao salvar a linha.');
        }
    }

    public function deletarLinha() {
        $this->requireAdminSessionJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID da linha invalido.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM linhas WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->jsonResponse(true, 'Linha removida com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover a linha.');
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
            $this->jsonResponse(false, 'ID do ponto invalido.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM pontos WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->jsonResponse(true, 'Ponto removido com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover o ponto.');
        }
    }

    private function ensureHorarioBase(PDO $db, $linhaId) {
        $stmt = $db->prepare("SELECT id FROM horarios_base WHERE linha_id = :linha_id LIMIT 1");
        $stmt->execute(['linha_id' => $linhaId]);

        if ($stmt->fetchColumn()) {
            return;
        }

        $insert = $db->prepare("INSERT INTO horarios_base (linha_id, turno, hora_saida_garagem) VALUES (:linha_id, :turno, :hora)");
        $insert->execute([
            'linha_id' => $linhaId,
            'turno' => 'Matutino',
            'hora' => '06:30:00',
        ]);
    }

    private function canAssignAdministrativeRole($tipoUsuario, $email) {
        if (!in_array($tipoUsuario, ['admin_empresa', 'admin_geral'], true)) {
            return true;
        }

        return $this->currentUserCanManageAdminUsers() && $this->isBeFlowEmail($email);
    }

    private function currentUserCanManageAdminUsers() {
        $email = $this->getCurrentSessionUserEmail();

        return $email !== '' && $this->isBeFlowEmail($email);
    }

    private function buscarUsuarioPorId($id) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT id, email, tipo_usuario FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getCurrentSessionUserEmail() {
        if (!isset($_SESSION['usuario_id'])) {
            return '';
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT email FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $_SESSION['usuario_id']]);

        return (string) ($stmt->fetchColumn() ?: '');
    }

    private function isBeFlowEmail($email) {
        return stripos(trim((string) $email), '@beflow') !== false;
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
?>
