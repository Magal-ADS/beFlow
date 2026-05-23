<?php
require_once __DIR__ . '/../Models/Usuario.php';

class AuthController {
    public function index() {
        require_once __DIR__ . '/../Views/login.php';
    }

    public function autenticar() {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            $_SESSION['empresa_id'] = $usuario['empresa_id'] ?? null;

            if ($usuario['tipo_usuario'] === 'aluno') {
                header('Location: ' . BASE_URL . '/home-aluno');
                exit;
            }

            if ($usuario['tipo_usuario'] === 'motorista') {
                header('Location: ' . BASE_URL . '/home-motorista');
                exit;
            }

            if ($usuario['tipo_usuario'] === 'admin_empresa' || $usuario['tipo_usuario'] === 'admin_geral') {
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            }

            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        echo "<script>alert('E-mail ou senha incorretos!'); window.location.href='" . BASE_URL . "/login';</script>";
    }

    public function logout() {
        session_unset();
        session_destroy();

        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
?>
