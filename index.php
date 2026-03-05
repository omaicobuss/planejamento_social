<?php
/**
 * Página de Registro de Regime de Trabalho
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'functions.php';

// Obter funcionários
$funcionarios = obterFuncionarios();

// Obter funcionário selecionado
$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : (count($funcionarios) > 0 ? $funcionarios[0]['id'] : null);

// Obter mês e ano
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');

// Validar mês e ano
if ($mes < 1 || $mes > 12) $mes = date('m');
if ($ano < 2020 || $ano > 2030) $ano = date('Y');

// Processar salvamento
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $func_id = (int)$_POST['funcionario_id'];
    $data = $_POST['data'];
    
    // Salvar manhã
    $status_manha = isset($_POST['status_manha']) && $_POST['status_manha'] !== '' ? $_POST['status_manha'] : null;
    if (salvarRegimeTrabalho($func_id, $data, 'manhã', $status_manha)) {
        // Sucesso
    } else {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        ✗ Erro ao salvar registro da manhã!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    }
    
    // Salvar tarde
    $status_tarde = isset($_POST['status_tarde']) && $_POST['status_tarde'] !== '' ? $_POST['status_tarde'] : null;
    if (salvarRegimeTrabalho($func_id, $data, 'tarde', $status_tarde)) {
        if (!$mensagem) {
            $mensagem = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            ✓ Registro salvo com sucesso!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
        }
    } else {
        if (!$mensagem) {
            $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            ✗ Erro ao salvar registro da tarde!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
        }
    }
}

// Obter regime do funcionário
$regime = $funcionario_id ? obterRegimeTrabalho($funcionario_id, $ano, $mes) : [];

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
        'dia_semana' => $data->format('N')
    ];
    $data->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Regime de Trabalho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            max-width: 1200px;
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

        .layout-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 16px;
        }

        .lista-funcionarios {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .func-item {
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #111827;
            background: white;
            transition: all 0.2s;
        }

        .func-item:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #111827;
        }

        .func-item.active {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }

        .navegacao-mes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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

        .calendario {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }

        .dia-semana {
            text-align: center;
            font-weight: 700;
            color: #2563eb;
            padding: 8px;
            border-bottom: 2px solid #2563eb;
            font-size: 0.82rem;
        }

        .dia {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            min-height: 88px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .dia:hover {
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.14);
            border-color: #2563eb;
        }

        .dia.outro-mes {
            background: #f9fafb;
            color: #9ca3af;
        }

        .dia-numero {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .dia-status {
            font-size: 11px;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
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

        .mensagem-box {
            margin-bottom: 16px;
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }

        .modal-header-custom .btn-close {
            filter: brightness(0) invert(1);
        }

        .floating-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primario:hover {
            background-color: #1d4ed8;
            color: #fff;
        }

        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
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

            .calendario {
                gap: 6px;
            }

            .dia {
                min-height: 72px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="pg-wrapper">
        <header class="pg-header">
            <h1 class="pg-header-title">Registro de Regime de Trabalho</h1>
            <p class="pg-header-subtitle">Selecione um servidor e registre o regime de cada dia.</p>
        </header>

        <main class="pg-container">
            <?php if ($mensagem): ?>
                <div class="mensagem-box"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <div class="layout-grid">
                <section class="secao">
                    <h2 class="secao-titulo">Servidores</h2>
                    <p class="secao-descricao">Selecione quem terá o calendário exibido.</p>
                    <div class="lista-funcionarios">
                        <?php foreach ($funcionarios as $func): ?>
                            <a href="?funcionario_id=<?php echo $func['id']; ?>&ano=<?php echo $ano; ?>&mes=<?php echo $mes; ?>"
                               class="func-item <?php echo $func['id'] == $funcionario_id ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($func['nome']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="secao">
                    <?php if ($funcionario_id): ?>
                        <div class="navegacao-mes">
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes == 1 ? $ano - 1 : $ano; ?>&mes=<?php echo $mes == 1 ? 12 : $mes - 1; ?>"
                               class="btn-nav">
                                ← Anterior
                            </a>
                            <div class="mes-ano">
                                <?php echo obterNomeMes($mes) . ' ' . $ano; ?>
                            </div>
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes == 12 ? $ano + 1 : $ano; ?>&mes=<?php echo $mes == 12 ? 1 : $mes + 1; ?>"
                               class="btn-nav">
                                Próximo →
                            </a>
                        </div>

                        <div class="calendario">
                            <div class="dia-semana">Dom</div>
                            <div class="dia-semana">Seg</div>
                            <div class="dia-semana">Ter</div>
                            <div class="dia-semana">Qua</div>
                            <div class="dia-semana">Qui</div>
                            <div class="dia-semana">Sex</div>
                            <div class="dia-semana">Sab</div>

                            <?php 
                            $primeiro_dia = new DateTime($data_inicio);
                            $dia_semana_inicio = (int)$primeiro_dia->format('N');
                            if ($dia_semana_inicio != 7) {
                                for ($i = 0; $i < $dia_semana_inicio - 1; $i++) {
                                    echo '<div class="dia outro-mes"></div>';
                                }
                            }

                            foreach ($dias_mes as $dia_info):
                                $data = $dia_info['data'];
                                $dia = $dia_info['dia'];
                                $status_manha = $regime[$data . '_manhã'] ?? null;
                                $status_tarde = $regime[$data . '_tarde'] ?? null;
                                $eh_fim_semana = $dia_info['dia_semana'] > 5;
                            ?>
                                <div class="dia <?php echo $eh_fim_semana ? 'outro-mes' : ''; ?>"
                                     data-bs-toggle="modal" data-bs-target="#modalDia"
                                     onclick="abrirModalDia('<?php echo $data; ?>', <?php echo $funcionario_id; ?>, '<?php echo $status_manha ?? ''; ?>', '<?php echo $status_tarde ?? ''; ?>')">
                                    <div class="dia-numero"><?php echo $dia; ?></div>
                                    <div class="dia-status">
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
                                </div>
                            <?php endforeach; ?>

                            <?php
                            $total_dias = count($dias_mes) + ($dia_semana_inicio == 7 ? 0 : $dia_semana_inicio - 1);
                            $dias_faltantes = (42 - $total_dias) % 7;
                            for ($i = 0; $i < $dias_faltantes; $i++) {
                                echo '<div class="dia outro-mes"></div>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <span class="status-badge status-afastamento">Nenhum servidor cadastrado.</span>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal para editar dia -->
    <div class="modal fade" id="modalDia" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title">Registrar Regime de Trabalho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="funcionario_id" id="modalFuncionarioId" value="">
                        <input type="hidden" name="data" id="modalData" value="">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Data: <span id="modalDataExibicao"></span></strong></label>
                        </div>

                        <!-- Turno Manhã -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Manhã</strong></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_manha" value="" id="manha_nao_definido" checked>
                                <label class="form-check-label" for="manha_nao_definido">Não definido</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_manha" value="presencial" id="manha_presencial">
                                <label class="form-check-label" for="manha_presencial">🏢 Presencial</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_manha" value="homeoffice" id="manha_homeoffice">
                                <label class="form-check-label" for="manha_homeoffice">🏠 Home Office</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_manha" value="férias" id="manha_ferias">
                                <label class="form-check-label" for="manha_ferias">🏖️ Férias</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_manha" value="afastamento" id="manha_afastamento">
                                <label class="form-check-label" for="manha_afastamento">🚫 Afastamento</label>
                            </div>
                        </div>

                        <hr>

                        <!-- Turno Tarde -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Tarde</strong></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_tarde" value="" id="tarde_nao_definido" checked>
                                <label class="form-check-label" for="tarde_nao_definido">Não definido</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_tarde" value="presencial" id="tarde_presencial">
                                <label class="form-check-label" for="tarde_presencial">🏢 Presencial</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_tarde" value="homeoffice" id="tarde_homeoffice">
                                <label class="form-check-label" for="tarde_homeoffice">🏠 Home Office</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_tarde" value="férias" id="tarde_ferias">
                                <label class="form-check-label" for="tarde_ferias">🏖️ Férias</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_tarde" value="afastamento" id="tarde_afastamento">
                                <label class="form-check-label" for="tarde_afastamento">🚫 Afastamento</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="salvar" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="floating-link">
        <a href="visao-geral.php" class="btn-primario">Ver visão geral</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirModalDia(data, funcionarioId, statusManha, statusTarde) {
            const dataObj = new Date(data + 'T00:00:00');
            const dataFormatada = dataObj.toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('modalData').value = data;
            document.getElementById('modalFuncionarioId').value = funcionarioId;
            document.getElementById('modalDataExibicao').textContent = dataFormatada;
            
            // Resetar formulário
            document.querySelectorAll('input[name="status_manha"]').forEach(el => el.checked = false);
            document.querySelectorAll('input[name="status_tarde"]').forEach(el => el.checked = false);
            
            // Carregar valores salvos
            if (statusManha) {
                const manhaRadio = document.querySelector(`input[name="status_manha"][value="${statusManha}"]`);
                if (manhaRadio) manhaRadio.checked = true;
            } else {
                document.getElementById('manha_nao_definido').checked = true;
            }
            
            if (statusTarde) {
                const tardeRadio = document.querySelector(`input[name="status_tarde"][value="${statusTarde}"]`);
                if (tardeRadio) tardeRadio.checked = true;
            } else {
                document.getElementById('tarde_nao_definido').checked = true;
            }
        }
    </script>
</body>
</html>
