<?php
/**
 * Funções Auxiliares
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'config.php';

/**
 * Obter todos os funcionários
 */
function obterFuncionarios() {
    global $conn;
    $sql = "SELECT id, nome FROM funcionarios ORDER BY nome ASC";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
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
    
    $dias = [];
    $data = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    
    while ($data <= $fim) {
        $dia_semana = $data->format('N'); // 1 = segunda, 5 = sexta
        if ($dia_semana >= 1 && $dia_semana <= 5) {
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
