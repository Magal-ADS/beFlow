<?php
/**
 * BeFlow - Arquivo de Roteamento Principal (index.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');

function normalizeBaseUrl($path) {
    $path = trim((string) $path);
    if ($path === '' || $path === '/') {
        return '';
    }

    $path = '/' . trim($path, '/');
    return rtrim($path, '/');
}

function resolveBaseUrl() {
    $configuredBaseUrl = getenv('APP_BASE_URL');
    if ($configuredBaseUrl !== false && trim($configuredBaseUrl) !== '') {
        return normalizeBaseUrl($configuredBaseUrl);
    }

    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return normalizeBaseUrl($scriptDirectory);
}

define('BASE_URL', resolveBaseUrl());

require_once __DIR__ . '/app/Controllers/AuthController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rota = $uri ?: '/';

if (BASE_URL !== '' && stripos($rota, BASE_URL) === 0) {
    $rota = substr($rota, strlen(BASE_URL));
}

if ($rota === '' || $rota === false) {
    $rota = '/';
}

switch ($rota) {
    case '':
    case '/':
    case '/login':
    case '/index.php':
        $controller = new AuthController();
        $controller->index();
        break;

    case '/autenticar':
        $controller = new AuthController();
        $controller->autenticar();
        break;

    case '/logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/home-aluno':
        require_once __DIR__ . '/app/Controllers/AlunoController.php';
        $controller = new AlunoController();
        $controller->index();
        break;

    case '/confirmar-presenca':
        require_once __DIR__ . '/app/Controllers/AlunoController.php';
        $controller = new AlunoController();
        $controller->confirmar();
        break;

    case '/status-viagem':
        require_once __DIR__ . '/app/Controllers/AlunoController.php';
        $controller = new AlunoController();
        $controller->checarStatusViagem();
        break;

    case '/aluno/selecionar-linha':
        require_once __DIR__ . '/app/Controllers/AlunoController.php';
        $controller = new AlunoController();
        $controller->selecionarLinha();
        break;

    case '/home-motorista':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->index();
        break;

    case '/api-pontos':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->apiPontos();
        break;

    case '/motorista/configurar-viagem':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->configurarViagem();
        break;

    case '/motorista/atualizar-localizacao':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->atualizarLocalizacao();
        break;

    case '/iniciar-rota':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->iniciarRota();
        break;

    case '/finalizar-rota':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->finalizarRota();
        break;

    case '/encerrar-dia':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->encerrarDia();
        break;

    case '/admin/dashboard':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        break;

    case '/admin/usuarios':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->usuarios();
        break;

    case '/admin/salvar-usuario':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->salvarUsuario();
        break;

    case '/admin/editar-usuario':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->editarUsuario();
        break;

    case '/admin/deletar-usuario':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->deletarUsuario();
        break;

    case '/admin/rotas':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->rotas();
        break;

    case '/admin/salvar-linha':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->salvarLinha();
        break;

    case '/admin/deletar-linha':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->deletarLinha();
        break;

    case '/admin/salvar-ponto':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->salvarPonto();
        break;

    case '/admin/deletar-ponto':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->deletarPonto();
        break;

    case '/admin/salvar-veiculo':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->salvarVeiculo();
        break;

    case '/admin/deletar-veiculo':
        require_once __DIR__ . '/app/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->deletarVeiculo();
        break;

    default:
        http_response_code(404);
        echo "<div style='font-family: sans-serif; text-align: center; padding-top: 50px;'>";
        echo "<h1 style='color: #4A7DDF;'>Erro 404</h1>";
        echo "<p>Pagina nao encontrada no BeFlow.</p>";
        echo "<a href='" . BASE_URL . "/login' style='color: #4A7DDF; text-decoration: none; font-weight: bold;'>&larr; Voltar para o inicio</a>";
        echo "</div>";
        break;
}
?>
