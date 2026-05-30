<?php
require_once __DIR__ . '/../Models/Ponto.php';
require_once __DIR__ . '/../../config/database.php';

class MotoristaController {
    private $tripStartWebhookUrl = 'https://webhook.weagles.com.br/webhook/f446e76d-f858-4650-b2f2-3c8db036149c';

    private function requireMotoristaJson() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }
    }

    private function getEmpresaId() {
        if (!empty($_SESSION['empresa_id'])) {
            return (int) $_SESSION['empresa_id'];
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_SESSION['usuario_id']]);
        $empresaId = (int) $stmt->fetchColumn();
        $_SESSION['empresa_id'] = $empresaId;

        return $empresaId;
    }

    private function tripResponse(Ponto $pontoModel) {
        return $pontoModel->buscarViagemAtual($_SESSION['usuario_id']);
    }

    public function index() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $empresaId = $this->getEmpresaId();
        $pontoModel = new Ponto();
        $pontos = $pontoModel->buscarPontosComContagem($_SESSION['usuario_id']);
        $viagemAtual = $pontoModel->buscarViagemAtual($_SESSION['usuario_id']);
        $linhasDisponiveis = $pontoModel->buscarLinhasDaEmpresa($empresaId);
        $veiculosDisponiveis = $pontoModel->buscarVeiculosDaEmpresa($empresaId);
        $db = (new Database())->getConnection();
        $stmtMotorista = $db->prepare("SELECT nome, telefone, email FROM usuarios WHERE id = :id LIMIT 1");
        $stmtMotorista->execute(['id' => $_SESSION['usuario_id']]);
        $currentMotorista = $stmtMotorista->fetch(PDO::FETCH_ASSOC) ?: [
            'nome' => $_SESSION['usuario_nome'] ?? 'Motorista',
            'telefone' => '',
            'email' => '',
        ];

        require_once __DIR__ . '/../Views/home_motorista.php';
    }

    public function apiPontos() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'motorista') {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $pontoModel = new Ponto();
        echo json_encode($pontoModel->buscarPontosComContagem($_SESSION['usuario_id']));
        exit;
    }

    public function configurarViagem() {
        $this->requireMotoristaJson();

        $linhaId = trim($_POST['linha_id'] ?? '');
        $veiculoId = trim($_POST['veiculo_id'] ?? '');
        $direcao = trim($_POST['direcao'] ?? '');

        if ($linhaId === '' || $veiculoId === '' || $direcao === '') {
            echo json_encode(['success' => false, 'message' => 'Selecione a linha, o veiculo e a direcao.']);
            exit;
        }

        $pontoModel = new Ponto();
        $ok = $pontoModel->configurarViagemDoDia($_SESSION['usuario_id'], (int) $linhaId, (int) $veiculoId, $direcao);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Viagem do dia configurada com sucesso.' : 'Nao foi possivel configurar a viagem do dia.',
            'trip' => $ok ? $this->tripResponse($pontoModel) : null,
        ]);
        exit;
    }

    public function iniciarRota() {
        $this->requireMotoristaJson();

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('em_rota', $_SESSION['usuario_id']);
        $trip = $ok ? $this->tripResponse($pontoModel) : null;

        if ($ok && $trip) {
            $this->dispatchTripStartWebhook($trip);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Rota iniciada com sucesso.' : 'Configure a viagem do dia antes de iniciar a rota.',
            'trip' => $trip,
        ]);
        exit;
    }

    public function finalizarRota() {
        $this->requireMotoristaJson();

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('aguardando_encerramento', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Chegada registrada. Agora acompanhe os retornos e encerre o dia pela sidebar.' : 'Nao foi possivel finalizar a rota.',
            'trip' => $ok ? $this->tripResponse($pontoModel) : null,
        ]);
        exit;
    }

    public function encerrarDia() {
        $this->requireMotoristaJson();

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarStatusViagem('finalizada', $_SESSION['usuario_id']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Dia encerrado com sucesso.' : 'Nao foi possivel encerrar o dia.',
            'trip' => $ok ? $this->tripResponse($pontoModel) : null,
        ]);
        exit;
    }

    public function atualizarLocalizacao() {
        $this->requireMotoristaJson();

        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $latitude = isset($payload['latitude']) ? (float) $payload['latitude'] : null;
        $longitude = isset($payload['longitude']) ? (float) $payload['longitude'] : null;

        if ($latitude === null || $longitude === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Latitude e longitude sao obrigatorias.']);
            exit;
        }

        $pontoModel = new Ponto();
        $ok = $pontoModel->atualizarLocalizacaoViagemAtiva($_SESSION['usuario_id'], $latitude, $longitude);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Localizacao atualizada.' : 'Nao foi possivel atualizar a localizacao.',
        ]);
        exit;
    }

    private function dispatchTripStartWebhook(array $trip) {
        $payload = [
            'evento' => 'viagem_iniciada',
            'motorista' => $_SESSION['usuario_nome'] ?? 'Motorista',
            'linha' => $trip['nome_linha'] ?? '',
            'veiculo' => trim(($trip['veiculo_identificador'] ?? '') . ' ' . ($trip['veiculo_placa'] ?? '')),
            'horario_inicio' => (new DateTime('now'))->format('Y-m-d H:i:s'),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            error_log('Webhook de inicio de viagem: falha ao serializar payload.');
            return;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($this->tripStartWebhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json),
                ],
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_TIMEOUT => 8,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false || $httpCode >= 400) {
                error_log('Webhook de inicio de viagem falhou: ' . curl_error($ch) . ' HTTP ' . $httpCode);
            }

            curl_close($ch);
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . 'Content-Length: ' . strlen($json) . "\r\n",
                'content' => $json,
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($this->tripStartWebhookUrl, false, $context);
        if ($response === false) {
            error_log('Webhook de inicio de viagem falhou via file_get_contents.');
        }
    }
}
?>
