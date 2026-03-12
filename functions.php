<?php
/**
 * Funções Auxiliares
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'config.php';

/**
 * Garante que a tabela de funcionarios suporte os campos mais recentes.
 */
function garantirEstruturaFuncionarios() {
    global $conn;

    $colunas = $conn->query("PRAGMA table_info(funcionarios)")->fetchAll();
    $nomesColunas = array_column($colunas, 'name');

    if (!in_array('cpf', $nomesColunas, true)) {
        $conn->exec("ALTER TABLE funcionarios ADD COLUMN cpf TEXT");
    }

    if (!in_array('email', $nomesColunas, true)) {
        $conn->exec("ALTER TABLE funcionarios ADD COLUMN email TEXT");
    }

    if (!in_array('supervisor', $nomesColunas, true)) {
        $conn->exec("ALTER TABLE funcionarios ADD COLUMN supervisor TEXT");
    }

    if (!in_array('categoria', $nomesColunas, true)) {
        $conn->exec("ALTER TABLE funcionarios ADD COLUMN categoria TEXT NOT NULL DEFAULT 'servidor'");
    }

    $conn->exec("UPDATE funcionarios SET categoria = 'servidor' WHERE categoria IS NULL OR categoria = ''");
    $conn->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_funcionarios_cpf ON funcionarios(cpf)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_funcionarios_categoria_nome ON funcionarios(categoria, nome)");
}

garantirEstruturaFuncionarios();

/**
 * Garante estrutura de configuracoes do sistema.
 */
function garantirEstruturaConfiguracoes() {
    global $conn;

    $conn->exec("
        CREATE TABLE IF NOT EXISTS configuracoes (
            chave TEXT PRIMARY KEY,
            valor TEXT NOT NULL
        )
    ");

    $senhaAdminAtual = obterConfiguracao('admin_page_password');
    if ($senhaAdminAtual === null || $senhaAdminAtual === '') {
        definirSenhaConfiguracao('admin_page_password', 'senha26');
    }

    $senhaEdicaoAtual = obterConfiguracao('edit_access_password');
    if ($senhaEdicaoAtual === null || $senhaEdicaoAtual === '') {
        definirSenhaConfiguracao('edit_access_password', 'senha26');
    }
}

/**
 * Garante estrutura para dias sem necessidade de escala.
 */
function garantirEstruturaDiasNaoTrabalhados() {
    global $conn;

    $conn->exec("
        CREATE TABLE IF NOT EXISTS dias_nao_trabalhados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data TEXT NOT NULL UNIQUE,
            tipo TEXT NOT NULL DEFAULT 'feriado',
            descricao TEXT,
            criado_em TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $conn->exec("CREATE INDEX IF NOT EXISTS idx_dias_nao_trabalhados_data ON dias_nao_trabalhados(data)");
}

/**
 * Obter configuracao por chave.
 */
function obterConfiguracao($chave, $valorPadrao = null) {
    global $conn;

    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
    $stmt->execute([(string)$chave]);
    $row = $stmt->fetch();

    if (!$row) {
        return $valorPadrao;
    }

    return $row['valor'];
}

/**
 * Definir configuracao por chave.
 */
function definirConfiguracao($chave, $valor) {
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
        ON CONFLICT(chave) DO UPDATE SET valor = excluded.valor
    ");

    return $stmt->execute([(string)$chave, (string)$valor]);
}

/**
 * Definir senha de configuracao com hash.
 */
function definirSenhaConfiguracao($chave, $senhaEmTextoPlano) {
    $senhaEmTextoPlano = trim((string)$senhaEmTextoPlano);
    if ($senhaEmTextoPlano === '') {
        return false;
    }

    $hash = password_hash($senhaEmTextoPlano, PASSWORD_DEFAULT);
    return definirConfiguracao($chave, $hash);
}

/**
 * Verifica senha para uma chave de configuracao.
 */
function verificarSenhaConfiguracao($chave, $senhaEmTextoPlano) {
    $hash = obterConfiguracao($chave);
    if (!$hash) {
        return false;
    }

    return password_verify((string)$senhaEmTextoPlano, $hash);
}

garantirEstruturaConfiguracoes();
garantirEstruturaDiasNaoTrabalhados();

/**
 * Obter todos os funcionarios
 */
function obterFuncionarios() {
    global $conn;
    $sql = "SELECT id, nome, categoria FROM funcionarios
            ORDER BY CASE WHEN categoria = 'servidor' THEN 0 ELSE 1 END, nome ASC";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

/**
 * Obter funcionarios separados por categoria.
 */
function obterFuncionariosPorCategoria() {
    $funcionarios = obterFuncionarios();
    $resultado = [
        'servidores' => [],
        'estagiarios' => []
    ];

    foreach ($funcionarios as $funcionario) {
        if (($funcionario['categoria'] ?? 'servidor') === 'estagiario') {
            $resultado['estagiarios'][] = $funcionario;
            continue;
        }

        $resultado['servidores'][] = $funcionario;
    }

    return $resultado;
}

/**
 * Cadastra um novo funcionario.
 */
function cadastrarFuncionario($nomeCompleto, $cpf, $email, $supervisor, $categoria) {
    global $conn;

    $nomeCompleto = trim((string)$nomeCompleto);
    $cpfOriginal = trim((string)$cpf);
    $cpfNumerico = preg_replace('/\D+/', '', $cpfOriginal);
    $email = trim((string)$email);
    $supervisor = trim((string)$supervisor);
    $categoria = trim((string)$categoria);

    if ($nomeCompleto === '' || $cpfOriginal === '' || $email === '') {
        return ['sucesso' => false, 'mensagem' => 'Preencha nome completo, CPF e e-mail.', 'id' => null];
    }

    if ($supervisor === '') {
        $supervisor = 'Supervisor não informado no cadastro';
    }

    if (!in_array($categoria, ['servidor', 'estagiario'], true)) {
        return ['sucesso' => false, 'mensagem' => 'Categoria inválida para cadastro.', 'id' => null];
    }

    if (strlen($cpfNumerico) !== 11) {
        return ['sucesso' => false, 'mensagem' => 'Informe um CPF com 11 dígitos.', 'id' => null];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um e-mail válido.', 'id' => null];
    }

    try {
        $sql = "INSERT INTO funcionarios (nome, cpf, email, supervisor, categoria)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nomeCompleto, $cpfNumerico, $email, $supervisor, $categoria]);

        return ['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso.', 'id' => (int)$conn->lastInsertId()];
    } catch (Throwable $e) {
        $mensagem = stripos($e->getMessage(), 'idx_funcionarios_cpf') !== false
            ? 'Já existe cadastro com este CPF.'
            : 'Não foi possível concluir o cadastro.';

        return ['sucesso' => false, 'mensagem' => $mensagem, 'id' => null];
    }
}

/**
 * Obter pessoas cadastradas com dados completos.
 */
function obterPessoasCadastradas() {
    global $conn;

    $stmt = $conn->query("
        SELECT id, nome, cpf, email, supervisor, categoria
        FROM funcionarios
        ORDER BY CASE WHEN categoria = 'servidor' THEN 0 ELSE 1 END, nome
    ");

    return $stmt->fetchAll();
}

/**
 * Atualizar dados de pessoa cadastrada.
 */
function atualizarPessoaCadastrada($id, $nomeCompleto, $cpf, $email, $supervisor, $categoria) {
    global $conn;

    $id = (int)$id;
    $nomeCompleto = trim((string)$nomeCompleto);
    $cpfNumerico = preg_replace('/\D+/', '', trim((string)$cpf));
    $email = trim((string)$email);
    $supervisor = trim((string)$supervisor);
    $categoria = trim((string)$categoria);

    if ($id <= 0) {
        return ['sucesso' => false, 'mensagem' => 'Pessoa inválida.'];
    }
    if ($nomeCompleto === '' || $cpfNumerico === '' || $email === '' || $supervisor === '') {
        return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos.'];
    }
    if (strlen($cpfNumerico) !== 11) {
        return ['sucesso' => false, 'mensagem' => 'CPF deve conter 11 dígitos.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'E-mail inválido.'];
    }
    if (!in_array($categoria, ['servidor', 'estagiario'], true)) {
        return ['sucesso' => false, 'mensagem' => 'Categoria inválida.'];
    }

    try {
        $stmt = $conn->prepare("
            UPDATE funcionarios
            SET nome = ?, cpf = ?, email = ?, supervisor = ?, categoria = ?
            WHERE id = ?
        ");
        $stmt->execute([$nomeCompleto, $cpfNumerico, $email, $supervisor, $categoria, $id]);
        return ['sucesso' => true, 'mensagem' => 'Dados atualizados com sucesso.'];
    } catch (Throwable $e) {
        $mensagem = stripos($e->getMessage(), 'idx_funcionarios_cpf') !== false
            ? 'CPF já cadastrado para outra pessoa.'
            : 'Não foi possível atualizar os dados.';
        return ['sucesso' => false, 'mensagem' => $mensagem];
    }
}

/**
 * Listar dias sem necessidade de escala.
 */
function obterDiasNaoTrabalhados($ano = null, $mes = null) {
    global $conn;

    if ($ano !== null && $mes !== null) {
        $ano = (int)$ano;
        $mes = (int)$mes;
        $dataInicio = "$ano-" . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . "-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        $stmt = $conn->prepare("
            SELECT id, data, tipo, descricao
            FROM dias_nao_trabalhados
            WHERE data BETWEEN ? AND ?
            ORDER BY data ASC
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        return $stmt->fetchAll();
    }

    $stmt = $conn->query("
        SELECT id, data, tipo, descricao
        FROM dias_nao_trabalhados
        ORDER BY data ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Cadastrar ou atualizar um dia sem necessidade de escala.
 */
function salvarDiaNaoTrabalhado($data, $tipo, $descricao = '') {
    global $conn;

    $data = trim((string)$data);
    $tipo = trim((string)$tipo);
    $descricao = trim((string)$descricao);

    $dataObj = DateTime::createFromFormat('Y-m-d', $data);
    $dataValida = $dataObj && $dataObj->format('Y-m-d') === $data;
    if (!$dataValida) {
        return ['sucesso' => false, 'mensagem' => 'Informe uma data válida.'];
    }

    if (!in_array($tipo, ['feriado', 'nao_trabalhado'], true)) {
        return ['sucesso' => false, 'mensagem' => 'Tipo de dia inválido.'];
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO dias_nao_trabalhados (data, tipo, descricao)
            VALUES (?, ?, ?)
            ON CONFLICT(data) DO UPDATE SET
                tipo = excluded.tipo,
                descricao = excluded.descricao
        ");
        $stmt->execute([$data, $tipo, $descricao]);

        return ['sucesso' => true, 'mensagem' => 'Dia salvo com sucesso.'];
    } catch (Throwable $e) {
        return ['sucesso' => false, 'mensagem' => 'Não foi possível salvar o dia informado.'];
    }
}

/**
 * Excluir dia sem necessidade de escala.
 */
function excluirDiaNaoTrabalhado($id) {
    global $conn;

    $id = (int)$id;
    if ($id <= 0) {
        return ['sucesso' => false, 'mensagem' => 'Dia inválido para exclusão.'];
    }

    $stmt = $conn->prepare("DELETE FROM dias_nao_trabalhados WHERE id = ?");
    $ok = $stmt->execute([$id]);

    if (!$ok) {
        return ['sucesso' => false, 'mensagem' => 'Não foi possível excluir o dia informado.'];
    }

    return ['sucesso' => true, 'mensagem' => 'Dia removido com sucesso.'];
}

/**
 * Retorna mapa de dias sem necessidade de escala para consulta rapida.
 */
function obterMapaDiasNaoTrabalhados($ano, $mes) {
    $dias = obterDiasNaoTrabalhados((int)$ano, (int)$mes);
    $mapa = [];

    foreach ($dias as $dia) {
        if (!empty($dia['data'])) {
            $mapa[(string)$dia['data']] = true;
        }
    }

    return $mapa;
}

/**
 * Obter os 3 primeiros digitos do CPF de um funcionario.
 */
function obterPrefixoCpfFuncionario($funcionarioId) {
    global $conn;

    $stmt = $conn->prepare("SELECT cpf FROM funcionarios WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$funcionarioId]);
    $row = $stmt->fetch();

    if (!$row || empty($row['cpf'])) {
        return null;
    }

    $cpfNumerico = preg_replace('/\D+/', '', (string)$row['cpf']);
    if (strlen($cpfNumerico) < 3) {
        return null;
    }

    return substr($cpfNumerico, 0, 3);
}

/**
 * Obter regime de trabalho de um funcionário em um período
 */
function obterRegimeTrabalho($funcionario_id, $ano, $mes) {
    global $conn;
    $data_inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    
    $sql = "SELECT data, turno, status FROM registros_regime 
            WHERE funcionario_id = ? AND data BETWEEN ? AND ?
            ORDER BY data, turno";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$funcionario_id, $data_inicio, $data_fim]);
    
    $registros = [];
    while ($row = $stmt->fetch()) {
        $key = $row['data'] . '_' . $row['turno'];
        $registros[$key] = $row['status'];
    }
    
    return $registros;
}

/**
 * Salvar ou atualizar regime de trabalho
 */
function salvarRegimeTrabalho($funcionario_id, $data, $turno, $status) {
    global $conn;
    
    // Se status é null, deletar o registro
    if ($status === null || $status === '') {
        $sql = "DELETE FROM registros_regime WHERE funcionario_id = ? AND data = ? AND turno = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$funcionario_id, $data, $turno]);
    }
    
    // Inserir ou atualizar (upsert no SQLite)
    $sql = "INSERT INTO registros_regime (funcionario_id, data, turno, status) 
            VALUES (?, ?, ?, ?)
            ON CONFLICT(funcionario_id, data, turno) DO UPDATE SET status = excluded.status";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$funcionario_id, $data, $turno, $status]);
}

/**
 * Obter matriz de presença para um mês
 */
function obterMatrizPresenca($ano, $mes) {
    global $conn;
    
    $data_inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    
    $sql = "SELECT 
                f.id, f.nome,
                r.data, r.turno, r.status
            FROM funcionarios f
            LEFT JOIN registros_regime r ON f.id = r.funcionario_id 
                AND r.data BETWEEN ? AND ?
            ORDER BY f.nome, r.data, r.turno";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim]);
    
    return $stmt->fetchAll();
}

/**
 * Verificar se um dia tem alguém presencialmente
 */
function temPresencialNoTurno($ano, $mes, $dia, $turno) {
    global $conn;
    
    $data = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
    
    $sql = "SELECT COUNT(*) as total FROM registros_regime 
            WHERE data = ? AND turno = ? AND status = 'presencial'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data, $turno]);
    $row = $stmt->fetch();
    
    return $row['total'] > 0;
}

/**
 * Obter contagem de status por dia e turno
 */
function obterContagemStatusPorDia($ano, $mes, $dia, $turno) {
    global $conn;
    
    $data = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
    
    $sql = "SELECT status, COUNT(*) as total FROM registros_regime 
            WHERE data = ? AND turno = ?
            GROUP BY status";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data, $turno]);
    
    $contagem = [
        'presencial' => 0,
        'homeoffice' => 0,
        'férias' => 0,
        'afastamento' => 0,
        'nao_definido' => 0
    ];
    
    while ($row = $stmt->fetch()) {
        $contagem[$row['status']] = $row['total'];
    }
    
    return $contagem;
}

/**
 * Obter dias úteis do mês (segunda a sexta)
 */
function obterDiasUteis($ano, $mes) {
    $data_inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    $mapaDiasNaoTrabalhados = obterMapaDiasNaoTrabalhados($ano, $mes);
    
    $dias = [];
    $data = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    
    while ($data <= $fim) {
        $dataIso = $data->format('Y-m-d');
        $dia_semana = $data->format('N'); // 1 = segunda, 5 = sexta
        if ($dia_semana >= 1 && $dia_semana <= 5 && !isset($mapaDiasNaoTrabalhados[$dataIso])) {
            $dias[] = (int)$data->format('d');
        }
        $data->modify('+1 day');
    }
    
    return $dias;
}

/**
 * Formatar data para exibição
 */
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

/**
 * Obter nome do mês
 */
function obterNomeMes($mes) {
    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $meses[$mes] ?? 'Mês inválido';
}

/**
 * Obter cor do status
 */
function obterCorStatus($status) {
    $cores = [
        'presencial' => 'success',
        'homeoffice' => 'warning',
        'férias' => 'danger',
        'afastamento' => 'secondary'
    ];
    return $cores[$status] ?? 'light';
}

/**
 * Obter ícone do status
 */
function obterIconeStatus($status) {
    $icones = [
        'presencial' => '🏢',
        'homeoffice' => '🏠',
        'férias' => '🏖️',
        'afastamento' => '🚫'
    ];
    return $icones[$status] ?? '❌';
}
