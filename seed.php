<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$driver = $database->getDriver();

try {
    $pdo = $database->getConnection();
    echo "Conectado ao banco {$driver}\n";
    echo str_repeat('-', 50) . "\n";
} catch (Throwable $e) {
    die("ERRO ao conectar: {$e->getMessage()}\n");
}

function upsert(PDO $pdo, $driver, $table, array $data, array $uniqueColumns) {
    $columns = array_keys($data);
    $placeholders = array_map(function ($column) {
        return ':' . $column;
    }, $columns);

    if ($driver === 'mysql') {
        $updates = array_map(function ($column) use ($uniqueColumns) {
            if (in_array($column, $uniqueColumns, true)) {
                return "{$column} = {$column}";
            }

            return "{$column} = VALUES({$column})";
        }, $columns);

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
    } else {
        $updates = array_map(function ($column) use ($uniqueColumns) {
            if (in_array($column, $uniqueColumns, true)) {
                return "{$column} = EXCLUDED.{$column}";
            }

            return "{$column} = EXCLUDED.{$column}";
        }, $columns);

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")
                ON CONFLICT (" . implode(', ', $uniqueColumns) . ")
                DO UPDATE SET " . implode(', ', $updates);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
}

echo "1. Empresa base\n";
upsert($pdo, $driver, 'empresa', [
    'id' => 1,
    'nome' => 'Viacao BeFlow',
    'cnpj' => '12.345.678/0001-99',
    'telefone' => '16999999999',
], ['id']);
echo "   [OK] Empresa configurada\n";

echo "\n2. Usuarios e alunos\n";
$usuarios = [
    ['email' => 'admin@beflow.com', 'nome' => 'Admin BeFlow', 'telefone' => '', 'senha' => 'admin123', 'tipo_usuario' => 'admin_empresa', 'empresa_id' => 1],
    ['email' => 'motorista@beflow.com', 'nome' => 'Carlos Motorista', 'telefone' => '', 'senha' => '123', 'tipo_usuario' => 'motorista', 'empresa_id' => 1],
    ['email' => 'ricardo@beflow.com', 'nome' => 'Ricardo Oliveira', 'telefone' => '16991112233', 'senha' => '123', 'tipo_usuario' => 'motorista', 'empresa_id' => 1],
    ['email' => 'amandaaluno@gmail.com', 'nome' => 'Amanda Amorin', 'telefone' => '16997435710', 'senha' => '123456', 'tipo_usuario' => 'aluno', 'empresa_id' => 1, 'turno' => 'Matutino', 'escola' => 'Objetivo'],
    ['email' => 'joao@gmail.com', 'nome' => 'Joao Vitor', 'telefone' => '16997547649', 'senha' => '123456', 'tipo_usuario' => 'aluno', 'empresa_id' => 1, 'turno' => 'Noturno', 'escola' => 'FATEC'],
    ['email' => 'isabela@gmail.com', 'nome' => 'Isabela Silva', 'telefone' => '16988444111', 'senha' => '123456', 'tipo_usuario' => 'aluno', 'empresa_id' => 1, 'turno' => 'Matutino', 'escola' => 'SESI'],
];

foreach ($usuarios as $usuario) {
    $baseUsuario = $usuario;
    unset($baseUsuario['turno'], $baseUsuario['escola']);
    upsert($pdo, $driver, 'usuarios', $baseUsuario, ['email']);

    $stmtUsuario = $pdo->prepare("SELECT id, tipo_usuario FROM usuarios WHERE email = :email LIMIT 1");
    $stmtUsuario->execute(['email' => $usuario['email']]);
    $usuarioDb = $stmtUsuario->fetch();

    if ($usuarioDb && $usuarioDb['tipo_usuario'] === 'aluno') {
        upsert($pdo, $driver, 'alunos', [
            'usuario_id' => $usuarioDb['id'],
            'turno' => $usuario['turno'],
            'escola' => $usuario['escola'],
        ], ['usuario_id']);
    }

    echo "   [OK] {$usuario['email']}\n";
}

echo "\n3. Linha, veiculo e horario base\n";
upsert($pdo, $driver, 'linhas', [
    'id' => 1,
    'nome' => 'Linha 302 - Centro',
    'empresa_id' => 1,
], ['id']);
upsert($pdo, $driver, 'veiculo', [
    'id' => 1,
    'numero_identificador' => 'BUS-302',
    'placa' => 'BEF-0302',
    'empresa_id' => 1,
], ['id']);
upsert($pdo, $driver, 'horarios_base', [
    'id' => 1,
    'linha_id' => 1,
    'turno' => 'Matutino',
    'hora_saida_garagem' => '06:30:00',
], ['id']);
echo "   [OK] Estrutura da rota criada\n";

echo "\n4. Pontos\n";
$pontos = [
    ['id' => 1, 'nome' => 'Ponto Praca Central', 'latitude' => '-21.40590000', 'longitude' => '-48.50520000', 'ordem_na_linha' => 1, 'linha_id' => 1],
    ['id' => 2, 'nome' => 'Ponto Escola Objetivo', 'latitude' => '-21.41000000', 'longitude' => '-48.51000000', 'ordem_na_linha' => 2, 'linha_id' => 1],
    ['id' => 3, 'nome' => 'Ponto Hospital Unimed', 'latitude' => '-21.41500000', 'longitude' => '-48.52000000', 'ordem_na_linha' => 3, 'linha_id' => 1],
];

foreach ($pontos as $ponto) {
    upsert($pdo, $driver, 'pontos', $ponto, ['id']);
    echo "   [OK] {$ponto['nome']}\n";
}

echo "\n5. Viagem do dia\n";
$stmtMotorista = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
$stmtMotorista->execute(['email' => 'motorista@beflow.com']);
$motoristaId = $stmtMotorista->fetchColumn();

if ($motoristaId) {
    if ($driver === 'mysql') {
        $sqlViagem = "
            INSERT INTO viagens (horario_base_id, motorista_id, veiculo_id, status, data_viagem, latitude_atual, longitude_atual)
            VALUES (1, :motorista_id, 1, 'aguardando', CURDATE(), NULL, NULL)
            ON DUPLICATE KEY UPDATE motorista_id = VALUES(motorista_id), status = 'aguardando'
        ";
    } else {
        $sqlViagem = "
            INSERT INTO viagens (horario_base_id, motorista_id, veiculo_id, status, data_viagem, latitude_atual, longitude_atual)
            VALUES (1, :motorista_id, 1, 'aguardando', CURRENT_DATE, NULL, NULL)
            ON CONFLICT ON CONSTRAINT uniq_viagem_dia DO UPDATE SET status = EXCLUDED.status
        ";
    }

    $stmtViagem = $pdo->prepare($sqlViagem);
    $stmtViagem->execute(['motorista_id' => $motoristaId]);
    echo "   [OK] Viagem base do dia criada\n";
}

echo "\n" . str_repeat('-', 50) . "\n";
echo "Seed concluido com sucesso.\n";
