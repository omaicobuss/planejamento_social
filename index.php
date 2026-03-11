<?php
/**
 * Pagina de Registro de Regime de Trabalho
 * Sistema de Gestao de Regime de Trabalho
 */

require_once 'functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
if ($mes < 1 || $mes > 12) {
    $mes = (int)date('m');
}
if ($ano < 2020 || $ano > 2030) {
    $ano = (int)date('Y');
}

$mensagem = '';
$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : null;
$funcionario_liberado_id = isset($_SESSION['funcionario_liberado_id']) ? (int)$_SESSION['funcionario_liberado_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_funcionario'])) {
    $resultadoCadastro = cadastrarFuncionario(
        $_POST['nome_completo'] ?? '',
        $_POST['cpf'] ?? '',
        $_POST['email'] ?? '',
        $_POST['supervisor'] ?? '',
        $_POST['categoria'] ?? 'servidor'
    );

    if ($resultadoCadastro['sucesso']) {
        $funcionario_id = (int)$resultadoCadastro['id'];
        $mensagem = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Cadastro concluido com sucesso.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } else {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
            . htmlspecialchars($resultadoCadastro['mensagem']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liberar_calendario'])) {
    $func_id = (int)($_POST['funcionario_id'] ?? 0);
    $ano_form = isset($_POST['ano']) ? (int)$_POST['ano'] : $ano;
    $mes_form = isset($_POST['mes']) ? (int)$_POST['mes'] : $mes;
    $cpf_prefixo_informado = preg_replace('/\D+/', '', (string)($_POST['cpf_prefixo'] ?? ''));
    $cpf_prefixo_informado = substr($cpf_prefixo_informado, 0, 3);
    $senha_edicao = (string)($_POST['senha_edicao'] ?? '');
    $cpf_prefixo_cadastrado = obterPrefixoCpfFuncionario($func_id);

    if ($cpf_prefixo_cadastrado === null) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Nao foi possivel liberar: CPF nao cadastrado para esta pessoa.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } elseif (!verificarSenhaConfiguracao('edit_access_password', $senha_edicao)) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Senha de edicao invalida.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } elseif (!preg_match('/^\d{3}$/', $cpf_prefixo_informado)) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Informe exatamente os 3 primeiros digitos do CPF.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } elseif ($cpf_prefixo_informado !== $cpf_prefixo_cadastrado) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Os 3 primeiros digitos do CPF nao conferem.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } else {
        $_SESSION['funcionario_liberado_id'] = $func_id;
        header('Location: ?funcionario_id=' . $func_id . '&ano=' . $ano_form . '&mes=' . $mes_form);
        exit;
    }
}

$pode_editar_calendario = $funcionario_id !== null && $funcionario_liberado_id === $funcionario_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $func_id = (int)($_POST['funcionario_id'] ?? 0);
    $data = $_POST['data'] ?? '';

    if (!$pode_editar_calendario || $func_id !== $funcionario_id) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Calendario bloqueado. Selecione o nome e valide os 3 digitos do CPF para editar.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } else {
        $status_manha = isset($_POST['status_manha']) && $_POST['status_manha'] !== '' ? $_POST['status_manha'] : null;
        $status_tarde = isset($_POST['status_tarde']) && $_POST['status_tarde'] !== '' ? $_POST['status_tarde'] : null;

        $okManha = salvarRegimeTrabalho($func_id, $data, 'manhã', $status_manha);
        $okTarde = salvarRegimeTrabalho($func_id, $data, 'tarde', $status_tarde);

        if ($okManha && $okTarde) {
            $mensagem = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            Registro salvo com sucesso.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
        } else {
            $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Erro ao salvar o registro.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
        }
    }
}

$funcionariosPorCategoria = obterFuncionariosPorCategoria();
$funcionarios = array_merge($funcionariosPorCategoria['servidores'], $funcionariosPorCategoria['estagiarios']);

$regime = $funcionario_id ? obterRegimeTrabalho($funcionario_id, $ano, $mes) : [];

$data_inicio = "$ano-" . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . "-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));
$dias_mes = [];
$dataCursor = new DateTime($data_inicio);
$fim = new DateTime($data_fim);

while ($dataCursor <= $fim) {
    $dias_mes[] = [
        'dia' => (int)$dataCursor->format('d'),
        'data' => $dataCursor->format('Y-m-d'),
        'dia_semana' => (int)$dataCursor->format('N')
    ];
    $dataCursor->modify('+1 day');
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

        .accordion .accordion-button {
            font-weight: 600;
            padding: 10px 12px;
            background: #f8fafc;
        }

        .accordion .accordion-button:not(.collapsed) {
            color: #1d4ed8;
            background: #eff6ff;
        }

        .accordion .accordion-body {
            padding: 10px;
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
            display: block;
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

        .btn-cadastro {
            width: 100%;
            margin-top: 12px;
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

        .dia.bloqueado {
            cursor: not-allowed;
            opacity: 0.75;
        }

        .dia.bloqueado:hover {
            box-shadow: none;
            border-color: #d1d5db;
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
            display: flex;
            justify-content: center;
            gap: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-presencial { background: #dcfce7; color: #166534; }
        .status-homeoffice { background: #fef3c7; color: #92400e; }
        .status-férias { background: #fee2e2; color: #991b1b; }
        .status-afastamento { background: #e5e7eb; color: #374151; }

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
            display: flex;
            flex-direction: column;
            gap: 8px;
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
            <p class="pg-header-subtitle">Selecione um nome e valide os 3 primeiros digitos do CPF para liberar edicao.</p>
        </header>

        <main class="pg-container">
            <?php if ($mensagem): ?>
                <div class="mensagem-box"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <div class="layout-grid">
                <section class="secao">
                    <h2 class="secao-titulo">Pessoas cadastradas</h2>
                    <p class="secao-descricao">Clique no titulo para abrir cada lista.</p>

                    <div class="accordion" id="accordionPessoas">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingServidores">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServidores" aria-expanded="false" aria-controls="collapseServidores">
                                    Servidores
                                </button>
                            </h2>
                            <div id="collapseServidores" class="accordion-collapse collapse" aria-labelledby="headingServidores" data-bs-parent="#accordionPessoas">
                                <div class="accordion-body">
                                    <div class="lista-funcionarios">
                                        <?php if (count($funcionariosPorCategoria['servidores']) > 0): ?>
                                            <?php foreach ($funcionariosPorCategoria['servidores'] as $func): ?>
                                                <button type="button"
                                                   class="func-item <?php echo (int)$func['id'] === (int)$funcionario_id ? 'active' : ''; ?>"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#modalAcesso"
                                                   onclick="abrirModalAcesso(<?php echo (int)$func['id']; ?>, '<?php echo htmlspecialchars($func['nome'], ENT_QUOTES); ?>')">
                                                    <?php echo htmlspecialchars($func['nome']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Nenhum servidor cadastrado.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEstagiarios">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstagiarios" aria-expanded="false" aria-controls="collapseEstagiarios">
                                    Estagiários
                                </button>
                            </h2>
                            <div id="collapseEstagiarios" class="accordion-collapse collapse" aria-labelledby="headingEstagiarios" data-bs-parent="#accordionPessoas">
                                <div class="accordion-body">
                                    <div class="lista-funcionarios">
                                        <?php if (count($funcionariosPorCategoria['estagiarios']) > 0): ?>
                                            <?php foreach ($funcionariosPorCategoria['estagiarios'] as $func): ?>
                                                <button type="button"
                                                   class="func-item <?php echo (int)$func['id'] === (int)$funcionario_id ? 'active' : ''; ?>"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#modalAcesso"
                                                   onclick="abrirModalAcesso(<?php echo (int)$func['id']; ?>, '<?php echo htmlspecialchars($func['nome'], ENT_QUOTES); ?>')">
                                                    <?php echo htmlspecialchars($func['nome']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Nenhum estagiario cadastrado.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-cadastro" type="button" data-bs-toggle="modal" data-bs-target="#modalCadastro">
                        Cadastrar pessoa
                    </button>
                    <a href="editar-pessoas.php" class="btn btn-outline-primary btn-cadastro">
                        Editar pessoas
                    </a>
                </section>

                <section class="secao">
                    <?php if ($funcionario_id): ?>
                        <?php if (!$pode_editar_calendario): ?>
                            <div class="alert alert-warning" role="alert">
                                Calendario bloqueado para edicao. Clique no nome da lista e informe os 3 primeiros digitos do CPF para liberar.
                            </div>
                        <?php endif; ?>

                        <div class="navegacao-mes">
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes === 1 ? $ano - 1 : $ano; ?>&mes=<?php echo $mes === 1 ? 12 : $mes - 1; ?>" class="btn-nav">
                                ← Anterior
                            </a>
                            <div class="mes-ano"><?php echo obterNomeMes($mes) . ' ' . $ano; ?></div>
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes === 12 ? $ano + 1 : $ano; ?>&mes=<?php echo $mes === 12 ? 1 : $mes + 1; ?>" class="btn-nav">
                                Proximo →
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
                            if ($dia_semana_inicio !== 7) {
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
                                $classe_dia = 'dia ' . ($eh_fim_semana ? 'outro-mes ' : '') . ($pode_editar_calendario ? '' : 'bloqueado');
                            ?>
                                <div class="<?php echo trim($classe_dia); ?>"
                                     <?php if ($pode_editar_calendario): ?>
                                         data-bs-toggle="modal" data-bs-target="#modalDia"
                                         onclick="abrirModalDia('<?php echo $data; ?>', <?php echo $funcionario_id; ?>, '<?php echo htmlspecialchars((string)($status_manha ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars((string)($status_tarde ?? ''), ENT_QUOTES); ?>')"
                                     <?php endif; ?>>
                                    <div class="dia-numero"><?php echo $dia; ?></div>
                                    <div class="dia-status">
                                        <?php if ($status_manha): ?>
                                            <span class="status-badge status-<?php echo $status_manha; ?>"><?php echo obterIconeStatus($status_manha); ?></span>
                                        <?php endif; ?>
                                        <?php if ($status_tarde): ?>
                                            <span class="status-badge status-<?php echo $status_tarde; ?>"><?php echo obterIconeStatus($status_tarde); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php
                            $total_dias = count($dias_mes) + ($dia_semana_inicio === 7 ? 0 : $dia_semana_inicio - 1);
                            $dias_faltantes = (42 - $total_dias) % 7;
                            for ($i = 0; $i < $dias_faltantes; $i++) {
                                echo '<div class="dia outro-mes"></div>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0" role="alert">
                            Nenhum nome selecionado. O calendario permanece bloqueado ate que um nome seja selecionado e validado.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalAcesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title">Liberar preenchimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="liberar_calendario" value="1">
                        <input type="hidden" name="funcionario_id" id="acessoFuncionarioId" value="">
                        <input type="hidden" name="ano" value="<?php echo (int)$ano; ?>">
                        <input type="hidden" name="mes" value="<?php echo (int)$mes; ?>">

                        <p class="mb-2">Nome selecionado: <strong id="acessoFuncionarioNome"></strong></p>

                        <div class="mb-2">
                            <label class="form-label" for="acessoCpfPrefixo">Informe os 3 primeiros digitos do CPF</label>
                            <input
                                class="form-control"
                                type="text"
                                id="acessoCpfPrefixo"
                                name="cpf_prefixo"
                                inputmode="numeric"
                                pattern="[0-9]{3}"
                                maxlength="3"
                                required
                            >
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="acessoSenhaEdicao">Senha de edicao</label>
                            <input
                                class="form-control"
                                type="password"
                                id="acessoSenhaEdicao"
                                name="senha_edicao"
                                required
                            >
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="visao-geral.php?ano=<?php echo (int)$ano; ?>&mes=<?php echo (int)$mes; ?>" class="btn btn-outline-secondary">
                            Quero apenas visualizar o planejamento
                        </a>
                        <button type="submit" class="btn btn-primary">Liberar preenchimento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDia" tabindex="-1" aria-hidden="true">
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

                        <div class="mb-3">
                            <label class="form-label"><strong>Manha</strong></label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="" id="manha_nao_definido" checked><label class="form-check-label" for="manha_nao_definido">Nao definido</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="presencial" id="manha_presencial"><label class="form-check-label" for="manha_presencial">🏢 Presencial</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="homeoffice" id="manha_homeoffice"><label class="form-check-label" for="manha_homeoffice">🏠 Home Office</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="férias" id="manha_ferias"><label class="form-check-label" for="manha_ferias">🏖️ Ferias</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="afastamento" id="manha_afastamento"><label class="form-check-label" for="manha_afastamento">🚫 Afastamento</label></div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label"><strong>Tarde</strong></label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="" id="tarde_nao_definido" checked><label class="form-check-label" for="tarde_nao_definido">Nao definido</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="presencial" id="tarde_presencial"><label class="form-check-label" for="tarde_presencial">🏢 Presencial</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="homeoffice" id="tarde_homeoffice"><label class="form-check-label" for="tarde_homeoffice">🏠 Home Office</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="férias" id="tarde_ferias"><label class="form-check-label" for="tarde_ferias">🏖️ Ferias</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="afastamento" id="tarde_afastamento"><label class="form-check-label" for="tarde_afastamento">🚫 Afastamento</label></div>
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

    <div class="modal fade" id="modalCadastro" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title">Cadastro de funcionario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="cadastrar_funcionario" value="1">

                        <div class="mb-3">
                            <label class="form-label" for="nome_completo">Nome completo</label>
                            <input class="form-control" type="text" id="nome_completo" name="nome_completo" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="cpf">CPF</label>
                            <input class="form-control" type="text" id="cpf" name="cpf" maxlength="14" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input class="form-control" type="email" id="email" name="email" required>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="supervisor">Supervisor</label>
                            <input class="form-control" type="text" id="supervisor" name="supervisor">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-outline-primary" name="categoria" value="servidor">Salvar como servidor</button>
                        <button type="submit" class="btn btn-primary" name="categoria" value="estagiario">Salvar como estagiario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="floating-link">
        <a href="visao-geral.php" class="btn-primario">Ver visao geral</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirModalAcesso(funcionarioId, nomeFuncionario) {
            document.getElementById('acessoFuncionarioId').value = funcionarioId;
            document.getElementById('acessoFuncionarioNome').textContent = nomeFuncionario;
            document.getElementById('acessoCpfPrefixo').value = '';
            document.getElementById('acessoSenhaEdicao').value = '';
        }

        function abrirModalDia(data, funcionarioId, statusManha, statusTarde) {
            const dataObj = new Date(data + 'T00:00:00');
            const dataFormatada = dataObj.toLocaleDateString('pt-BR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('modalData').value = data;
            document.getElementById('modalFuncionarioId').value = funcionarioId;
            document.getElementById('modalDataExibicao').textContent = dataFormatada;

            document.querySelectorAll('input[name="status_manha"]').forEach(el => el.checked = false);
            document.querySelectorAll('input[name="status_tarde"]').forEach(el => el.checked = false);

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
