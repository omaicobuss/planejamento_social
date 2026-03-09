<?php
/**
 * Migra dados do dump MySQL (phpMyAdmin) para SQLite.
 *
 * Uso:
 * php migrate_dump_to_sqlite.php
 */

declare(strict_types=1);

$baseDir = __DIR__;
$dumpFile = $baseDir . DIRECTORY_SEPARATOR . 'mobuss_presenca.sql';
$sqliteFile = $baseDir . DIRECTORY_SEPARATOR . 'database.sqlite';

if (!file_exists($dumpFile)) {
    fwrite(STDERR, "Arquivo de dump nao encontrado: {$dumpFile}" . PHP_EOL);
    exit(1);
}

if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

try {
    $pdo = new PDO('sqlite:' . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $schema = <<<'SQL'
CREATE TABLE funcionarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL UNIQUE,
    data_criacao TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registros_regime (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    funcionario_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    turno TEXT NOT NULL CHECK (turno IN ('manhã', 'tarde')),
    status TEXT NOT NULL CHECK (status IN ('presencial', 'homeoffice', 'férias', 'afastamento')),
    data_registro TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(funcionario_id, data, turno),
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE
);
SQL;

    $pdo->exec($schema);

    $handle = fopen($dumpFile, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Nao foi possivel abrir o dump para leitura.');
    }

    $collecting = false;
    $statement = '';
    $insertCount = 0;

    while (($line = fgets($handle)) !== false) {
        $trimmed = ltrim($line);

        if (!$collecting && stripos($trimmed, 'INSERT INTO `funcionarios`') === 0) {
            $collecting = true;
            $statement = $line;
            continue;
        }

        if (!$collecting && stripos($trimmed, 'INSERT INTO `registros_regime`') === 0) {
            $collecting = true;
            $statement = $line;
            continue;
        }

        if ($collecting) {
            $statement .= $line;

            if (strpos($line, ';') !== false) {
                $pdo->exec($statement);
                $insertCount++;
                $collecting = false;
                $statement = '';
            }
        }
    }

    fclose($handle);

    if ($insertCount < 2) {
        throw new RuntimeException('Nenhum INSERT util foi encontrado no dump.');
    }

    $funcionarios = (int) $pdo->query('SELECT COUNT(*) FROM funcionarios')->fetchColumn();
    $registros = (int) $pdo->query('SELECT COUNT(*) FROM registros_regime')->fetchColumn();

    echo "SQLite criado com sucesso: {$sqliteFile}" . PHP_EOL;
    echo "Funcionarios importados: {$funcionarios}" . PHP_EOL;
    echo "Registros importados: {$registros}" . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro na migracao: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
