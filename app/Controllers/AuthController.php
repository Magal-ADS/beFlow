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
        // Nota: Futuramente trocaremos para password_verify para maior segurança
        if ($usuario && $usuario['senha'] === $senha) {
            
            // Define as variáveis de sessão essenciais
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

            // --- REDIRECIONAMENTO POR TIPO DE USUÁRIO ---
            
            if ($usuario['tipo_usuario'] == 'aluno') {
                // Aluno vai para o mapa de embarque
                header("Location: /beFlow/home-aluno");
                exit; 
                
            } else if ($usuario['tipo_usuario'] == 'motorista') {
                // Motorista vai para o painel de controle da rota
                header("Location: /beFlow/home-motorista");
                exit;
                
            } else if ($usuario['tipo_usuario'] == 'admin_empresa' || $usuario['tipo_usuario'] == 'admin_geral') {
                // Administrador vai para o novo Dashboard da Empresa
                header("Location: /beFlow/admin/dashboard");
                exit;
                
            } else {
                // Caso haja um tipo não mapeado, volta pro login por segurança
                session_destroy();
                header("Location: /beFlow/login");
                exit;
            }
            
        } else {
            // Caso falhe, exibe um alerta e retorna para o login
            echo "<script>alert('E-mail ou senha incorretos!'); window.location.href='/beFlow/login';</script>";
        }
    }

    /**
     * Encerra a sessão do usuário com segurança
     */
    public function logout() {
        // Garante que a sessão está ativa antes de destruí-la
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpa as variáveis e destrói a sessão
        session_unset();
        session_destroy();
        
        // Redireciona o usuário para a tela de login
        header("Location: /beFlow/login");
        exit;
    }
}