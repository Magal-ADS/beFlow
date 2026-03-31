<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$config = $database->getConfig();
$driver = $database->getDriver();

try {
    if ($driver === 'mysql') {
        $serverPdo = $database->createPdo(false);
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    $pdo = $database->getConnection();
    echo "Conectado ao banco {$driver}: {$config['dbname']} @ {$config['host']}\n\n";
} catch (Throwable $e) {
    die("ERRO ao conectar: {$e->getMessage()}\n");
}

echo "Limpando estrutura antiga...\n";

$dropTables = [
    'confirmacoes',
    'viagens',
    'horarios_base',
    'pontos',
    'alunos',
    'veiculo',
    'linhas',
    'usuarios',
    'empresa',
];

if ($driver === 'mysql') {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($dropTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
} else {
    $pdo->exec('DROP TABLE IF EXISTS confirmacoes, viagens, horarios_base, pontos, alunos, veiculo, linhas, usuarios, empresa CASCADE');
}

echo "Criando tabelas...\n";

$queries = [];

if ($driver === 'mysql') {
    $queries = [
        'empresa' => "
            CREATE TABLE empresa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                cnpj VARCHAR(18) NOT NULL UNIQUE,
                telefone VARCHAR(15)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'usuarios' => "
            CREATE TABLE usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                telefone VARCHAR(20),
                senha VARCHAR(255) NOT NULL,
                tipo_usuario VARCHAR(50) NOT NULL,
                empresa_id INT NULL,
                CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'linhas' => "
            CREATE TABLE linhas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                empresa_id INT NOT NULL,
                CONSTRAINT fk_linhas_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'veiculo' => "
            CREATE TABLE veiculo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_identificador VARCHAR(50) NOT NULL,
                placa VARCHAR(10) NOT NULL UNIQUE,
                empresa_id INT NOT NULL,
                CONSTRAINT fk_veiculo_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'alunos' => "
            CREATE TABLE alunos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL UNIQUE,
                turno VARCHAR(20),
                escola VARCHAR(100),
                CONSTRAINT fk_alunos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'pontos' => "
            CREATE TABLE pontos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(150) NOT NULL,
                latitude DECIMAL(10,8) NOT NULL,
                longitude DECIMAL(11,8) NOT NULL,
                ordem_na_linha INT NOT NULL,
                linha_id INT NOT NULL,
                CONSTRAINT fk_pontos_linha FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'horarios_base' => "
            CREATE TABLE horarios_base (
                id INT AUTO_INCREMENT PRIMARY KEY,
                linha_id INT NOT NULL,
                turno VARCHAR(20) NOT NULL,
                hora_saida_garagem TIME NOT NULL,
                CONSTRAINT fk_horarios_linha FOREIGN KEY (linha_id) REFERENCES linhas (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'viagens' => "
            CREATE TABLE viagens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                horario_base_id INT NOT NULL,
                motorista_id INT NOT NULL,
                veiculo_id INT NOT NULL,
                status VARCHAR(50) DEFAULT 'aguardando',
                data_viagem DATE NOT NULL,
                latitude_atual DECIMAL(10,8) NULL,
                longitude_atual DECIMAL(11,8) NULL,
                UNIQUE KEY uniq_viagem_dia (horario_base_id, motorista_id, veiculo_id, data_viagem),
                CONSTRAINT fk_viagens_horario FOREIGN KEY (horario_base_id) REFERENCES horarios_base (id) ON DELETE CASCADE,
                CONSTRAINT fk_viagens_motorista FOREIGN KEY (motorista_id) REFERENCES usuarios (id) ON DELETE CASCADE,
                CONSTRAINT fk_viagens_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculo (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'confirmacoes' => "
            CREATE TABLE confirmacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                viagem_id INT NOT NULL,
                ponto_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_confirmacao (aluno_id, viagem_id, ponto_id, tipo),
                CONSTRAINT fk_conf_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
                CONSTRAINT fk_conf_viagem FOREIGN KEY (viagem_id) REFERENCES viagens (id) ON DELETE CASCADE,
                CONSTRAINT fk_conf_ponto FOREIGN KEY (ponto_id) REFERENCES pontos (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
    ];
} else {
    $queries = [
        'empresa' => "
            CREATE TABLE empresa (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                cnpj VARCHAR(18) NOT NULL UNIQUE,
                telefone VARCHAR(15)
            );
        ",
        'usuarios' => "
            CREATE TABLE usuarios (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                telefone VARCHAR(20),
                senha VARCHAR(255) NOT NULL,
                tipo_usuario VARCHAR(50) NOT NULL,
                empresa_id INT NULL,
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
                CONSTRAINT uniq_viagem_dia UNIQUE (horario_base_id, motorista_id, veiculo_id, data_viagem),
                CONSTRAINT fk_viagens_horario FOREIGN KEY (horario_base_id) REFERENCES horarios_base (id) ON DELETE CASCADE,
                CONSTRAINT fk_viagens_motorista FOREIGN KEY (motorista_id) REFERENCES usuarios (id) ON DELETE CASCADE,
                CONSTRAINT fk_viagens_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculo (id) ON DELETE CASCADE
            );
        ",
        'confirmacoes' => "
            CREATE TABLE confirmacoes (
                id SERIAL PRIMARY KEY,
                aluno_id INT NOT NULL,
                viagem_id INT NOT NULL,
                ponto_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uniq_confirmacao UNIQUE (aluno_id, viagem_id, ponto_id, tipo),
                CONSTRAINT fk_conf_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
                CONSTRAINT fk_conf_viagem FOREIGN KEY (viagem_id) REFERENCES viagens (id) ON DELETE CASCADE,
                CONSTRAINT fk_conf_ponto FOREIGN KEY (ponto_id) REFERENCES pontos (id) ON DELETE CASCADE
            );
        ",
    ];
}

$created = 0;
$errors = 0;

foreach ($queries as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] {$name}\n";
        $created++;
    } catch (Throwable $e) {
        echo "[ERRO] {$name}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\nResultado: {$created}/" . count($queries) . " tabelas criadas";
if ($errors > 0) {
    echo " ({$errors} erros)\n";
} else {
    echo " com sucesso.\n";
}
