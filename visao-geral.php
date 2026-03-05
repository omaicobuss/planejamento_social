<?php
/**
 * Página de Visão Geral
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'functions.php';

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
    $dias_mes[] = [
        'dia' => (int)$data->format('d'),
        'data' => $data->format('Y-m-d'),
        'dia_semana' => $data->format('N'),
        'eh_util' => $data->format('N') >= 1 && $data->format('N') <= 5
    ];
    $data->modify('+1 day');
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
        }
    </style>
</head>
<body>
    <div class="pg-wrapper">
        <header class="pg-header">
            <h1 class="pg-header-title">Visão Geral de Presença</h1>
            <p class="pg-header-subtitle">Matriz mensal de todos os servidores.</p>
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
                    <div class="card-metrica-label">Servidores</div>
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
                <h2 class="secao-titulo">Legenda de status</h2>
                <div class="legenda">
                    <div class="legenda-item">
                        <span class="legenda-badge status-presencial">🏢</span>
                        <span>Presencial</span>
                    </div>
                    <div class="legenda-item">
                        <span class="legenda-badge status-homeoffice">🏠</span>
                        <span>Home Office</span>
                    </div>
                    <div class="legenda-item">
                        <span class="legenda-badge status-ferias">🏖️</span>
                        <span>Férias</span>
                    </div>
                    <div class="legenda-item">
                        <span class="legenda-badge status-afastamento">🚫</span>
                        <span>Afastamento</span>
                    </div>
                    <div class="legenda-item">
                        <span style="background: #ffe6e6; padding: 4px 8px; border-radius: 3px; border-left: 3px solid #d32f2f;"></span>
                        <span>Sem presencial no dia</span>
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

            <section class="secao">
                <details class="accordion-matriz">
                    <summary>Matriz Consolidadora de Escala</summary>
                    <div class="accordion-matriz-content">
                        <h3 class="secao-titulo" style="margin-top: 12px;">Matriz Consolidadora</h3>
                        <p class="secao-descricao">Visualização diária por servidor com todos os status registrados.</p>
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

                                                $eh_dia_sem_presencial = $dia_info['eh_util'] &&
                                                    !temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'manhã') &&
                                                    !temPresencialNoTurno($ano, $mes, $dia_info['dia'], 'tarde');

                                                $classe_alerta = $eh_dia_sem_presencial ? 'dia-sem-presencial' : '';
                                                ?>
                                                <td class="status-cell <?php echo $classe_alerta; ?>">
                                                    <?php if ($status_manha || $status_tarde): ?>
                                                        <div>
                                                            <?php if ($status_manha): ?>
                                                                <span class="status-badge status-<?php echo $status_manha; ?>">
                                                                    <?php echo obterIconeStatus($status_manha); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($status_tarde): ?>
                                                                <span class="status-badge status-<?php echo $status_tarde; ?>">
                                                                    <?php echo obterIconeStatus($status_tarde); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php
                                                        $contagem = obterContagemStatusPorDia($ano, $mes, $dia_info['dia'], 'manhã');
                                                        $contagem_tarde = obterContagemStatusPorDia($ano, $mes, $dia_info['dia'], 'tarde');
                                                        ?>
                                                        <div class="resumo-dia">
                                                            <span>Manhã: <?php echo $contagem['presencial']; ?> P</span>
                                                            <span>Tarde: <?php echo $contagem_tarde['presencial']; ?> P</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </section>
        </main>
    </div>

    <div class="floating-link">
        <a href="index.php" class="btn-primario">← Voltar</a>
    </div>
</body>
</html>
