<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';

class AdminController {
    private $allowedColors = ['azul', 'vermelha', 'amarela', 'verde'];
    private $schemaColumnCache = [];

    public function index() {
        $this->requireAdminSession();
        $empresaId = $this->getAdminEmpresaId();

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
        $stmtMotoristas = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'motorista' AND empresa_id = :empresa_id");
        $stmtMotoristas->execute(['empresa_id' => $empresaId]);
        $motoristasTotal = (int) $stmtMotoristas->fetchColumn();
        $stmtLinhas = $db->prepare("SELECT * FROM linhas WHERE empresa_id = :empresa_id ORDER BY nome ASC");
        $stmtLinhas->execute(['empresa_id' => $empresaId]);
        $linhas = $stmtLinhas->fetchAll(PDO::FETCH_ASSOC);
        $linhasTotal = count($linhas);
        $stmtVeiculosCount = $db->prepare("SELECT COUNT(*) FROM veiculo WHERE empresa_id = :empresa_id");
        $stmtVeiculosCount->execute(['empresa_id' => $empresaId]);
        $onibusTotal = (int) $stmtVeiculosCount->fetchColumn();
        $stmtVeiculos = $db->prepare("SELECT * FROM veiculo WHERE empresa_id = :empresa_id ORDER BY numero_identificador ASC, placa ASC");
        $stmtVeiculos->execute(['empresa_id' => $empresaId]);
        $veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);
        $pontosTotal = (int) $db->query("SELECT COUNT(*) FROM pontos")->fetchColumn();
        $horariosTotal = (int) $db->query("SELECT COUNT(*) FROM horarios_base")->fetchColumn();
        $empresasTotal = (int) $db->query("SELECT COUNT(*) FROM empresa")->fetchColumn();
        $viagensTotal = (int) $db->query("SELECT COUNT(*) FROM viagens")->fetchColumn();
        $confirmacoesTotal = (int) $db->query("SELECT COUNT(*) FROM confirmacoes")->fetchColumn();
        $this->ensurePontosHorarioAproximadoColumn($db);
        $pontos = $db->query($this->buildPontosSelectSql())->fetchAll(PDO::FETCH_ASSOC);

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
            'linhas_pontos' => $linhasTotal . '/' . $pontosTotal,
            'veiculos' => $onibusTotal,
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
                'title' => 'Linhas e Pontos',
                'value' => $linhasTotal . '/' . $pontosTotal,
                'change' => 'Operacao cadastrada',
                'icon_bg' => 'bg-emerald-100',
                'icon_fg' => 'text-emerald-600',
                'icon' => 'bus',
            ],
            [
                'title' => 'Veiculos',
                'value' => (string) $onibusTotal,
                'change' => 'Frota cadastrada',
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
        $empresaId = $this->getAdminEmpresaId();

        $usuarioModel = new Usuario();
        $listaUsuarios = $usuarioModel->buscarTodosComDadosDeAluno($empresaId, ['aluno', 'motorista']);
        $canManageAdminUsers = $this->currentUserCanManageAdminUsers();

        require_once __DIR__ . '/../Views/admin_usuarios.php';
    }

    public function empresas() {
        $this->requireAdminSession();

        $db = (new Database())->getConnection();
        $stmt = $db->query("
            SELECT
                e.*,
                (
                    SELECT COUNT(*)
                    FROM usuarios u
                    WHERE u.empresa_id = e.id
                ) AS total_usuarios,
                (
                    SELECT COUNT(*)
                    FROM linhas l
                    WHERE l.empresa_id = e.id
                ) AS total_linhas,
                (
                    SELECT COUNT(*)
                    FROM veiculo v
                    WHERE v.empresa_id = e.id
                ) AS total_veiculos
            FROM empresa e
            ORDER BY e.nome ASC
        ");
        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $canManageCompanies = $this->currentUserCanManageCompanies();

        require_once __DIR__ . '/../Views/admin_empresas.php';
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

    public function salvarEmpresa() {
        $this->requireAdminSessionJson();
        $this->requireGeneralAdminJson();

        $db = (new Database())->getConnection();
        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = $this->normalizeCnpj($_POST['cnpj'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        if ($nome === '' || $cnpj === '') {
            $this->jsonResponse(false, 'Informe o nome e o CNPJ da empresa.');
        }

        if (!$this->isValidCnpj($cnpj)) {
            $this->jsonResponse(false, 'Informe um CNPJ valido com 14 digitos.');
        }

        if ($telefone !== '' && strlen(preg_replace('/\D+/', '', $telefone)) < 10) {
            $this->jsonResponse(false, 'Informe um telefone valido para a empresa.');
        }

        try {
            if ($id !== '') {
                $stmt = $db->prepare("
                    UPDATE empresa
                    SET nome = :nome,
                        cnpj = :cnpj,
                        telefone = :telefone
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nome' => $nome,
                    'cnpj' => $cnpj,
                    'telefone' => $telefone !== '' ? $telefone : null,
                    'id' => $id,
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO empresa (nome, cnpj, telefone)
                    VALUES (:nome, :cnpj, :telefone)
                ");
                $stmt->execute([
                    'nome' => $nome,
                    'cnpj' => $cnpj,
                    'telefone' => $telefone !== '' ? $telefone : null,
                ]);
            }

            $this->jsonResponse(true, 'Empresa salva com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Ja existe uma empresa cadastrada com este CNPJ.'
                : 'Erro ao salvar a empresa.';
            $this->jsonResponse(false, $message);
        }
    }

    public function deletarEmpresa() {
        $this->requireAdminSessionJson();
        $this->requireGeneralAdminJson();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID da empresa invalido.');
        }

        if ($this->empresaPossuiDependencias((int) $id)) {
            $this->jsonResponse(false, 'Remova ou reatribua usuarios, linhas e veiculos desta empresa antes de exclui-la.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare('DELETE FROM empresa WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $this->jsonResponse(true, 'Empresa removida com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover a empresa.');
        }
    }

    public function rotas() {
        $this->requireAdminSession();
        $empresaId = $this->getAdminEmpresaId();

        $db = (new Database())->getConnection();
        $stmtLinhas = $db->prepare("SELECT * FROM linhas WHERE empresa_id = :empresa_id ORDER BY nome ASC");
        $stmtLinhas->execute(['empresa_id' => $empresaId]);
        $linhas = $stmtLinhas->fetchAll(PDO::FETCH_ASSOC);
        $this->ensurePontosHorarioAproximadoColumn($db);
        $pontos = $db->query($this->buildPontosSelectSql())->fetchAll(PDO::FETCH_ASSOC);

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
        $empresaId = $this->getAdminEmpresaId();

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
                $stmt->execute(['nome' => $nome, 'cor' => $cor, 'empresa_id' => $empresaId]);
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
        $horarioAproximado = trim($_POST['horario_aproximado'] ?? '');

        if ($linhaId === '' || $nome === '' || $latitude === '' || $longitude === '' || $ordem === '') {
            $this->jsonResponse(false, 'Preencha todos os dados do ponto.');
        }

        if ($horarioAproximado !== '' && !$this->isValidApproximateTime($horarioAproximado)) {
            $this->jsonResponse(false, 'Informe um horario aproximado valido para o ponto.');
        }

        $horarioAproximado = $horarioAproximado !== '' ? $this->normalizeApproximateTime($horarioAproximado) : null;

        try {
            $db = (new Database())->getConnection();
            $this->ensurePontosHorarioAproximadoColumn($db);

            if ($id !== '') {
                $stmt = $db->prepare("
                    UPDATE pontos
                    SET linha_id = :linha_id, nome = :nome, latitude = :latitude, longitude = :longitude, ordem_na_linha = :ordem, horario_aproximado = :horario_aproximado
                    WHERE id = :id
                ");
                $stmt->execute([
                    'linha_id' => $linhaId,
                    'nome' => $nome,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'ordem' => $ordem,
                    'horario_aproximado' => $horarioAproximado,
                    'id' => $id,
                ]);
            } else {
                $this->syncPontosPrimaryKeySequence($db);
                $stmt = $db->prepare("
                    INSERT INTO pontos (linha_id, nome, latitude, longitude, ordem_na_linha, horario_aproximado)
                    VALUES (:linha_id, :nome, :latitude, :longitude, :ordem, :horario_aproximado)
                ");
                $stmt->execute([
                    'linha_id' => $linhaId,
                    'nome' => $nome,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'ordem' => $ordem,
                    'horario_aproximado' => $horarioAproximado,
                ]);
            }

            $this->jsonResponse(true, 'Ponto salvo com sucesso.');
        } catch (PDOException $e) {
            error_log('Erro ao salvar ponto: ' . $e->getMessage());
            $this->jsonResponse(false, 'Erro ao salvar o ponto: ' . $e->getMessage());
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

    public function salvarVeiculo() {
        $this->requireAdminSessionJson();
        $empresaId = $this->getAdminEmpresaId();

        $id = trim($_POST['id'] ?? '');
        $numeroIdentificador = trim($_POST['numero_identificador'] ?? '');
        $placa = strtoupper(trim($_POST['placa'] ?? ''));

        if ($numeroIdentificador === '' || $placa === '') {
            $this->jsonResponse(false, 'Informe o identificador e a placa do veiculo.');
        }

        try {
            $db = (new Database())->getConnection();

            if ($id !== '') {
                $stmt = $db->prepare("
                    UPDATE veiculo
                    SET numero_identificador = :numero_identificador,
                        placa = :placa
                    WHERE id = :id AND empresa_id = :empresa_id
                ");
                $stmt->execute([
                    'numero_identificador' => $numeroIdentificador,
                    'placa' => $placa,
                    'id' => $id,
                    'empresa_id' => $empresaId,
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO veiculo (numero_identificador, placa, empresa_id)
                    VALUES (:numero_identificador, :placa, :empresa_id)
                ");
                $stmt->execute([
                    'numero_identificador' => $numeroIdentificador,
                    'placa' => $placa,
                    'empresa_id' => $empresaId,
                ]);
            }

            $this->jsonResponse(true, 'Veiculo salvo com sucesso.');
        } catch (PDOException $e) {
            $message = $this->isDuplicateKey($e)
                ? 'Ja existe um veiculo com essa placa.'
                : 'Erro ao salvar o veiculo.';
            $this->jsonResponse(false, $message);
        }
    }

    public function deletarVeiculo() {
        $this->requireAdminSessionJson();
        $empresaId = $this->getAdminEmpresaId();

        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $this->jsonResponse(false, 'ID do veiculo invalido.');
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("DELETE FROM veiculo WHERE id = :id AND empresa_id = :empresa_id");
            $stmt->execute([
                'id' => $id,
                'empresa_id' => $empresaId,
            ]);

            $this->jsonResponse(true, 'Veiculo removido com sucesso.');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Erro ao remover o veiculo.');
        }
    }

    private function ensureHorarioBase(PDO $db, $linhaId) {
        $stmt = $db->prepare("SELECT id FROM horarios_base WHERE linha_id = :linha_id LIMIT 1");
        $stmt->execute(['linha_id' => $linhaId]);

        if ($stmt->fetchColumn()) {
            return;
        }

        $insert = $db->prepare("INSERT INTO horarios_base (linha_id, turno, hora_ida, hora_volta) VALUES (:linha_id, :turno, :hora_ida, :hora_volta)");
        $insert->execute([
            'linha_id' => $linhaId,
            'turno' => 'Matutino',
            'hora_ida' => '06:30:00',
            'hora_volta' => '17:30:00',
        ]);
    }

    private function canAssignAdministrativeRole($tipoUsuario, $email) {
        if (!in_array($tipoUsuario, ['admin_empresa', 'admin_geral'], true)) {
            return true;
        }

        return $this->currentUserCanManageAdminUsers() && $this->isBeFlowEmail($email);
    }

    private function currentUserCanManageCompanies() {
        $tipoUsuario = $_SESSION['tipo_usuario'] ?? '';

        if ($tipoUsuario === 'admin_geral') {
            return true;
        }

        return $this->currentUserCanManageAdminUsers();
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

    private function getAdminEmpresaId() {
        if (!empty($_SESSION['empresa_id'])) {
            return (int) $_SESSION['empresa_id'];
        }

        if (!isset($_SESSION['usuario_id'])) {
            return 1;
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $_SESSION['usuario_id']]);
        $empresaId = (int) ($stmt->fetchColumn() ?: 1);
        $_SESSION['empresa_id'] = $empresaId;

        return $empresaId;
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

    private function requireGeneralAdminJson() {
        if ($this->currentUserCanManageCompanies()) {
            return;
        }

        $this->jsonResponse(false, 'Somente o Administrador Geral pode gerenciar empresas.');
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

    private function isValidApproximateTime($value) {
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) === 1;
    }

    private function normalizeApproximateTime($value) {
        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function normalizeCnpj($value) {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function isValidCnpj($value) {
        return preg_match('/^\d{14}$/', (string) $value) === 1;
    }

    private function empresaPossuiDependencias(int $empresaId) {
        $db = (new Database())->getConnection();

        $stmtUsuarios = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE empresa_id = :empresa_id');
        $stmtUsuarios->execute(['empresa_id' => $empresaId]);

        $stmtLinhas = $db->prepare('SELECT COUNT(*) FROM linhas WHERE empresa_id = :empresa_id');
        $stmtLinhas->execute(['empresa_id' => $empresaId]);

        $stmtVeiculos = $db->prepare('SELECT COUNT(*) FROM veiculo WHERE empresa_id = :empresa_id');
        $stmtVeiculos->execute(['empresa_id' => $empresaId]);

        return ((int) $stmtUsuarios->fetchColumn()) > 0
            || ((int) $stmtLinhas->fetchColumn()) > 0
            || ((int) $stmtVeiculos->fetchColumn()) > 0;
    }

    private function buildPontosSelectSql() {
        return "SELECT id, nome, latitude, longitude, ordem_na_linha, linha_id, horario_aproximado FROM pontos ORDER BY linha_id ASC, ordem_na_linha ASC";
    }

    private function ensurePontosHorarioAproximadoColumn(PDO $db) {
        if ($this->hasColumn($db, 'pontos', 'horario_aproximado')) {
            return;
        }

        $db->exec("ALTER TABLE pontos ADD COLUMN horario_aproximado TIME NULL");
        $this->schemaColumnCache['pontos.horario_aproximado'] = true;
    }

    private function syncPontosPrimaryKeySequence(PDO $db) {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            return;
        }

        $db->query("
            SELECT setval(
                pg_get_serial_sequence('pontos', 'id'),
                COALESCE((SELECT MAX(id) FROM pontos), 1),
                true
            )
        ")->fetchColumn();
    }

    private function hasColumn(PDO $db, $table, $column) {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaColumnCache)) {
            return $this->schemaColumnCache[$cacheKey];
        }

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $stmt = $db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = CURRENT_SCHEMA()
                  AND table_name = :table
                  AND column_name = :column
                LIMIT 1
            ");
        } else {
            $stmt = $db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table
                  AND column_name = :column
                LIMIT 1
            ");
        }

        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $exists = (bool) $stmt->fetchColumn();
        $this->schemaColumnCache[$cacheKey] = $exists;

        return $exists;
    }
}
?>
