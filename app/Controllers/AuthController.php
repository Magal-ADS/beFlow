<?php
/**
 * BeFlow - Controlador de Autenticação (AuthController.php)
 */

// Requisita o Model de Usuário para poder acessar o banco de dados
require_once __DIR__ . '/../Models/Usuario.php';

class AuthController {
    
    /**
     * Exibe a tela de login
     */
    public function index() {
        // Renderiza a View do formulário de login
        require_once __DIR__ . '/../Views/login.php';
    }

    /**
     * Processa o formulário de login (POST)
     */
    public function autenticar() {
        // Pega os dados enviados pelo formulário
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        // Busca o usuário no banco de dados através do Model
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($email);

        // Verifica se o usuário existe e se a senha confere
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            // Define as variáveis de sessão essenciais
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

            // --- REDIRECIONAMENTO POR TIPO DE USUÁRIO ---
            
            if ($usuario['tipo_usuario'] == 'aluno') {
                // Aluno vai para o mapa de embarque
                header("Location: " . BASE_URL . "/home-aluno");
                exit; 
                
            } else if ($usuario['tipo_usuario'] == 'motorista') {
                // Motorista vai para o painel de controle da rota
                header("Location: " . BASE_URL . "/home-motorista");
                exit;
                
            } else if ($usuario['tipo_usuario'] == 'admin_empresa' || $usuario['tipo_usuario'] == 'admin_geral') {
                // Administrador vai para o novo Dashboard da Empresa
                header("Location: " . BASE_URL . "/admin/dashboard");
                exit;
                
            } else {
                // Caso haja um tipo não mapeado, volta pro login por segurança
                session_unset();
                session_destroy();
                header("Location: " . BASE_URL . "/login");
                exit;
            }
            
        } else {
            // Caso falhe, exibe um alerta e retorna para o login
            echo "<script>alert('E-mail ou senha incorretos!'); window.location.href='" . BASE_URL . "/login';</script>";
        }
    }

    /**
     * Encerra a sessão do usuário com segurança
     */
    public function logout() {
        // Limpa as variáveis e destrói a sessão
        session_unset();
        session_destroy();
        
        // Redireciona o usuário para a tela de login
        header("Location: " . BASE_URL . "/login");
        exit;
    }
}