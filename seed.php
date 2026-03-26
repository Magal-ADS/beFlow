<?php
// ==========================================================
// SEEDER - Dados iniciais do BeFlow (PostgreSQL)
// ATUALIZADO: Povoando tabela usuarios E alunos (Herança)
// ==========================================================
// Uso no terminal: php seed.php
// ==========================================================

// Carrega .env (se existir)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Configuração de conexão flexível (Local ou Heroku)
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $host = $url["host"];
    $port = $url["port"] ?? 5432;
    $user = $url["user"];
    $pass = $url["pass"] ?? '';
    $db   = ltrim($url["path"], "/");
} else {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: 5432;
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: 'sua_senha_local';
    $db   = getenv('DB_NAME') ?: 'beflow';
}

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Conectado ao banco PostgreSQL: $db @ $host\n";
    echo str_repeat('-', 50) . "\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: " . $e->getMessage() . "\n");
}

// ==========================================================
// 1. Empresa Principal
// ==========================================================
echo "1. Configurando Empresa Base...\n";

$stmt = $pdo->prepare("
    INSERT INTO empresa (id, nome, cnpj, telefone) 
    VALUES (1, 'Viação BeFlow', '12.345.678/0001-99', '16999999999')
    ON CONFLICT (id) DO NOTHING
");
$stmt->execute();
echo $stmt->rowCount() > 0 
    ? "   [OK] Empresa Viação BeFlow criada\n" 
    : "   [--] Empresa já existe\n";


// ==========================================================
// 2. Usuários do Sistema E Alunos (Herança)
// ==========================================================
echo "\n2. Povoando Usuários e Alunos...\n";

$usuarios = [
    // O Admin
    ['nome' => 'Admin BeFlow', 'email' => 'admin@beflow.com', 'senha' => 'admin123', 'telefone' => '', 'tipo' => 'admin_empresa', 'empresa_id' => 1],
    
    // Motoristas
    ['nome' => 'Carlos Motorista', 'email' => 'motorista@beflow.com', 'senha' => '123', 'telefone' => '', 'tipo' => 'motorista', 'empresa_id' => 1],
    ['nome' => 'Ricardo Oliveira', 'email' => 'ricardo@beflow.com', 'senha' => '123', 'telefone' => '16991112233', 'tipo' => 'motorista', 'empresa_id' => 1],
    
    // Alunos (Adicionado dados específicos do aluno)
    ['nome' => 'Amanda Amorin', 'email' => 'amandaaluno@gmail.com', 'senha' => '123456', 'telefone' => '16997435710', 'tipo' => 'aluno', 'empresa_id' => 1, 'turno' => 'Matutino', 'escola' => 'Objetivo'],
    ['nome' => 'João Vitor', 'email' => 'joao@gmail.com', 'senha' => '123456', 'telefone' => '16997547649', 'tipo' => 'aluno', 'empresa_id' => 1, 'turno' => 'Noturno', 'escola' => 'FATEC'],
    ['nome' => 'Isabela Silva', 'email' => 'isabela@gmail.com', 'senha' => '123456', 'telefone' => '16988444111', 'tipo' => 'aluno', 'empresa_id' => 1, 'turno' => 'Matutino', 'escola' => 'SESI'],
];

$stmtUsr = $pdo->prepare("
    INSERT INTO usuarios (nome, email, telefone, senha, tipo_usuario, empresa_id)
    VALUES (:nome, :email, :telefone, :senha, :tipo, :empresa_id)
    ON CONFLICT (email) DO NOTHING
    RETURNING id
");

// Prepara a query de inserir na tabela de alunos
$stmtAluno = $pdo->prepare("
    INSERT INTO alunos (usuario_id, turno, escola)
    VALUES (:usuario_id, :turno, :escola)
    ON CONFLICT (usuario_id) DO NOTHING
");

$usuariosCriados = [];

foreach ($usuarios as $u) {
    $stmtUsr->execute([
        ':nome'       => $u['nome'],
        ':email'      => $u['email'],
        ':telefone'   => $u['telefone'],
        ':senha'      => $u['senha'], 
        ':tipo'       => $u['tipo'],
        ':empresa_id' => $u['empresa_id'],
    ]);
    
    $inserted = $stmtUsr->rowCount() > 0;
    
    if ($inserted) {
        $usuariosCriados[] = "{$u['nome']} ({$u['tipo']})";
        
        // Pega o ID que acabou de ser gerado pelo banco
        $novoUsuarioId = $stmtUsr->fetchColumn();

        // Se o tipo for aluno, insere os dados dele na tabela ALUNOS
        if ($u['tipo'] === 'aluno' && $novoUsuarioId) {
            $stmtAluno->execute([
                ':usuario_id' => $novoUsuarioId,
                ':turno'      => $u['turno'] ?? 'Não informado',
                ':escola'     => $u['escola'] ?? 'Não informada'
            ]);
            echo "   [OK] {$u['email']} criado e vinculado como Aluno\n";
        } else {
            echo "   [OK] {$u['email']} criado\n";
        }
    } else {
        echo "   [--] {$u['email']} já existe\n";
    }
}


// ==========================================================
// 3. Linhas de Ônibus
// ==========================================================
echo "\n3. Criando Linhas...\n";

$stmtLinha = $pdo->prepare("
    INSERT INTO linhas (id, nome, empresa_id) 
    VALUES (1, 'Linha 302 - Centro', 1)
    ON CONFLICT (id) DO NOTHING
");
$stmtLinha->execute();
echo $stmtLinha->rowCount() > 0 
    ? "   [OK] Linha 302 - Centro\n" 
    : "   [--] Linha já existe\n";


// ==========================================================
// 4. Pontos de Parada
// ==========================================================
echo "\n4. Adicionando Pontos...\n";

$pontos = [
    ['id' => 1, 'nome' => 'Ponto Praça Central', 'lat' => '-21.40590000', 'lng' => '-48.50520000', 'ordem' => 1],
    ['id' => 2, 'nome' => 'Ponto Escola Objetivo', 'lat' => '-21.41000000', 'lng' => '-48.51000000', 'ordem' => 2],
    ['id' => 3, 'nome' => 'Ponto Hospital Unimed', 'lat' => '-21.41500000', 'lng' => '-48.52000000', 'ordem' => 3],
];

$stmtPts = $pdo->prepare("
    INSERT INTO pontos (id, nome, latitude, longitude, ordem_na_linha, linha_id)
    VALUES (:id, :nome, :lat, :lng, :ordem, 1)
    ON CONFLICT (id) DO NOTHING
");

foreach ($pontos as $p) {
    $stmtPts->execute([
        ':id'    => $p['id'],
        ':nome'  => $p['nome'],
        ':lat'   => $p['lat'],
        ':lng'   => $p['lng'],
        ':ordem' => $p['ordem'],
    ]);
    
    $inserted = $stmtPts->rowCount() > 0;
    echo $inserted
        ? "   [OK] {$p['nome']}\n"
        : "   [--] {$p['nome']} já existe\n";
}

// ==========================================================
// RESET DE SEQUENCES (SERIAL)
// ==========================================================
try {
    $pdo->exec("SELECT setval(pg_get_serial_sequence('empresa', 'id'), coalesce(max(id),0) + 1, false) FROM empresa;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('usuarios', 'id'), coalesce(max(id),0) + 1, false) FROM usuarios;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('alunos', 'id'), coalesce(max(id),0) + 1, false) FROM alunos;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('linhas', 'id'), coalesce(max(id),0) + 1, false) FROM linhas;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('pontos', 'id'), coalesce(max(id),0) + 1, false) FROM pontos;");
} catch (Exception $e) {
    // Ignora silenciosamente se houver erro nas sequences
}


echo "\n" . str_repeat('-', 50) . "\n";
echo "Seed concluído com sucesso no PostgreSQL!\n";

if (count($usuariosCriados) === 0) {
    echo "   [--] Nenhum usuário novo foi criado.\n";
} else {
    echo "Novos usuários habilitados no sistema:\n";
    foreach ($usuariosCriados as $usuarioCriado) {
        echo "   - $usuarioCriado\n";
    }
}
echo str_repeat('-', 50) . "\n";