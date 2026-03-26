<?php
/**
 * BeFlow - Arquivo de Roteamento Principal (index.php)
 */

// Requisita o controlador de autenticação (usado em quase todas as rotas de acesso)
require_once __DIR__ . '/app/Controllers/AuthController.php';

// Pega a URL que o usuário digitou
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove a pasta base "/beflow" para o roteamento focar apenas na funcionalidade
$rota = str_ireplace('/beflow', '', $uri);

// Switch de Roteamento
switch ($rota) {

    // ==========================================
    // ROTAS DE AUTENTICAÇÃO (LOGIN/LOGOUT)
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
    // ROTAS DO ALUNO (MAPA E CONFIRMAÇÃO)
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

    case '/status-viagem': // <-- NOVA ROTA: Celular do aluno checa se o ônibus saiu
        require_once __DIR__ . '/app/Controllers/AlunoController.php';
        $controller = new AlunoController();
        $controller->checarStatusViagem();
        break;

    // ==========================================
    // ROTAS DO MOTORISTA (PAINEL DE EMBARQUE E API)
    // ==========================================
    case '/home-motorista':
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->index();
        break;

    case '/api-pontos': // Rota para o Auto-Refresh do mapa
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->apiPontos();
        break;

    case '/iniciar-rota': // <-- NOVA ROTA: Motorista avisa que a viagem começou
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->iniciarRota();
        break;

    case '/finalizar-rota': // Rota: Motorista finaliza a viagem
        require_once __DIR__ . '/app/Controllers/MotoristaController.php';
        $controller = new MotoristaController();
        $controller->finalizarRota();
        break;

    // ==========================================
    // ROTA PADRÃO (ERRO 404)
    // ==========================================
    default:
        http_response_code(404);
        echo "<div style='font-family: sans-serif; text-align: center; padding-top: 50px;'>";
        echo "<h1 style='color: #4A7DDF;'>Erro 404</h1>";
        echo "<p>Página não encontrada no BeFlow.</p>";
        echo "<p>Caminho interpretado: <b>" . htmlspecialchars($rota) . "</b></p>";
        echo "<a href='/beFlow/login' style='color: #4A7DDF;'>Voltar para o início</a>";
        echo "</div>";
        break;
}
?>