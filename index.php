<?php
/**
 * Página de Registro de Regime de Trabalho
 * Sistema de Gestão de Regime de Trabalho
 */

require_once 'functions.php';
@ini_set('session.use_cookies', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.use_strict_mode', '1');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function codificarTokenBase64Url($valor) {
    return rtrim(strtr(base64_encode((string)$valor), '+/', '-_'), '=');
}

function decodificarTokenBase64Url($valor) {
    $valor = strtr((string)$valor, '-_', '+/');
    $resto = strlen($valor) % 4;
    if ($resto !== 0) {
        $valor .= str_repeat('=', 4 - $resto);
    }

    return base64_decode($valor, true);
}

function obterChaveTokenLiberacaoCalendario() {
    $semente = (string)obterConfiguracao('edit_access_password', '');
    if ($semente === '') {
        $semente = __FILE__;
    }

    return hash('sha256', 'planejamento_social:' . $semente);
}

function gerarTokenLiberacaoCalendario($funcionarioId, $ttlSegundos = 43200) {
    $funcionarioId = (int)$funcionarioId;
    $expiraEm = time() + max(300, (int)$ttlSegundos);
    $payload = $funcionarioId . '|' . $expiraEm;
    $assinatura = hash_hmac('sha256', $payload, obterChaveTokenLiberacaoCalendario());

    return codificarTokenBase64Url($payload . '|' . $assinatura);
}

function validarTokenLiberacaoCalendario($token, $funcionarioEsperado = null) {
    $token = trim((string)$token);
    if ($token === '') {
        return null;
    }

    $bruto = decodificarTokenBase64Url($token);
    if ($bruto === false) {
        return null;
    }

    $partes = explode('|', $bruto, 3);
    if (count($partes) !== 3) {
        return null;
    }

    [$funcionarioIdBruto, $expiraEmBruto, $assinaturaInformada] = $partes;
    if (!ctype_digit($funcionarioIdBruto) || !ctype_digit($expiraEmBruto)) {
        return null;
    }

    $funcionarioId = (int)$funcionarioIdBruto;
    $expiraEm = (int)$expiraEmBruto;
    if ($funcionarioId <= 0 || $expiraEm < time()) {
        return null;
    }

    if ($funcionarioEsperado !== null && $funcionarioId !== (int)$funcionarioEsperado) {
        return null;
    }

    $payload = $funcionarioId . '|' . $expiraEm;
    $assinaturaEsperada = hash_hmac('sha256', $payload, obterChaveTokenLiberacaoCalendario());
    if (!hash_equals($assinaturaEsperada, (string)$assinaturaInformada)) {
        return null;
    }

    return [
        'funcionario_id' => $funcionarioId,
        'expira_em' => $expiraEm,
        'token' => $token
    ];
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
$token_liberacao_informado = (string)($_POST['acesso'] ?? ($_GET['acesso'] ?? ''));
$dados_token_liberacao = validarTokenLiberacaoCalendario($token_liberacao_informado);
$token_liberacao_ativo = $dados_token_liberacao !== null ? $dados_token_liberacao['token'] : '';

$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : null;
if ($funcionario_id === null && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['funcionario_id'])) {
    $funcionario_id = (int)$_POST['funcionario_id'];
}

$funcionario_liberado_id = isset($_SESSION['funcionario_liberado_id']) ? (int)$_SESSION['funcionario_liberado_id'] : null;
if ($funcionario_liberado_id === null && $dados_token_liberacao !== null) {
    $funcionario_liberado_id = (int)$dados_token_liberacao['funcionario_id'];
}
if ($dados_token_liberacao !== null && $funcionario_liberado_id === (int)$dados_token_liberacao['funcionario_id']) {
    $_SESSION['funcionario_liberado_id'] = $funcionario_liberado_id;
}
if ($token_liberacao_ativo === '' && $funcionario_liberado_id !== null && $funcionario_liberado_id > 0) {
    $token_liberacao_ativo = gerarTokenLiberacaoCalendario($funcionario_liberado_id);
}
$query_acesso = $token_liberacao_ativo !== '' ? '&acesso=' . rawurlencode($token_liberacao_ativo) : '';

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
                        Cadastro concluído com sucesso.
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
    $cpf_prefixo_cadastrado = obterPrefixoCpfFuncionario($func_id);

    if ($cpf_prefixo_cadastrado === null) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Não foi possível liberar: CPF não cadastrado para esta pessoa.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } elseif (!preg_match('/^\d{3}$/', $cpf_prefixo_informado)) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Informe exatamente os 3 primeiros dígitos do CPF.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } elseif ($cpf_prefixo_informado !== $cpf_prefixo_cadastrado) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Os 3 primeiros dígitos do CPF não conferem.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } else {
        $_SESSION['funcionario_liberado_id'] = $func_id;
        $token_liberacao_ativo = gerarTokenLiberacaoCalendario($func_id);
        header('Location: ?funcionario_id=' . $func_id . '&ano=' . $ano_form . '&mes=' . $mes_form . '&acesso=' . rawurlencode($token_liberacao_ativo));
        exit;
    }
}

$pode_editar_calendario = $funcionario_id !== null && $funcionario_liberado_id === $funcionario_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $func_id = (int)($_POST['funcionario_id'] ?? 0);
    $data = $_POST['data'] ?? '';
    $token_salvar = (string)($_POST['acesso'] ?? '');
    $liberacao_por_token = validarTokenLiberacaoCalendario($token_salvar, $func_id) !== null;
    $liberado_para_salvar = $pode_editar_calendario || $liberacao_por_token;

    if (!$liberado_para_salvar || $func_id !== $funcionario_id) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Calendário bloqueado. Selecione o nome e valide os 3 dígitos do CPF para editar.
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
$nome_funcionario_selecionado = '';
if ($funcionario_id !== null) {
    foreach ($funcionarios as $funcionario) {
        if ((int)$funcionario['id'] === (int)$funcionario_id) {
            $nome_funcionario_selecionado = (string)$funcionario['nome'];
            break;
        }
    }
}

$nome_funcionario_liberado = '';
if ($pode_editar_calendario) {
    foreach ($funcionarios as $funcionario) {
        if ((int)$funcionario['id'] === (int)$funcionario_id) {
            $nome_funcionario_liberado = (string)$funcionario['nome'];
            break;
        }
    }
}

$dias_nao_trabalhados_mes = obterDiasNaoTrabalhados($ano, $mes);
$mapa_feriados = [];
foreach ($dias_nao_trabalhados_mes as $diaNaoTrabalhado) {
    if (($diaNaoTrabalhado['tipo'] ?? '') !== 'feriado' || empty($diaNaoTrabalhado['data'])) {
        continue;
    }

    $mapa_feriados[(string)$diaNaoTrabalhado['data']] = trim((string)($diaNaoTrabalhado['descricao'] ?? ''));
}

$regime = $funcionario_id ? obterRegimeTrabalho($funcionario_id, $ano, $mes) : [];

$data_inicio = "$ano-" . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . "-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));
$dias_mes = [];
$dataCursor = new DateTime($data_inicio);
$fim = new DateTime($data_fim);

while ($dataCursor <= $fim) {
    $dataIso = $dataCursor->format('Y-m-d');
    $ehFeriado = isset($mapa_feriados[$dataIso]);
    $dias_mes[] = [
        'dia' => (int)$dataCursor->format('d'),
        'data' => $dataIso,
        'dia_semana' => (int)$dataCursor->format('w'),
        'eh_feriado' => $ehFeriado,
        'descricao_feriado' => $ehFeriado ? (string)$mapa_feriados[$dataIso] : ''
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

        .mes-centro {
            min-width: 200px;
            text-align: center;
        }

        .mes-pessoa {
            margin-top: 4px;
            font-size: 0.82rem;
            color: #1f2937;
        }

        .mes-pessoa strong {
            color: #1d4ed8;
            font-weight: 600;
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
            background-color: #f9fafb;
            color: #9ca3af;
        }

        .dia.feriado {
            background-image:
                linear-gradient(
                    45deg,
                    rgba(15, 23, 42, 0.12) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(15, 23, 42, 0.12) 50%,
                    rgba(15, 23, 42, 0.12) 75%,
                    transparent 75%,
                    transparent
                );
            background-size: 12px 12px;
        }

        .dia-numero {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .feriado-tag {
            display: inline-block;
            margin: 0 auto 4px;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            color: #92400e;
            background: rgba(254, 243, 199, 0.95);
            border: 1px solid rgba(217, 119, 6, 0.4);
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

        .secao-visao-geral {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border: 1px solid #bfdbfe;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }

        .secao-visao-geral .secao-descricao {
            margin: 0;
            max-width: 780px;
        }

        .btn-visao-geral {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.22);
        }

        .btn-visao-geral:hover {
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
            color: #fff;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .modal-footer-acesso {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .modal-footer-acesso .btn {
            width: 100%;
            margin: 0;
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

            .secao-visao-geral {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-visao-geral {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="pg-wrapper">
        <header class="pg-header">
            <h1 class="pg-header-title">Registro de Regime de Trabalho</h1>
            <p class="pg-header-subtitle">Selecione um nome e valide os 3 primeiros dígitos do CPF para liberar edição.</p>
        </header>

        <main class="pg-container">
            <section class="secao secao-visao-geral">
                <div>
                    <h2 class="secao-titulo">Visão geral do planejamento</h2>
                    <p class="secao-descricao">Acompanhe rapidamente a cobertura presencial e os períodos críticos do mês em uma visão consolidada.</p>
                </div>
                <a href="visao-geral.php?ano=<?php echo (int)$ano; ?>&mes=<?php echo (int)$mes; ?>" class="btn-visao-geral">
                    Ver visão geral
                </a>
            </section>

            <?php if ($mensagem): ?>
                <div class="mensagem-box"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <div class="layout-grid">
                <section class="secao">
                    <h2 class="secao-titulo">Pessoas cadastradas</h2>
                    <p class="secao-descricao">Clique no título para abrir cada lista.</p>

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
                                                   data-funcionario-id="<?php echo (int)$func['id']; ?>"
                                                   data-funcionario-nome="<?php echo htmlspecialchars($func['nome'], ENT_QUOTES); ?>">
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
                                                   data-funcionario-id="<?php echo (int)$func['id']; ?>"
                                                   data-funcionario-nome="<?php echo htmlspecialchars($func['nome'], ENT_QUOTES); ?>">
                                                    <?php echo htmlspecialchars($func['nome']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Nenhum estagiário cadastrado.</span>
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
                                Calendário bloqueado para edição. Clique no nome da lista e informe os 3 primeiros dígitos do CPF para liberar.
                            </div>
                        <?php endif; ?>

                        <div class="navegacao-mes">
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes === 1 ? $ano - 1 : $ano; ?>&mes=<?php echo $mes === 1 ? 12 : $mes - 1; ?><?php echo $query_acesso; ?>" class="btn-nav">
                                ← Anterior
                            </a>
                            <div class="mes-centro">
                                <div class="mes-ano"><?php echo obterNomeMes($mes) . ' ' . $ano; ?></div>
                                <?php if ($nome_funcionario_liberado !== ''): ?>
                                    <div class="mes-pessoa">Preenchendo escala: <strong><?php echo htmlspecialchars($nome_funcionario_liberado); ?></strong></div>
                                <?php elseif ($nome_funcionario_selecionado !== ''): ?>
                                    <div class="mes-pessoa">Pessoa selecionada: <strong><?php echo htmlspecialchars($nome_funcionario_selecionado); ?></strong></div>
                                <?php endif; ?>
                            </div>
                            <a href="?funcionario_id=<?php echo $funcionario_id; ?>&ano=<?php echo $mes === 12 ? $ano + 1 : $ano; ?>&mes=<?php echo $mes === 12 ? 1 : $mes + 1; ?><?php echo $query_acesso; ?>" class="btn-nav">
                                Próximo →
                            </a>
                        </div>

                        <?php if (!empty($mapa_feriados)): ?>
                            <div class="text-muted small mb-2">Dias com padrão quadriculado indicam feriado.</div>
                        <?php endif; ?>

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
                            $dia_semana_inicio = (int)$primeiro_dia->format('w'); // 0 = domingo, 6 = sábado
                            for ($i = 0; $i < $dia_semana_inicio; $i++) {
                                echo '<div class="dia outro-mes"></div>';
                            }

                            foreach ($dias_mes as $dia_info):
                                $data = $dia_info['data'];
                                $dia = $dia_info['dia'];
                                $status_manha = $regime[$data . '_manhã'] ?? null;
                                $status_tarde = $regime[$data . '_tarde'] ?? null;
                                $eh_fim_semana = $dia_info['dia_semana'] === 0 || $dia_info['dia_semana'] === 6;
                                $eh_feriado = !empty($dia_info['eh_feriado']);
                                $classe_dia = 'dia '
                                    . ($eh_fim_semana ? 'outro-mes ' : '')
                                    . ($pode_editar_calendario ? '' : 'bloqueado ')
                                    . ($eh_feriado ? 'feriado' : '');
                                $titulo_dia = '';
                                if ($eh_feriado) {
                                    $titulo_dia = 'Feriado';
                                    if (!empty($dia_info['descricao_feriado'])) {
                                        $titulo_dia .= ': ' . $dia_info['descricao_feriado'];
                                    }
                                }
                            ?>
                                <div class="<?php echo trim($classe_dia); ?>"
                                     <?php if ($titulo_dia !== ''): ?>
                                         title="<?php echo htmlspecialchars($titulo_dia, ENT_QUOTES); ?>"
                                     <?php endif; ?>
                                     <?php if ($pode_editar_calendario): ?>
                                         data-bs-toggle="modal" data-bs-target="#modalDia"
                                         data-dia="<?php echo htmlspecialchars($data, ENT_QUOTES); ?>"
                                         data-funcionario-id="<?php echo (int)$funcionario_id; ?>"
                                         data-status-manha="<?php echo htmlspecialchars((string)($status_manha ?? ''), ENT_QUOTES); ?>"
                                         data-status-tarde="<?php echo htmlspecialchars((string)($status_tarde ?? ''), ENT_QUOTES); ?>"
                                     <?php endif; ?>>
                                    <div class="dia-numero"><?php echo $dia; ?></div>
                                    <?php if ($eh_feriado): ?>
                                        <div class="feriado-tag">Feriado</div>
                                    <?php endif; ?>
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
                            $total_dias = count($dias_mes) + $dia_semana_inicio;
                            $dias_faltantes = (42 - $total_dias) % 7;
                            for ($i = 0; $i < $dias_faltantes; $i++) {
                                echo '<div class="dia outro-mes"></div>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0" role="alert">
                            Nenhum nome selecionado. O calendário permanece bloqueado até que um nome seja selecionado e validado.
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
                        <input type="hidden" name="acesso" value="<?php echo htmlspecialchars($token_liberacao_ativo, ENT_QUOTES); ?>">

                        <p class="mb-2">Nome selecionado: <strong id="acessoFuncionarioNome"></strong></p>

                        <div class="mb-2">
                            <label class="form-label" for="acessoCpfPrefixo">Informe os 3 primeiros dígitos do CPF</label>
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

                    </div>
                    <div class="modal-footer modal-footer-acesso">
                        <button type="submit" class="btn btn-primary">Liberar preenchimento</button>
                        <a href="visao-geral.php?ano=<?php echo (int)$ano; ?>&mes=<?php echo (int)$mes; ?>" class="btn btn-outline-secondary">
                            Quero apenas visualizar o planejamento
                        </a>
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
                        <input type="hidden" name="acesso" value="<?php echo htmlspecialchars($token_liberacao_ativo, ENT_QUOTES); ?>">

                        <div class="mb-3">
                            <label class="form-label"><strong>Data: <span id="modalDataExibicao"></span></strong></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Manhã</strong></label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="" id="manha_nao_definido" checked><label class="form-check-label" for="manha_nao_definido">Não definido</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="presencial" id="manha_presencial"><label class="form-check-label" for="manha_presencial">🏢 Presencial</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="homeoffice" id="manha_homeoffice"><label class="form-check-label" for="manha_homeoffice">🏠 Home Office</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="férias" id="manha_ferias"><label class="form-check-label" for="manha_ferias">🏖️ Férias</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_manha" value="afastamento" id="manha_afastamento"><label class="form-check-label" for="manha_afastamento">🚫 Afastamento</label></div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label"><strong>Tarde</strong></label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="" id="tarde_nao_definido" checked><label class="form-check-label" for="tarde_nao_definido">Não definido</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="presencial" id="tarde_presencial"><label class="form-check-label" for="tarde_presencial">🏢 Presencial</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="homeoffice" id="tarde_homeoffice"><label class="form-check-label" for="tarde_homeoffice">🏠 Home Office</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="status_tarde" value="férias" id="tarde_ferias"><label class="form-check-label" for="tarde_ferias">🏖️ Férias</label></div>
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
                    <h5 class="modal-title">Cadastro de funcionário</h5>
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
                        <button type="submit" class="btn btn-primary" name="categoria" value="estagiario">Salvar como estagiário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/index.js?v=<?php echo (int)filemtime(__DIR__ . '/assets/index.js'); ?>"></script>
</body>
</html>
