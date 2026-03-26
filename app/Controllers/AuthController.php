<?php
// Requisita o Model de Usuário para poder acessar o banco de dados
require_once __DIR__ . '/../Models/Usuario.php';

class AuthController {
    
    // Método para exibir a tela de login
    public function index() {
        // Renderiza a View do formulário de login
        require_once __DIR__ . '/../Views/login.php';
    }

    // Método que processa o formulário de login (POST)
    public function autenticar() {
        // Inicia a sessão para persistir os dados do usuário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Pega os dados enviados pelo formulário
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        // Busca o usuário no banco de dados através do Model
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($email);

        // Verifica se o usuário existe e se a senha confere
        // Nota: Futuramente trocaremos para password_verify para maior segurança (RNF9)
        if ($usuario && $usuario['senha'] === $senha) {
            
            // Define as variáveis de sessão essenciais
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

            // Redirecionamento baseado no tipo de usuário (RNF1)
            if ($usuario['tipo_usuario'] == 'aluno') {
                // Manda o aluno para a tela do mapa (Leaflet/Google Maps)
                header("Location: /beFlow/home-aluno");
                exit; 
                
            } else if ($usuario['tipo_usuario'] == 'motorista') {
                // Manda o motorista direto para o painel dele
                header("Location: /beFlow/home-motorista");
                exit;
                
            } else {
                // Futura tela administrativa
                echo "<h2>Login de ADMIN com sucesso! Bem-vindo(a), " . $usuario['nome'] . "</h2>";
            }
            
        } else {
            // Caso falhe, exibe um alerta e retorna para o login
            echo "<script>alert('E-mail ou senha incorretos!'); window.location.href='/beFlow/login';</script>";
        }
    }

    // Método para encerrar a sessão do usuário com segurança
    public function logout() {
        // Garante que a sessão está ativa antes de destruí-la
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpa as variáveis e destrói a sessão
        session_unset();
        session_destroy();
        
        // Redireciona o usuário para a tela de login limpa
        header("Location: /beFlow/login");
        exit;
    }
}
?>