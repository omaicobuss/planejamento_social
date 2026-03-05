<?php
/**
 * Configuração do Banco de Dados
 * Sistema de Gestão de Regime de Trabalho
 */

// Configurações de conexão
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mobuss_presenca');

// Criar conexão
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Definir charset para UTF-8
$conn->set_charset("utf8mb4");

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');
