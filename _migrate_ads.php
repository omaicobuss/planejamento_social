<?php
// Proteção: só executa se passar a chave correta na URL
// Exemplo: https://seuservidor.com.br/_migrate_ads.php?chave=migrar123
$chave_esperada = 'migrar123'; // ALTERE para uma chave secreta sua
if (!isset($_GET['chave']) || $_GET['chave'] !== $chave_esperada) {
    http_response_code(403);
    die('Acesso negado.');
}

try {
    $db_path = __DIR__ . '/database.sqlite';
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se o valor 'ads' já é aceito tentando um INSERT em tabela temporária
    $current = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='registros_regime'")->fetchColumn();
    if (strpos($current, "'ads'") !== false) {
        die('Nada a fazer: o constraint já inclui "ads".');
    }

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec('BEGIN');
    $pdo->exec('ALTER TABLE registros_regime RENAME TO registros_regime_old');

    $sql = "CREATE TABLE registros_regime ("
         . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
         . "funcionario_id INTEGER NOT NULL,"
         . "data TEXT NOT NULL,"
         . "turno TEXT NOT NULL CHECK (turno IN ('manh\xc3\xa3', 'tarde')),"
         . "status TEXT NOT NULL CHECK (status IN ('presencial','homeoffice','f\xc3\xa9rias','afastamento','ads')),"
         . "data_registro TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,"
         . "UNIQUE(funcionario_id, data, turno),"
         . "FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE"
         . ")";
    $pdo->exec($sql);
    $pdo->exec('INSERT INTO registros_regime SELECT * FROM registros_regime_old');
    $pdo->exec('DROP TABLE registros_regime_old');
    $pdo->exec('COMMIT');
    $pdo->exec('PRAGMA foreign_keys = ON');

    echo 'Migração concluída com sucesso! Pode apagar este arquivo do servidor.';
} catch (Exception $e) {
    $pdo->exec('ROLLBACK');
    $pdo->exec('PRAGMA foreign_keys = ON');
    http_response_code(500);
    echo 'Erro: ' . htmlspecialchars($e->getMessage());
}
