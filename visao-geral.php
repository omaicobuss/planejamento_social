<?php
/**
 * Página de Visão Geral
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'functions.php';
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

// Obter mês e ano
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');

// Validar mês e ano
if ($mes < 1 || $mes > 12) $mes = date('m');
if ($ano < 2020 || $ano > 2030) $ano = date('Y');

// Obter dados
$funcionarios = obterFuncionarios();
$dados_matriz = obterMatrizPresenca($ano, $mes);
$dias_uteis = obterDiasUteis($ano, $mes);
$mapa_dias_nao_trabalhados = obterMapaDiasNaoTrabalhados($ano, $mes);
$dias_nao_trabalhados_mes = obterDiasNaoTrabalhados($ano, $mes);
$mapa_feriados = [];
foreach ($dias_nao_trabalhados_mes as $diaNaoTrabalhado) {
    if (($diaNaoTrabalhado['tipo'] ?? '') !== 'feriado' || empty($diaNaoTrabalhado['data'])) {
        continue;
    }

    $mapa_feriados[(string)$diaNaoTrabalhado['data']] = [
        'descricao' => trim((string)($diaNaoTrabalhado['descricao'] ?? ''))
    ];
}

// Organizar dados por funcionário e data
$matriz = [];
foreach ($funcionarios as $func) {
    $matriz[$func['id']] = [
        'nome' => $func['nome'],
        'dados' => []
    ];
}

foreach ($dados_matriz as $row) {
    if ($row['id'] && isset($matriz[$row['id']])) {
        if ($row['data']) {
            $key = $row['data'] . '_' . $row['turno'];
            $matriz[$row['id']]['dados'][$key] = $row['status'];
        }
    }
}

// Gerar dias do mês
$data_inicio = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));
$dias_mes = [];
$data = new DateTime($data_inicio);
$fim = new DateTime($data_fim);

while ($data <= $fim) {
    $dataIso = $data->format('Y-m-d');
    $ehFeriado = isset($mapa_feriados[$dataIso]);
    $dias_mes[] = [
        'dia' => (int)$data->format('d'),
        'data' => $dataIso,
        'dia_semana' => $data->format('N'),
        'eh_util' => $data->format('N') >= 1
            && $data->format('N') <= 5
            && !isset($mapa_dias_nao_trabalhados[$dataIso]),
        'eh_feriado' => $ehFeriado,
        'descricao_feriado' => $ehFeriado ? (string)$mapa_feriados[$dataIso]['descricao'] : ''
    ];
    $data->modify('+1 day');
}

$categoria_por_funcionario = [];
foreach ($funcionarios as $func) {
    $categoria_por_funcionario[(int)$func['id']] = (($func['categoria'] ?? 'servidor') === 'estagiario')
        ? 'estagiario'
        : 'servidor';
}

$resumo_presencial_calendario = [];
foreach ($dias_mes as $dia_info) {
    $resumo_presencial_calendario[$dia_info['data']] = [
        'manha' => ['servidor' => 0, 'estagiario' => 0, 'total' => 0],
        'tarde' => ['servidor' => 0, 'estagiario' => 0, 'total' => 0],
        'classe' => 'cc-dia--vermelho',
        'descricao' => 'Sem presencial em pelo menos um periodo'
    ];
}

foreach ($dados_matriz as $row) {
    $funcionarioId = isset($row['id']) ? (int)$row['id'] : 0;
    $dataReferencia = $row['data'] ?? '';
    $status = $row['status'] ?? null;

    if ($funcionarioId === 0 || $dataReferencia === '' || $status !== 'presencial') {
        continue;
    }

    if (!isset($resumo_presencial_calendario[$dataReferencia])) {
        continue;
    }

    $turnoOriginal = strtolower((string)($row['turno'] ?? ''));
    $turno = (strpos($turnoOriginal, 'manh') === 0) ? 'manha' : 'tarde';
    $categoria = $categoria_por_funcionario[$funcionarioId] ?? 'servidor';

    $resumo_presencial_calendario[$dataReferencia][$turno][$categoria]++;
    $resumo_presencial_calendario[$dataReferencia][$turno]['total']++;
}

foreach ($dias_mes as $dia_info) {
    $dataReferencia = $dia_info['data'];
    $resumoDia = $resumo_presencial_calendario[$dataReferencia];

    if (empty($dia_info['eh_util'])) {
        $resumo_presencial_calendario[$dataReferencia]['classe'] = 'cc-dia--neutro';
        $resumo_presencial_calendario[$dataReferencia]['descricao'] = 'Fim de semana (sem escala)';
        continue;
    }

    $manhaTotal = $resumoDia['manha']['total'];
    $tardeTotal = $resumoDia['tarde']['total'];
    $temServidor = ($resumoDia['manha']['servidor'] + $resumoDia['tarde']['servidor']) > 0;
    $temEstagiario = ($resumoDia['manha']['estagiario'] + $resumoDia['tarde']['estagiario']) > 0;

    if ($manhaTotal === 0 || $tardeTotal === 0) {
        $resumo_presencial_calendario[$dataReferencia]['classe'] = 'cc-dia--vermelho';
        $resumo_presencial_calendario[$dataReferencia]['descricao'] = 'Sem presencial em pelo menos um periodo';
    } elseif (!$temServidor && $temEstagiario) {
        $resumo_presencial_calendario[$dataReferencia]['classe'] = 'cc-dia--amarelo';
        $resumo_presencial_calendario[$dataReferencia]['descricao'] = 'Apenas estagiarios presenciais';
    } elseif ($temServidor && $temEstagiario) {
        $resumo_presencial_calendario[$dataReferencia]['classe'] = 'cc-dia--verde';
        $resumo_presencial_calendario[$dataReferencia]['descricao'] = 'Servidores e estagiarios presenciais';
    } else {
        $resumo_presencial_calendario[$dataReferencia]['classe'] = 'cc-dia--neutro';
        $resumo_presencial_calendario[$dataReferencia]['descricao'] = 'Somente servidores presenciais';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visão Geral - Regime de Trabalho</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #222;
        }

        .pg-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .pg-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 16px 24px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.35);
        }

        .pg-header-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
        }

        .pg-header-subtitle {
            margin: 4px 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .pg-container {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 16px 32px;
            width: 100%;
        }

        .secao {
            margin-bottom: 24px;
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
        }

        .secao-titulo {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 8px;
            color: #111827;
        }

        .secao-descricao {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0 0 12px;
        }

        .navegacao-mes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            gap: 10px;
        }

        .btn-nav {
            border: 1px solid #2563eb;
            color: #2563eb;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 120px;
            text-align: center;
        }

        .btn-nav:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .mes-ano {
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            color: #111827;
            min-width: 200px;
        }

        .cc-box {
            display: flex;
            justify-content: center;
        }

        .cc-wrap {
            width: 100%;
            max-width: 33.333%;
        }

        .cc-legenda {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .cc-legenda-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: #374151;
        }

        .cc-legenda-cor {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            border: 1px solid rgba(17, 24, 39, 0.15);
        }

        .cc-calendario {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .cc-semana {
            font-size: 0.72rem;
            font-weight: 700;
            text-align: center;
            color: #374151;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .cc-dia {
            min-height: 78px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            font: inherit;
            color: inherit;
            text-align: center;
            appearance: none;
            -webkit-appearance: none;
        }

        .cc-dia:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
        }

        .cc-dia--vazio {
            border: none;
            background: transparent;
            min-height: 0;
            padding: 0;
            cursor: default;
            box-shadow: none;
            transform: none;
        }

        .cc-dia--vazio:hover {
            box-shadow: none;
            transform: none;
        }

        .cc-dia--neutro {
            background-color: #ffffff;
            border-color: #d1d5db;
        }

        .cc-dia--vermelho {
            background-color: #fee2e2;
            border-color: #ef4444;
        }

        .cc-dia--amarelo {
            background-color: #fef9c3;
            border-color: #f59e0b;
        }

        .cc-dia--verde {
            background-color: #dcfce7;
            border-color: #22c55e;
        }

        .cc-dia--feriado {
            background-image:
                linear-gradient(
                    45deg,
                    rgba(15, 23, 42, 0.14) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(15, 23, 42, 0.14) 50%,
                    rgba(15, 23, 42, 0.14) 75%,
                    transparent 75%,
                    transparent
                );
            background-size: 12px 12px;
        }

        .cc-legenda-cor.cc-dia--feriado {
            background-color: #ffffff;
        }

        .cc-dia-numero {
            font-size: 0.85rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }

        .cc-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.45);
            z-index: 1200;
        }

        .cc-modal.aberto {
            display: flex;
        }

        .cc-modal-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.28);
            overflow: hidden;
        }

        .cc-modal-header {
            padding: 12px 14px;
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .cc-modal-titulo {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e3a8a;
        }

        .cc-modal-fechar {
            border: none;
            background: transparent;
            color: #1e3a8a;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        .cc-modal-corpo {
            padding: 14px;
        }

        .cc-modal-bloco {
            margin-bottom: 12px;
        }

        .cc-modal-bloco:last-child {
            margin-bottom: 0;
        }

        .cc-modal-periodo {
            margin: 0 0 6px;
            font-size: 0.88rem;
            color: #111827;
            font-weight: 700;
        }

        .cc-modal-linha {
            display: flex;
            justify-content: space-between;
            font-size: 0.84rem;
            color: #1f2937;
            padding: 2px 0;
        }

        .cc-modal-alerta {
            display: none;
            margin: 0 0 12px;
            padding: 8px 10px;
            border-radius: 6px;
            border-left: 4px solid #d97706;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.82rem;
        }

        .cc-modal-alerta.visivel {
            display: block;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card-metrica {
            background: #fff;
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .card-metrica-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .card-metrica-valor {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-metrica-detalhe {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .tabela-scroll {
            overflow-x: auto;
        }

        table.tabela-limpa {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        table.tabela-limpa thead {
            background: #f9fafb;
        }

        table.tabela-limpa th,
        table.tabela-limpa td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        table.tabela-limpa th {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        table.tabela-limpa th:last-child,
        table.tabela-limpa td:last-child {
            border-right: none;
        }

        .col-funcionario {
            text-align: left;
            font-weight: 500;
            background: #f9fafb;
            min-width: 180px;
            position: sticky;
            left: 0;
        }

        .status-cell {
            padding: 4px 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            margin: 1px;
        }

        .status-presencial {
            background: #d4edda;
            color: #155724;
        }

        .status-homeoffice {
            background: #fff3cd;
            color: #856404;
        }

        .status-ferias {
            background: #f8d7da;
            color: #721c24;
        }

        .status-férias {
            background: #f8d7da;
            color: #721c24;
        }

        .status-afastamento {
            background: #e2e3e5;
            color: #383d41;
        }

        .dia-sem-presencial {
            background: #ffe6e6 !important;
            font-weight: 600;
            color: #d32f2f;
        }

        .dia-header {
            font-size: 11px;
        }

        .dia-numero {
            font-weight: 700;
        }

        .dia-semana {
            font-size: 10px;
            color: #666;
        }

        .legenda {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 0;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .legenda-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .alerta-presenca {
            padding: 15px;
            margin-bottom: 0;
            border-radius: 6px;
            border-left: 4px solid #d32f2f;
            background: #ffe6e6;
        }
        .alerta-presenca h5 {
            color: #d32f2f;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        .alerta-presenca ul {
            margin: 0;
            padding-left: 20px;
        }
        .alerta-presenca li {
            color: #d32f2f;
            margin: 5px 0;
        }

        .resumo-dia {
            background: #f9f9f9;
            padding: 6px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 10px;
        }

        .resumo-dia span {
            display: inline-block;
            margin-right: 8px;
        }

        .presencial-chip {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            background: #ecfdf3;
            color: #15803d;
            margin: 1px;
        }

        .sem-presencial {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 600;
        }

        .accordion-matriz {
            margin-top: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .accordion-matriz summary {
            cursor: pointer;
            padding: 12px 14px;
            font-weight: 600;
            color: #1f2937;
            list-style: none;
        }

        .accordion-matriz summary::-webkit-details-marker {
            display: none;
        }

        .accordion-matriz summary::after {
            content: '▾';
            float: right;
            color: #6b7280;
        }

        .accordion-matriz[open] summary::after {
            content: '▴';
        }

        .accordion-matriz-content {
            padding: 0 14px 14px;
            border-top: 1px solid #e5e7eb;
        }

        .floating-link {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
        }

        .btn-primario {
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primario:hover {
            background-color: #1d4ed8;
            color: #fff;
        }

        @media (max-width: 768px) {
            .pg-header {
                padding: 12px 16px;
            }

            .pg-container {
                margin-top: 16px;
                padding: 0 12px 24px;
            }

            .secao {
                padding: 12px;
            }

            .navegacao-mes {
                flex-wrap: wrap;
            }

            .btn-nav {
                min-width: unset;
                flex: 1;
            }

            .cc-wrap {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="pg-wrapper">
        <header class="pg-header">
            <h1 class="pg-header-title">Visão Geral de Presença</h1>
            <p class="pg-header-subtitle">Consolidado mensal de servidores e estagiarios.</p>
        </header>

        <main class="pg-container">
            <section class="secao">
                <div class="navegacao-mes">
                    <a href="?ano=<?php echo $mes == 1 ? $ano - 1 : $ano; ?>&mes=<?php echo $mes == 1 ? 12 : $mes - 1; ?>"
                       class="btn-nav">
                        ← Anterior
                    </a>
                    <div class="mes-ano">
                        <?php echo obterNomeMes((int)$mes) . ' ' . $ano; ?>
                    </div>
                    <a href="?ano=<?php echo $mes == 12 ? $ano + 1 : $ano; ?>&mes=<?php echo $mes == 12 ? 1 : $mes + 1; ?>"
                       class="btn-nav">
                        Próximo →
                    </a>
                </div>
            </section>

            <section class="cards-grid">
                <article class="card-metrica">
                    <div class="card-metrica-label">Pessoas</div>
                    <div class="card-metrica-valor"><?php echo count($funcionarios); ?></div>
                    <div class="card-metrica-detalhe">Cadastrados no sistema.</div>
                </article>
                <article class="card-metrica">
                    <div class="card-metrica-label">Dias úteis no mês</div>
                    <div class="card-metrica-valor"><?php echo count($dias_uteis); ?></div>
                    <div class="card-metrica-detalhe">Considerando segunda a sexta.</div>
                </article>
                <article class="card-metrica">
                    <div class="card-metrica-label">Períodos com alerta</div>
                    <div class="card-metrica-valor">
                        <?php
                        $dias_alerta = [];
                        foreach ($dias_mes as $dia_info) {
                            if ($dia_info['eh_util']) {
                                if (!temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'manhã')) {
                                    $dias_alerta[] = [
                                        'dia' => $dia_info['dia'],
                                        'turno' => 'Manhã',
                                        'data' => $dia_info['data']
                                    ];
                                }
                                if (!temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'tarde')) {
                                    $dias_alerta[] = [
                                        'dia' => $dia_info['dia'],
                                        'turno' => 'Tarde',
                                        'data' => $dia_info['data']
                                    ];
                                }
                            }
                        }
                        echo count($dias_alerta);
                        ?>
                    </div>
                    <div class="card-metrica-detalhe">Manhã ou tarde sem presencial.</div>
                </article>
            </section>

            <section class="secao">
                <h2 class="secao-titulo">Calendario consolidado de presença</h2>
                <p class="secao-descricao">Resumo mensal de servidores e estagiários por dia, com classificação por cobertura dos períodos.</p>

                <div class="cc-box">
                    <div class="cc-wrap">
                        <div class="cc-legenda">
                            <div class="cc-legenda-item">
                                <span class="cc-legenda-cor cc-dia--vermelho"></span>
                                <span>Algum periodo sem servidor em presencial</span>
                            </div>
                            <div class="cc-legenda-item">
                                <span class="cc-legenda-cor cc-dia--amarelo"></span>
                                <span>Apenas estagiários presenciais</span>
                            </div>
                            <div class="cc-legenda-item">
                                <span class="cc-legenda-cor cc-dia--verde"></span>
                                <span>Servidores e estagiários presenciais</span>
                            </div>
                            <div class="cc-legenda-item">
                                <span class="cc-legenda-cor cc-dia--neutro"></span>
                                <span>Fim de semana (sem escala)</span>
                            </div>
                            <div class="cc-legenda-item">
                                <span class="cc-legenda-cor cc-dia--feriado"></span>
                                <span>Feriado (padrao quadriculado)</span>
                            </div>
                        </div>

                        <div class="cc-calendario">
                            <div class="cc-semana">Dom</div>
                            <div class="cc-semana">Seg</div>
                            <div class="cc-semana">Ter</div>
                            <div class="cc-semana">Qua</div>
                            <div class="cc-semana">Qui</div>
                            <div class="cc-semana">Sex</div>
                            <div class="cc-semana">Sab</div>

                            <?php
                            $dia_semana_inicio = (int)(new DateTime($data_inicio))->format('w');
                            for ($i = 0; $i < $dia_semana_inicio; $i++) {
                                echo '<div class="cc-dia cc-dia--vazio"></div>';
                            }

                            foreach ($dias_mes as $dia_info):
                                $resumoDia = $resumo_presencial_calendario[$dia_info['data']];
                                $classeDia = $resumoDia['classe'];
                                $tituloDia = $resumoDia['descricao'];
                                $classeFeriado = !empty($dia_info['eh_feriado']) ? ' cc-dia--feriado' : '';
                            ?>
                                <button
                                    type="button"
                                    class="cc-dia <?php echo $classeDia . $classeFeriado; ?>"
                                    title="<?php echo htmlspecialchars($tituloDia, ENT_QUOTES); ?>"
                                    data-data="<?php echo htmlspecialchars($dia_info['data'], ENT_QUOTES); ?>"
                                    data-manha-servidor="<?php echo (int)$resumoDia['manha']['servidor']; ?>"
                                    data-manha-estagiario="<?php echo (int)$resumoDia['manha']['estagiario']; ?>"
                                    data-tarde-servidor="<?php echo (int)$resumoDia['tarde']['servidor']; ?>"
                                    data-tarde-estagiario="<?php echo (int)$resumoDia['tarde']['estagiario']; ?>"
                                    data-feriado="<?php echo !empty($dia_info['eh_feriado']) ? '1' : '0'; ?>"
                                    data-feriado-descricao="<?php echo htmlspecialchars((string)($dia_info['descricao_feriado'] ?? ''), ENT_QUOTES); ?>"
                                    onclick="abrirModalCalendario(this)"
                                >
                                    <div class="cc-dia-numero"><?php echo $dia_info['dia']; ?></div>
                                </button>
                            <?php endforeach; ?>

                            <?php
                            $total_celulas = $dia_semana_inicio + count($dias_mes);
                            $faltantes = (7 - ($total_celulas % 7)) % 7;
                            for ($i = 0; $i < $faltantes; $i++) {
                                echo '<div class="cc-dia cc-dia--vazio"></div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </section>

        <!-- Alertas de Dias sem Presencial -->
            <?php if (!empty($dias_alerta)): ?>
                <section class="secao">
                    <h2 class="secao-titulo">Atenção: períodos sem presencial</h2>
                    <div class="alerta-presenca">
                        <p>Os seguintes períodos não possuem ninguém registrado como presencial:</p>
                        <ul>
                            <?php foreach ($dias_alerta as $alerta): ?>
                                <li>
                                    <strong><?php echo $alerta['dia']; ?> de <?php echo obterNomeMes((int)$mes); ?></strong>
                                    (<?php echo $alerta['turno']; ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <section class="secao">
                <h2 class="secao-titulo">Matriz do Presencial</h2>
                <p class="secao-descricao">Exibe, por data específica, quais servidores planejaram estar fisicamente no setor (presencial), separados por manhã e tarde.</p>
                <div class="tabela-scroll">
                    <table class="tabela-limpa">
                <thead>
                    <tr>
                        <th style="width: 180px;">Servidor</th>
                        <?php foreach ($dias_mes as $dia_info): ?>
                            <th style="width: 60px;">
                                <div class="dia-header">
                                    <div class="dia-numero"><?php echo $dia_info['dia']; ?></div>
                                    <div class="dia-semana">
                                        <?php 
                                        $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
                                        echo $dias_semana[$dia_info['dia_semana'] % 7];
                                        ?>
                                    </div>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $func): ?>
                        <tr>
                            <td class="col-funcionario">
                                <?php echo htmlspecialchars($func['nome']); ?>
                            </td>
                            <?php foreach ($dias_mes as $dia_info): ?>
                                <?php
                                $data = $dia_info['data'];
                                $status_manha = $matriz[$func['id']]['dados'][$data . '_manhã'] ?? null;
                                $status_tarde = $matriz[$func['id']]['dados'][$data . '_tarde'] ?? null;

                                $presencial_manha = $status_manha === 'presencial';
                                $presencial_tarde = $status_tarde === 'presencial';
                                $tem_presencial = $presencial_manha || $presencial_tarde;

                                $eh_dia_sem_presencial = $dia_info['eh_util'] &&
                                    !temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'manhã') &&
                                    !temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'tarde');

                                $classe_alerta = $eh_dia_sem_presencial ? 'dia-sem-presencial' : '';
                                ?>
                                <td class="status-cell <?php echo $classe_alerta; ?>">
                                    <?php if ($tem_presencial): ?>
                                        <div>
                                            <?php if ($presencial_manha): ?>
                                                <span class="presencial-chip">M</span>
                                            <?php endif; ?>
                                            <?php if ($presencial_tarde): ?>
                                                <span class="presencial-chip">T</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="sem-presencial">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </div>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <div class="cc-modal" id="ccModalDia" aria-hidden="true">
        <div class="cc-modal-card" role="dialog" aria-modal="true" aria-labelledby="ccModalTitulo">
            <div class="cc-modal-header">
                <h3 class="cc-modal-titulo" id="ccModalTitulo">Detalhes do dia</h3>
                <button type="button" class="cc-modal-fechar" onclick="fecharModalCalendario()" aria-label="Fechar">&times;</button>
            </div>
            <div class="cc-modal-corpo">
                <div class="cc-modal-alerta" id="ccModalAlertaFeriado"></div>
                <div class="cc-modal-bloco">
                    <p class="cc-modal-periodo">Manh&atilde;</p>
                    <div class="cc-modal-linha">
                        <span>Servidores</span>
                        <strong id="ccModalManhaServidor">0</strong>
                    </div>
                    <div class="cc-modal-linha">
                        <span>Estagiarios</span>
                        <strong id="ccModalManhaEstagiario">0</strong>
                    </div>
                </div>
                <div class="cc-modal-bloco">
                    <p class="cc-modal-periodo">Tarde</p>
                    <div class="cc-modal-linha">
                        <span>Servidores</span>
                        <strong id="ccModalTardeServidor">0</strong>
                    </div>
                    <div class="cc-modal-linha">
                        <span>Estagiarios</span>
                        <strong id="ccModalTardeEstagiario">0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="floating-link">
        <a href="index.php" class="btn-primario">← Voltar</a>
    </div>

    <script>
        function formatarDataModal(dataIso) {
            const partes = dataIso.split('-');
            if (partes.length !== 3) return dataIso;

            const ano = partes[0];
            const mes = parseInt(partes[1], 10);
            const dia = parseInt(partes[2], 10);
            const meses = [
                'janeiro', 'fevereiro', 'marco', 'abril', 'maio', 'junho',
                'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
            ];

            return `${dia} de ${meses[mes - 1]} de ${ano}`;
        }

        function abrirModalCalendario(el) {
            const modal = document.getElementById('ccModalDia');
            const alertaFeriado = document.getElementById('ccModalAlertaFeriado');
            document.getElementById('ccModalTitulo').textContent = `Detalhes de ${formatarDataModal(el.dataset.data || '')}`;
            document.getElementById('ccModalManhaServidor').textContent = el.dataset.manhaServidor || '0';
            document.getElementById('ccModalManhaEstagiario').textContent = el.dataset.manhaEstagiario || '0';
            document.getElementById('ccModalTardeServidor').textContent = el.dataset.tardeServidor || '0';
            document.getElementById('ccModalTardeEstagiario').textContent = el.dataset.tardeEstagiario || '0';

            if ((el.dataset.feriado || '0') === '1') {
                const descricaoFeriado = (el.dataset.feriadoDescricao || '').trim();
                alertaFeriado.textContent = descricaoFeriado !== ''
                    ? `Dia cadastrado como feriado: ${descricaoFeriado}.`
                    : 'Dia cadastrado como feriado.';
                alertaFeriado.classList.add('visivel');
            } else {
                alertaFeriado.textContent = '';
                alertaFeriado.classList.remove('visivel');
            }

            modal.classList.add('aberto');
            modal.setAttribute('aria-hidden', 'false');
        }

        function fecharModalCalendario() {
            const modal = document.getElementById('ccModalDia');
            modal.classList.remove('aberto');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('click', function(event) {
            const modal = document.getElementById('ccModalDia');
            if (event.target === modal) {
                fecharModalCalendario();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                fecharModalCalendario();
            }
        });
    </script>
</body>
</html>

