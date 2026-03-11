-- Criar tabelas para o sistema de gestão de regime de trabalho

-- Tabela de funcionários
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de registros de regime de trabalho
CREATE TABLE IF NOT EXISTS registros_regime (
    id INT PRIMARY KEY AUTO_INCREMENT,
    funcionario_id INT NOT NULL,
    data DATE NOT NULL,
    turno ENUM('manhã', 'tarde') NOT NULL,
    status ENUM('presencial', 'homeoffice', 'férias', 'afastamento') NOT NULL,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registro (funcionario_id, data, turno)
);

-- Tabela de dias sem necessidade de escala (feriados e dias nao trabalhados)
CREATE TABLE IF NOT EXISTS dias_nao_trabalhados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data DATE NOT NULL UNIQUE,
    tipo ENUM('feriado', 'nao_trabalhado') NOT NULL DEFAULT 'feriado',
    descricao VARCHAR(120) NULL,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir funcionários padrão
INSERT IGNORE INTO funcionarios (nome) VALUES 
    ('João Silva'),
    ('Maria Santos'),
    ('Pedro Costa'),
    ('Ana Paula'),
    ('Carlos Oliveira');
