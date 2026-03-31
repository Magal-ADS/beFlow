<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Ponto.php';
require_once __DIR__ . '/../app/Models/Confirmacao.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "[OK] {$message}\n";
}

$db = (new Database())->getConnection();
$usuarioModel = new Usuario();
$pontoModel = new Ponto();
$confirmacaoModel = new Confirmacao();

$admin = $usuarioModel->buscarPorEmail('admin@beflow.com');
assertTrue($admin && $admin['tipo_usuario'] === 'admin_empresa', 'Login model encontra o admin');

$motoristaId = $db->query("SELECT id FROM usuarios WHERE email = 'motorista@beflow.com'")->fetchColumn();
$alunoUsuarioId = $db->query("SELECT id FROM usuarios WHERE email = 'amandaaluno@gmail.com'")->fetchColumn();
$pontoId = $db->query("SELECT id FROM pontos ORDER BY id ASC LIMIT 1")->fetchColumn();

assertTrue(count($pontoModel->buscarTodos()) >= 3, 'Lista de pontos carregada');
assertTrue($pontoModel->lerStatusViagem() === 'aguardando', 'Status inicial da viagem e aguardando');

$db->exec('DELETE FROM confirmacoes');
assertTrue($confirmacaoModel->registrar($alunoUsuarioId, $pontoId) === true, 'Aluno confirma presenca na viagem do dia');
assertTrue($confirmacaoModel->registrar($alunoUsuarioId, $pontoId) === false, 'Confirmacao duplicada e bloqueada');

$pontosComContagem = $pontoModel->buscarPontosComContagem();
$primeiroPonto = $pontosComContagem[0] ?? null;
assertTrue($primeiroPonto && (int) $primeiroPonto['total_alunos'] === 1, 'Motorista recebe contagem correta por ponto');

assertTrue($pontoModel->atualizarStatusViagem('em_rota', $motoristaId) === true, 'Motorista inicia a rota');
assertTrue($pontoModel->lerStatusViagem() === 'em_rota', 'Status muda para em_rota');
assertTrue($pontoModel->atualizarStatusViagem('finalizada', $motoristaId) === true, 'Motorista finaliza a rota');
assertTrue($pontoModel->lerStatusViagem() === 'finalizada', 'Status muda para finalizada');

foreach (['salvarLinha', 'deletarLinha', 'salvarPonto', 'deletarPonto'] as $method) {
    assertTrue(method_exists(AdminController::class, $method), "AdminController expõe {$method}");
}

echo "\nSmoke tests concluídos.\n";
