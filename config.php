<?php
/**
 * Configuracao do Banco de Dados
 * Sistema de Gestão de Regime de Trabalho
 */

// Caminho do arquivo SQLite
define('DB_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'database.sqlite');

try {
    $conn = new PDO('sqlite:' . DB_FILE);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec('PRAGMA foreign_keys = ON;');
} catch (Throwable $e) {
    die('Erro de conexao com SQLite: ' . $e->getMessage());
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');
