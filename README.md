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

- PHP 7.4 ou superior (com extensao `pdo_sqlite` habilitada)
- Servidor Web (Apache, Nginx, etc.)
- Bootstrap 5.3 (CDN)

## 🚀 Instalação

### 1. Preparar o Banco de Dados SQLite

O sistema agora usa SQLite com o arquivo `database.sqlite` na raiz do projeto.

Se voce ja tem o dump MySQL (`mobuss_presenca.sql`), gere o SQLite com:

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" migrate_dump_to_sqlite.php
```

Esse comando cria/recria o arquivo `database.sqlite` e importa os dados do dump.

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

### 4. Configurar Conexao com Banco de Dados

O arquivo `config.php` ja esta configurado para SQLite:

```php
define('DB_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'database.sqlite');
```

## 📁 Estrutura de Arquivos

```
regime-trabalho/
├── config.php              # Configuracao do banco (SQLite)
├── functions.php           # Funções auxiliares
├── index.php              # Página de registro de regime
├── visao-geral.php        # Página de visão geral e alertas
├── schema.sql             # Script de criação do banco
├── migrate_dump_to_sqlite.php # Migra dump MySQL para SQLite
├── mobuss_presenca.sql     # Dump MySQL de origem (opcional)
├── database.sqlite         # Banco SQLite usado pela aplicacao
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

### Adicionar Novos Funcionarios

Insira diretamente no banco de dados SQLite:

```sql
INSERT INTO funcionarios (nome) VALUES ('Nome do Funcionário');
```

### Alterar Caminho do Banco SQLite

Edite `config.php`:

```php
define('DB_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'database.sqlite');
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

```powershell
# Backup do arquivo SQLite
Copy-Item .\database.sqlite .\backup_database_20260309.sqlite

# Restaurar backup
Copy-Item .\backup_database_20260309.sqlite .\database.sqlite -Force
```

Se voce ainda estiver migrando de MySQL, mantenha tambem o dump `mobuss_presenca.sql` como backup historico.

## 🐛 Troubleshooting

### Erro de Conexao com Banco de Dados

- Verifique se o arquivo `database.sqlite` existe
- Confirme se a extensao `pdo_sqlite` esta habilitada no PHP
- Confirme o caminho `DB_FILE` em `config.php`

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
2. Mensagens de erro do PHP/SQLite
3. As permissões de arquivo/diretório

## 📄 Licença

Este sistema é fornecido como está, sem garantias.

## 🎨 Personalização

O sistema usa Bootstrap 5.3 e pode ser facilmente personalizado:

- **Cores**: Edite as variáveis CSS nos arquivos PHP
- **Tipografia**: Modifique as fontes no `<head>` dos arquivos
- **Layout**: Ajuste o CSS inline nos arquivos PHP

---

**Versao**: 1.1.0  
**Data**: 2026-03-09  
**Desenvolvido com**: PHP, SQLite, Bootstrap 5.3
