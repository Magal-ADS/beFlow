<?php
// ==========================================================
// MIGRATION - Cria toda a estrutura do banco PostgreSQL (Heroku)
// ATUALIZADO PARA O DER FINAL (Com Herança e Viagens)
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
    echo "Conectado ao banco PostgreSQL: $db @ $host\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: Verifique se o banco '$db' existe e se as credenciais estão corretas.\nDetalhe: " . $e->getMessage() . "\n");
}

// ==========================================================
// CONFIGURAÇÕES GERAIS E LIMPEZA ESTRUTURAL
// ==========================================================

$pdo->exec("SET timezone = 'UTC';");

echo "Limpando estrutura antiga para aplicar o novo DER...\n";
// O CASCADE garante que ele apague as tabelas mesmo que tenham dependências (FKs)
$pdo->exec("DROP TABLE IF EXISTS confirmacoes, viagens, horarios_base, pontos, alunos, veiculo, linhas, usuarios, viagem_atual, empresa CASCADE;");

// ==========================================================
// CRIAÇÃO DAS TABELAS (Ordem respeita as FKs)
// ==========================================================

$tabelas = [

    // 1. SEM DEPENDÊNCIAS
    'empresa' => "
        CREATE TABLE empresa (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          cnpj VARCHAR(18) NOT NULL UNIQUE,
          telefone VARCHAR(15)
        );

        INSERT INTO empresa (id, nome, cnpj, telefone) 
        VALUES (1, 'Viação BeFlow', '12.345.678/0001-99', '16999999999')
        ON CONFLICT (id) DO NOTHING;
    ",

    // 2. DEPENDEM DE EMPRESA
    'usuarios' => "
        CREATE TABLE usuarios (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          email VARCHAR(100) NOT NULL UNIQUE,
          telefone VARCHAR(20),
          senha VARCHAR(255) NOT NULL,
          tipo_usuario VARCHAR(50) NOT NULL,
          empresa_id INT,
          CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE SET NULL
        );
    ",

    'linhas' => "
        CREATE TABLE linhas (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(100) NOT NULL,
          empresa_id INT NOT NULL,
          CONSTRAINT fk_linhas_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        );
    ",

    'veiculo' => "
        CREATE TABLE veiculo (
          id SERIAL PRIMARY KEY,
          numero_identificador VARCHAR(50) NOT NULL,
          placa VARCHAR(10) NOT NULL UNIQUE,
          empresa_id INT NOT NULL,
          CONSTRAINT fk_veiculo_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        );
    ",

    // 3. DEPENDEM DE USUARIOS E LINHAS
    'alunos' => "
        CREATE TABLE alunos (
          id SERIAL PRIMARY KEY,
          usuario_id INT NOT NULL UNIQUE,
          turno VARCHAR(20),
          escola VARCHAR(100),
          CONSTRAINT fk_alunos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
        );
    ",

    'pontos' => "
        CREATE TABLE pontos (
          id SERIAL PRIMARY KEY,
          nome VARCHAR(150) NOT NULL,
          latitude NUMERIC(10,8) NOT NULL,
          longitude NUMERIC(11,8) NOT NULL,
          ordem_na_linha INT NOT NULL,
          linha_id INT NOT NULL,
          CONSTRAINT fk_pontos_linha FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
        );
    ",

    'horarios_base' => "
        CREATE TABLE horarios_base (
          id SERIAL PRIMARY KEY,
          linha_id INT NOT NULL,
          turno VARCHAR(20) NOT NULL,
          hora_saida_garagem TIME NOT NULL,
          CONSTRAINT fk_horarios_linha FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
        );
    ",

    // 4. DEPENDEM DE HORARIOS, USUARIOS E VEICULOS (A NOVA TABELA DE VIAGENS DO DER)
    'viagens' => "
        CREATE TABLE viagens (
          id SERIAL PRIMARY KEY,
          horario_base_id INT NOT NULL,
          motorista_id INT NOT NULL,
          veiculo_id INT NOT NULL,
          status VARCHAR(50) DEFAULT 'aguardando',
          data_viagem DATE NOT NULL,
          latitude_atual NUMERIC(10,8),
          longitude_atual NUMERIC(11,8),
          CONSTRAINT fk_viagens_horario FOREIGN KEY (horario_base_id) REFERENCES horarios_base (id) ON DELETE CASCADE,
          CONSTRAINT fk_viagens_motorista FOREIGN KEY (motorista_id) REFERENCES usuarios (id) ON DELETE CASCADE,
          CONSTRAINT fk_viagens_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculo (id) ON DELETE CASCADE
        );
    ",

    // 5. DEPENDEM DE ALUNOS, VIAGENS E PONTOS
    'confirmacoes' => "
        CREATE TABLE confirmacoes (
          id SERIAL PRIMARY KEY,
          aluno_id INT NOT NULL,
          viagem_id INT NOT NULL,
          ponto_id INT NOT NULL,
          tipo VARCHAR(50) NOT NULL,
          CONSTRAINT fk_conf_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
          CONSTRAINT fk_conf_viagem FOREIGN KEY (viagem_id) REFERENCES viagens (id) ON DELETE CASCADE,
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

echo "Estruturando $total tabelas...\n";
echo str_repeat('-', 50) . "\n";

foreach ($tabelas as $nome => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] Tabela $nome\n";
        $criadas++;
    } catch (PDOException $e) {
        echo "[ERRO] Tabela $nome: " . $e->getMessage() . "\n";
        $erros++;
    }
}

// Reset de Sequences da Empresa (pois fizemos um INSERT manual do ID 1 nela)
try {
    $pdo->exec("SELECT setval(pg_get_serial_sequence('empresa', 'id'), coalesce(max(id),0) + 1, false) FROM empresa;");
} catch (Exception $e) {}

echo str_repeat('-', 50) . "\n";
echo "\nResultado: $criadas/$total processadas com sucesso.";
if ($erros > 0) {
    echo " ($erros erros)";
} else {
    echo " Tudo limpo e rodando perfeito no Postgres! DER Aplicado.";
}
echo "\n";
?>