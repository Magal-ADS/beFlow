<?php
require_once __DIR__ . '/../Models/Usuario.php';

class PerfilController {
    public function index() {
        $this->requireAuth();

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorId((int) $_SESSION['usuario_id']);

        if (!$usuario) {
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $currentSection = 'perfil';
        $flash = $_SESSION['perfil_flash'] ?? null;
        unset($_SESSION['perfil_flash']);

        require_once __DIR__ . '/../Views/perfil.php';
    }

    public function salvar() {
        $this->requireAuth();

        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telefone' => trim($_POST['telefone'] ?? ''),
            'senha' => trim($_POST['senha'] ?? ''),
            'confirmar_senha' => trim($_POST['confirmar_senha'] ?? ''),
        ];

        if ($dados['nome'] === '' || $dados['email'] === '') {
            $this->setFlash(false, 'Nome e e-mail sao obrigatorios.');
            $this->redirectToProfile();
        }

        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash(false, 'Informe um e-mail valido.');
            $this->redirectToProfile();
        }

        if ($dados['senha'] !== '' && strlen($dados['senha']) < 6) {
            $this->setFlash(false, 'A nova senha precisa ter pelo menos 6 caracteres.');
            $this->redirectToProfile();
        }

        if ($dados['senha'] !== $dados['confirmar_senha']) {
            $this->setFlash(false, 'A confirmacao de senha nao confere.');
            $this->redirectToProfile();
        }

        try {
            $usuarioModel = new Usuario();
            $usuarioModel->atualizarPerfil((int) $_SESSION['usuario_id'], $dados);

            $_SESSION['usuario_nome'] = $dados['nome'];
            $this->setFlash(true, 'Perfil atualizado com sucesso.');
        } catch (RuntimeException $e) {
            $this->setFlash(false, $e->getMessage());
        } catch (Throwable $e) {
            $this->setFlash(false, 'Nao foi possivel atualizar o perfil.');
        }

        $this->redirectToProfile();
    }

    private function requireAuth() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function setFlash($success, $message) {
        $_SESSION['perfil_flash'] = [
            'success' => (bool) $success,
            'message' => $message,
        ];
    }

    private function redirectToProfile() {
        header('Location: ' . BASE_URL . '/perfil');
        exit;
    }
}
?>
