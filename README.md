# Sistema de Gestão de Regime de Trabalho

Um sistema web para gerenciar o regime de trabalho (presencial/home office/férias/afastamento) de funcionários de um setor, com visualização geral e alertas para dias sem presença.

## 🎯 Funcionalidades

- **Registro de Regime de Trabalho**: Funcionários selecionam seu nome e registram seu regime por turno (Manhã/Tarde)
- **Suporte a Dois Turnos**: Cada dia permite configurar separadamente Manhã e Tarde
- **Quatro Opções de Status**:
  - 🏢 Presencial
  - 🏠 Home Office
  - 🏖️ Férias
  - 🚫 Afastamento
- **Visão Geral**: Matriz completa mostrando presença de todos os funcionários
- **Alertas Automáticos**: Destaca em vermelho dias/turnos sem ninguém presencialmente
- **Navegação por Mês**: Visualize dados de meses passados e futuros
- **Sem Login**: Acesso direto com seleção de funcionário

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache, Nginx, etc.)
- Bootstrap 5.3 (CDN)

## 🚀 Instalação

### 1. Preparar o Banco de Dados

```bash
# Criar banco de dados
mysql -u root -p -e "CREATE DATABASE regime_trabalho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Criar usuário (opcional, mas recomendado)
mysql -u root -p -e "CREATE USER 'regime_user'@'localhost' IDENTIFIED BY 'regime123';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON regime_trabalho.* TO 'regime_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Importar schema
mysql -u regime_user -p regime_trabalho < schema.sql
```

### 2. Copiar Arquivos para o Servidor

```bash
# Copiar para o diretório raiz do Apache (geralmente /var/www/html)
cp -r regime-trabalho/* /var/www/html/

# Ou para um subdiretório
cp -r regime-trabalho/* /var/www/html/regime-trabalho/
```

### 3. Configurar Permissões

```bash
# Dar permissão de leitura/escrita
chmod -R 755 /var/www/html/regime-trabalho/
```

### 4. Configurar Conexão com Banco de Dados

Edite o arquivo `config.php` e atualize as credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'regime_user');
define('DB_PASS', 'regime123');
define('DB_NAME', 'regime_trabalho');
```

## 📁 Estrutura de Arquivos

```
regime-trabalho/
├── config.php              # Configuração do banco de dados
├── functions.php           # Funções auxiliares
├── index.php              # Página de registro de regime
├── visao-geral.php        # Página de visão geral e alertas
├── schema.sql             # Script de criação do banco
├── .htaccess              # Configuração Apache (opcional)
└── README.md              # Este arquivo
```

## 📖 Como Usar

### Página de Registro (index.php)

1. Acesse `http://seu-dominio.com/regime-trabalho/index.php`
2. Selecione seu nome na lista de funcionários (esquerda)
3. Clique em um dia do calendário
4. Selecione o status para Manhã e Tarde
5. Clique em "Salvar"

### Página de Visão Geral (visao-geral.php)

1. Acesse `http://seu-dominio.com/regime-trabalho/visao-geral.php`
2. Veja a matriz completa de presença
3. Identifique dias/turnos sem ninguém presencialmente (destacados em vermelho)
4. Use os botões "Anterior" e "Próximo" para navegar entre meses

## 🔧 Configuração Avançada

### Adicionar Novos Funcionários

Insira diretamente no banco de dados:

```sql
INSERT INTO funcionarios (nome) VALUES ('Nome do Funcionário');
```

### Alterar Credenciais do Banco

Edite `config.php`:

```php
define('DB_HOST', 'seu-host');
define('DB_USER', 'seu-usuario');
define('DB_PASS', 'sua-senha');
define('DB_NAME', 'seu-banco');
```

### Personalizar Timezone

Edite `functions.php`:

```php
date_default_timezone_set('America/Sao_Paulo'); // Altere conforme necessário
```

## 🔐 Segurança

- Senhas do banco de dados devem ser fortes
- Use HTTPS em produção
- Restrinja acesso ao `config.php` via `.htaccess`
- Faça backups regulares do banco de dados

## 📝 Backup do Banco de Dados

```bash
# Fazer backup
mysqldump -u regime_user -p regime_trabalho > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -u regime_user -p regime_trabalho < backup_20240101.sql
```

## 🐛 Troubleshooting

### Erro de Conexão com Banco de Dados

- Verifique se MySQL está rodando
- Confirme as credenciais em `config.php`
- Verifique se o usuário tem permissões corretas

### Dados não são salvos

- Verifique permissões do diretório
- Confirme que o banco de dados foi criado corretamente
- Verifique os logs do PHP/MySQL

### Página em branco

- Ative exibição de erros em `config.php`:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```

## 📞 Suporte

Para problemas ou dúvidas, verifique:
1. Os logs do servidor web
2. Os logs do MySQL
3. As permissões de arquivo/diretório

## 📄 Licença

Este sistema é fornecido como está, sem garantias.

## 🎨 Personalização

O sistema usa Bootstrap 5.3 e pode ser facilmente personalizado:

- **Cores**: Edite as variáveis CSS nos arquivos PHP
- **Tipografia**: Modifique as fontes no `<head>` dos arquivos
- **Layout**: Ajuste o CSS inline nos arquivos PHP

---

**Versão**: 1.0.0  
**Data**: 2026-02-23  
**Desenvolvido com**: PHP, MySQL, Bootstrap 5.3
