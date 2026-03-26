<?php
/**
 * BeFlow - Arquivo de Roteamento Principal (index.php)
 */

require_once __DIR__ . '/app/Controllers/AuthController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rota = str_ireplace('/beFlow', '', $uri);

switch ($rota) {

    // ==========================================
    // ROTAS DE AUTENTICAÇÃO
    // ==========================================
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

    // ==========================================
    // ROTAS DO ALUNO
    // ==========================================
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

    // ==========================================
    // ROTAS DO MOTORISTA
    // ==========================================
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

    // ==========================================
    // ROTAS DO ADMINISTRADOR (EMPRESA)
    // ==========================================
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

    // --- NOVAS ROTAS PARA GERENCIAMENTO DE LINHAS E PONTOS ---
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

    // ==========================================
    // ROTA PADRÃO (ERRO 404)
    // ==========================================
    default:
        http_response_code(404);
        echo "<div style='font-family: sans-serif; text-align: center; padding-top: 50px;'>";
        echo "<h1 style='color: #4A7DDF;'>Erro 404</h1>";
        echo "<p>Página não encontrada no BeFlow.</p>";
        echo "<a href='/beFlow/login' style='color: #4A7DDF; text-decoration: none; font-weight: bold;'>← Voltar para o início</a>";
        echo "</div>";
        break;
}