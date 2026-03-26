<?php
// ==========================================================
// MIGRATION - Cria toda a estrutura do banco PostgreSQL (Heroku)
// ==========================================================
// Uso no terminal: php migrate.php
//
// Este script cria todas as tabelas na ordem correta,
// respeitando foreign keys (chaves estrangeiras). 
// Usa IF NOT EXISTS e ON CONFLICT para ser 100% seguro 
// de rodar múltiplas vezes sem quebrar o banco.
// ==========================================================

// Carrega .env (Caso você use no futuro para produção)
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

// Configuração de conexão flexível (Funciona no Postgres local e no Heroku)
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parseia URL de produção (Ex: Heroku Postgres, Railway)
    $url = parse_url($databaseUrl);
    $host = $url["host"];
    $port = $url["port"] ?? 5432; // Porta padrão do Postgres
    $user = $url["user"];
    $pass = $url["pass"] ?? '';
    $db   = ltrim($url["path"], "/");
} else {
    // Fallback padrão para o Postgres local
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: 5432;
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: 'sua_senha_local'; // Mude para a sua senha do Postgres local
    $db   = getenv('DB_NAME') ?: 'beflow';
}

// DSN para PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Conectado ao banco PostgreSQL: $db @ $host\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: Verifique se o banco '$db' existe e se as credenciais estão corretas.\nDetalhe: " . $e->getMessage() . "\n");
}

// ==========================================================
// CONFIGURAÇÕES GERAIS E TABELAS (Ordem respeita as FKs)
// ==========================================================

// Prepara o banco para a transação
$pdo->exec("SET timezone = 'UTC';");

$tabelas = [

    // 1. SEM DEPENDÊNCIAS
    'empresa' => "
        CREATE TABLE IF NOT EXISTS empresa (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          cnpj VARCHAR(18) NOT NULL UNIQUE,
          telefone VARCHAR(15)
        );

        INSERT INTO empresa (id, nome, cnpj, telefone) 
        VALUES (1, 'Viação BeFlow', '12.345.678/0001-99', '16999999999')
        ON CONFLICT (id) DO NOTHING;
    ",

    'viagem_atual' => "
        CREATE TABLE IF NOT EXISTS viagem_atual (
          id INT PRIMARY KEY,
          status VARCHAR(50) DEFAULT 'aguardando'
        );

        INSERT INTO viagem_atual (id, status) 
        VALUES (1, 'aguardando')
        ON CONFLICT (id) DO NOTHING;
    ",

    // 2. DEPENDEM DE EMPRESA
    'usuarios' => "
        CREATE TABLE IF NOT EXISTS usuarios (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          email VARCHAR(100) NOT NULL UNIQUE,
          telefone VARCHAR(20),
          senha VARCHAR(255) NOT NULL,
          tipo_usuario VARCHAR(50) NOT NULL,
          empresa_id INT,
          CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE SET NULL
        );

        INSERT INTO usuarios (id, nome, email, telefone, senha, tipo_usuario, empresa_id) VALUES
        (1, 'Amanda Amorin', 'amandaaluno@gmail.com', '16997435710', '123456', 'aluno', 1),
        (2, 'Carlos Motorista', 'motorista@beflow.com', NULL, '123', 'motorista', 1)
        ON CONFLICT (email) DO NOTHING;
    ",

    'linhas' => "
        CREATE TABLE IF NOT EXISTS linhas (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          empresa_id INT NOT NULL,
          CONSTRAINT fk_linhas_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        );

        INSERT INTO linhas (id, nome, empresa_id) 
        VALUES (1, 'Linha 302 - Centro', 1)
        ON CONFLICT (id) DO NOTHING;
    ",

    'veiculo' => "
        CREATE TABLE IF NOT EXISTS veiculo (
          id SERIAL PRIMARY KEY,
          numero_identificador VARCHAR(50) NOT NULL,
          placa VARCHAR(10) NOT NULL UNIQUE,
          empresa_id INT NOT NULL,
          CONSTRAINT fk_veiculo_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        );
    ",

    // 3. DEPENDEM DE USUARIOS
    'alunos' => "
        CREATE TABLE IF NOT EXISTS alunos (
          id SERIAL PRIMARY KEY,
          usuario_id INT NOT NULL,
          turno VARCHAR(20),
          escola VARCHAR(100),
          CONSTRAINT fk_alunos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
        );
    ",

    // 4. DEPENDEM DE LINHAS
    'pontos' => "
        CREATE TABLE IF NOT EXISTS pontos (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(150) NOT NULL,
          latitude NUMERIC(10,8) NOT NULL,
          longitude NUMERIC(11,8) NOT NULL,
          ordem_na_linha INT NOT NULL,
          linha_id INT NOT NULL,
          CONSTRAINT fk_pontos_linha FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
        );

        INSERT INTO pontos (id, nome, latitude, longitude, ordem_na_linha, linha_id) VALUES
        (1, 'Ponto Praça Central', -21.40590000, -48.50520000, 1, 1),
        (2, 'Ponto Escola Objetivo', -21.41000000, -48.51000000, 2, 1),
        (3, 'Ponto Hospital Unimed', -21.41500000, -48.52000000, 3, 1)
        ON CONFLICT (id) DO NOTHING;
    ",

    // 5. DEPENDEM DE PONTOS E USUARIOS
    'confirmacoes' => "
        CREATE TABLE IF NOT EXISTS confirmacoes (
          id SERIAL PRIMARY KEY,
          usuario_id INT NOT NULL,
          ponto_id INT NOT NULL,
          data_confirmacao DATE NOT NULL,
          hora_confirmacao TIME NOT NULL,
          CONSTRAINT fk_conf_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
          CONSTRAINT fk_conf_ponto FOREIGN KEY (ponto_id) REFERENCES pontos (id) ON DELETE CASCADE
        );
    ",
];

// ==========================================================
// EXECUÇÃO
// ==========================================================

$total = count($tabelas);
$criadas = 0;
$erros = 0;

echo "Estruturando e populando $total tabelas...\n";
echo str_repeat('-', 50) . "\n";

// No PostgreSQL a ordem das tabelas já garante que as Foreign Keys não quebrem.
foreach ($tabelas as $nome => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] $nome\n";
        $criadas++;
    } catch (PDOException $e) {
        echo "[ERRO] $nome: " . $e->getMessage() . "\n";
        $erros++;
    }
}

// ==========================================================
// RESET DE SEQUENCES (SERIAL)
// ==========================================================
// Como fizemos INSERTs manuais com IDs fixos (ex: id = 1), 
// precisamos avisar o Postgres para continuar a contagem a partir do último ID.
try {
    $pdo->exec("SELECT setval(pg_get_serial_sequence('empresa', 'id'), coalesce(max(id),0) + 1, false) FROM empresa;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('usuarios', 'id'), coalesce(max(id),0) + 1, false) FROM usuarios;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('linhas', 'id'), coalesce(max(id),0) + 1, false) FROM linhas;");
    $pdo->exec("SELECT setval(pg_get_serial_sequence('pontos', 'id'), coalesce(max(id),0) + 1, false) FROM pontos;");
} catch (Exception $e) {
    // Silencioso, apenas se as tabelas ainda não existirem
}

echo str_repeat('-', 50) . "\n";
echo "\nResultado: $criadas/$total processadas com sucesso.";
if ($erros > 0) {
    echo " ($erros erros)";
} else {
    echo " Tudo limpo e rodando perfeito no Postgres!";
}
echo "\n";
?>