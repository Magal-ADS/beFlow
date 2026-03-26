<?php
// ==========================================================
// MIGRATION - Cria toda a estrutura do banco MySQL/MariaDB
// ==========================================================
// Uso no terminal: php migrate.php
//
// Este script cria todas as tabelas na ordem correta,
// respeitando foreign keys (chaves estrangeiras). 
// Usa IF NOT EXISTS e INSERT IGNORE para ser 100% seguro 
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

// Configuração de conexão flexível (Funciona no XAMPP local e em Produção)
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parseia URL de produção (Ex: ClearDB, JawsDB, Railway)
    $url = parse_url($databaseUrl);
    $host = $url["host"];
    $port = $url["port"] ?? 3306;
    $user = $url["user"];
    $pass = $url["pass"] ?? '';
    $db   = ltrim($url["path"], "/");
} else {
    // Fallback padrão para o XAMPP local
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: 3306;
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'beflow';
}

// DSN para MySQL/MariaDB
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Conectado ao banco MySQL: $db @ $host\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar: Verifique se o banco '$db' existe no phpMyAdmin.\nDetalhe: " . $e->getMessage() . "\n");
}

// ==========================================================
// CONFIGURAÇÕES GERAIS E TABELAS (Ordem respeita as FKs)
// ==========================================================

// Prepara o banco para a transação
$pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
$pdo->exec("SET time_zone = '+00:00';");

$tabelas = [

    // 1. SEM DEPENDÊNCIAS
    'empresa' => "
        CREATE TABLE IF NOT EXISTS empresa (
          id int(11) NOT NULL AUTO_INCREMENT,
          nome varchar(100) NOT NULL,
          cnpj varchar(18) NOT NULL,
          telefone varchar(15) DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY cnpj (cnpj)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        INSERT IGNORE INTO empresa (id, nome, cnpj, telefone) 
        VALUES (1, 'Viação BeFlow', '12.345.678/0001-99', '16999999999');
    ",

    'viagem_atual' => "
        CREATE TABLE IF NOT EXISTS viagem_atual (
          id int(11) NOT NULL,
          status varchar(50) DEFAULT 'aguardando',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        INSERT IGNORE INTO viagem_atual (id, status) 
        VALUES (1, 'aguardando');
    ",

    // 2. DEPENDEM DE EMPRESA
    'usuarios' => "
        CREATE TABLE IF NOT EXISTS usuarios (
          id int(11) NOT NULL AUTO_INCREMENT,
          nome varchar(100) NOT NULL,
          email varchar(100) NOT NULL,
          telefone varchar(20) DEFAULT NULL,
          senha varchar(255) NOT NULL,
          tipo_usuario enum('admin_geral','admin_empresa','motorista','aluno') NOT NULL,
          empresa_id int(11) DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY email (email),
          KEY empresa_id (empresa_id),
          CONSTRAINT usuarios_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresa (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        INSERT IGNORE INTO usuarios (id, nome, email, telefone, senha, tipo_usuario, empresa_id) VALUES
        (1, 'Amanda Amorin', 'amandaaluno@gmail.com', '16997435710', '123456', 'aluno', 1),
        (2, 'Carlos Motorista', 'motorista@beflow.com', NULL, '123', 'motorista', 1);
    ",

    'linhas' => "
        CREATE TABLE IF NOT EXISTS linhas (
          id int(11) NOT NULL AUTO_INCREMENT,
          nome varchar(100) NOT NULL,
          empresa_id int(11) NOT NULL,
          PRIMARY KEY (id),
          KEY empresa_id (empresa_id),
          CONSTRAINT linhas_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        INSERT IGNORE INTO linhas (id, nome, empresa_id) 
        VALUES (1, 'Linha 302 - Centro', 1);
    ",

    'veiculo' => "
        CREATE TABLE IF NOT EXISTS veiculo (
          id int(11) NOT NULL AUTO_INCREMENT,
          numero_identificador varchar(50) NOT NULL,
          placa varchar(10) NOT NULL,
          empresa_id int(11) NOT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY placa (placa),
          KEY empresa_id (empresa_id),
          CONSTRAINT veiculo_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ",

    // 3. DEPENDEM DE USUARIOS
    'alunos' => "
        CREATE TABLE IF NOT EXISTS alunos (
          id int(11) NOT NULL AUTO_INCREMENT,
          usuario_id int(11) NOT NULL,
          turno varchar(20) DEFAULT NULL,
          escola varchar(100) DEFAULT NULL,
          PRIMARY KEY (id),
          KEY usuario_id (usuario_id),
          CONSTRAINT alunos_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ",

    // 4. DEPENDEM DE LINHAS
    'pontos' => "
        CREATE TABLE IF NOT EXISTS pontos (
          id int(11) NOT NULL AUTO_INCREMENT,
          nome varchar(150) NOT NULL,
          latitude decimal(10,8) NOT NULL,
          longitude decimal(11,8) NOT NULL,
          ordem_na_linha int(11) NOT NULL,
          linha_id int(11) NOT NULL,
          PRIMARY KEY (id),
          KEY linha_id (linha_id),
          CONSTRAINT pontos_ibfk_1 FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        INSERT IGNORE INTO pontos (id, nome, latitude, longitude, ordem_na_linha, linha_id) VALUES
        (1, 'Ponto Praça Central', -21.40590000, -48.50520000, 1, 1),
        (2, 'Ponto Escola Objetivo', -21.41000000, -48.51000000, 2, 1),
        (3, 'Ponto Hospital Unimed', -21.41500000, -48.52000000, 3, 1);
    ",

    // 5. DEPENDEM DE PONTOS E USUARIOS
    'confirmacoes' => "
        CREATE TABLE IF NOT EXISTS confirmacoes (
          id int(11) NOT NULL AUTO_INCREMENT,
          usuario_id int(11) NOT NULL,
          ponto_id int(11) NOT NULL,
          data_confirmacao date NOT NULL,
          hora_confirmacao time NOT NULL,
          PRIMARY KEY (id),
          KEY usuario_id (usuario_id),
          KEY ponto_id (ponto_id),
          CONSTRAINT confirmacoes_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
          CONSTRAINT confirmacoes_ibfk_2 FOREIGN KEY (ponto_id) REFERENCES pontos (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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

// Desativa verificação de chaves temporariamente para evitar conflitos na criação
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

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

// Reativa verificação de chaves
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo str_repeat('-', 50) . "\n";
echo "\nResultado: $criadas/$total processadas com sucesso.";
if ($erros > 0) {
    echo " ($erros erros)";
} else {
    echo " Tudo limpo e rodando!";
}
echo "\n";