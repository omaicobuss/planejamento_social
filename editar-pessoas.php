<?php
require_once 'functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$mensagem = '';
$adminAutenticado = !empty($_SESSION['admin_page_authenticated']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_logout'])) {
    unset($_SESSION['admin_page_authenticated']);
    $adminAutenticado = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $senha = (string)($_POST['senha_admin'] ?? '');
    if (verificarSenhaConfiguracao('admin_page_password', $senha)) {
        $_SESSION['admin_page_authenticated'] = true;
        $adminAutenticado = true;
    } else {
        $mensagem = '<div class="alert alert-danger" role="alert">Senha invalida.</div>';
    }
}

if ($adminAutenticado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_senha_edicao'])) {
    $novaSenha = (string)($_POST['nova_senha_edicao'] ?? '');
    $confirmacao = (string)($_POST['confirmar_senha_edicao'] ?? '');

    if (trim($novaSenha) === '') {
        $mensagem = '<div class="alert alert-danger" role="alert">Informe a nova senha de edicao.</div>';
    } elseif ($novaSenha !== $confirmacao) {
        $mensagem = '<div class="alert alert-danger" role="alert">Confirmacao da senha de edicao nao confere.</div>';
    } elseif (definirSenhaConfiguracao('edit_access_password', $novaSenha)) {
        $mensagem = '<div class="alert alert-success" role="alert">Senha de edicao atualizada com sucesso.</div>';
    } else {
        $mensagem = '<div class="alert alert-danger" role="alert">Nao foi possivel atualizar a senha de edicao.</div>';
    }
}

if ($adminAutenticado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_pessoa'])) {
    $resultado = atualizarPessoaCadastrada(
        $_POST['pessoa_id'] ?? 0,
        $_POST['nome'] ?? '',
        $_POST['cpf'] ?? '',
        $_POST['email'] ?? '',
        $_POST['supervisor'] ?? '',
        $_POST['categoria'] ?? ''
    );

    if ($resultado['sucesso']) {
        $mensagem = '<div class="alert alert-success" role="alert">' . htmlspecialchars($resultado['mensagem']) . '</div>';
    } else {
        $mensagem = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($resultado['mensagem']) . '</div>';
    }
}

$tiposDiaNaoTrabalhado = [
    'feriado' => 'Feriado',
    'nao_trabalhado' => 'Dia nao trabalhado'
];

if ($adminAutenticado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_dia_nao_trabalhado'])) {
    $resultadoDia = salvarDiaNaoTrabalhado(
        $_POST['data_dia_nao_trabalhado'] ?? '',
        $_POST['tipo_dia_nao_trabalhado'] ?? '',
        $_POST['descricao_dia_nao_trabalhado'] ?? ''
    );

    if ($resultadoDia['sucesso']) {
        $mensagem = '<div class="alert alert-success" role="alert">' . htmlspecialchars($resultadoDia['mensagem']) . '</div>';
    } else {
        $mensagem = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($resultadoDia['mensagem']) . '</div>';
    }
}

if ($adminAutenticado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_dia_nao_trabalhado'])) {
    $resultadoDia = excluirDiaNaoTrabalhado($_POST['dia_nao_trabalhado_id'] ?? 0);

    if ($resultadoDia['sucesso']) {
        $mensagem = '<div class="alert alert-success" role="alert">' . htmlspecialchars($resultadoDia['mensagem']) . '</div>';
    } else {
        $mensagem = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($resultadoDia['mensagem']) . '</div>';
    }
}

$pessoas = $adminAutenticado ? obterPessoasCadastradas() : [];
$diasNaoTrabalhados = $adminAutenticado ? obterDiasNaoTrabalhados() : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edicao de Pessoas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Edicao de Pessoas Cadastradas</h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
        </div>

        <?php if ($mensagem): ?>
            <?php echo $mensagem; ?>
        <?php endif; ?>

        <?php if (!$adminAutenticado): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Acesso protegido por senha</h2>
                    <form method="POST" class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label" for="senha_admin">Senha</label>
                            <input type="password" class="form-control" id="senha_admin" name="senha_admin" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="admin_login" class="btn btn-primary">Entrar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-end mb-3">
                <form method="POST">
                    <button type="submit" name="admin_logout" class="btn btn-outline-danger btn-sm">Sair</button>
                </form>
            </div>

            <section class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6">Senha para liberar edicao no calendario</h2>
                    <form method="POST" class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label" for="nova_senha_edicao">Nova senha</label>
                            <input type="password" class="form-control" id="nova_senha_edicao" name="nova_senha_edicao" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="confirmar_senha_edicao">Confirmar senha</label>
                            <input type="password" class="form-control" id="confirmar_senha_edicao" name="confirmar_senha_edicao" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="salvar_senha_edicao" class="btn btn-primary">Salvar senha de edicao</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6">Dias sem necessidade de escala</h2>
                    <p class="text-muted small mb-3">Cadastre feriados e outros dias nao trabalhados para que o sistema desconsidere a necessidade de escala nessas datas.</p>

                    <form method="POST" class="row g-3 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label" for="dataDiaNaoTrabalhado">Data</label>
                            <input type="date" class="form-control" id="dataDiaNaoTrabalhado" name="data_dia_nao_trabalhado" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="tipoDiaNaoTrabalhado">Tipo</label>
                            <select class="form-select" id="tipoDiaNaoTrabalhado" name="tipo_dia_nao_trabalhado" required>
                                <?php foreach ($tiposDiaNaoTrabalhado as $valorTipo => $rotuloTipo): ?>
                                    <option value="<?php echo htmlspecialchars($valorTipo); ?>"><?php echo htmlspecialchars($rotuloTipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="descricaoDiaNaoTrabalhado">Descricao (opcional)</label>
                            <input type="text" class="form-control" id="descricaoDiaNaoTrabalhado" name="descricao_dia_nao_trabalhado" maxlength="120">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" name="salvar_dia_nao_trabalhado" class="btn btn-primary">Salvar dia</button>
                        </div>
                    </form>

                    <?php if (count($diasNaoTrabalhados) === 0): ?>
                        <div class="alert alert-secondary mb-0">Nenhum dia nao trabalhado cadastrado.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Descricao</th>
                                        <th class="text-end">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diasNaoTrabalhados as $dia): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(formatarData($dia['data'])); ?></td>
                                            <td><?php echo htmlspecialchars($tiposDiaNaoTrabalhado[$dia['tipo']] ?? $dia['tipo']); ?></td>
                                            <td><?php echo htmlspecialchars((string)($dia['descricao'] ?? '')); ?></td>
                                            <td class="text-end">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="dia_nao_trabalhado_id" value="<?php echo (int)$dia['id']; ?>">
                                                    <button type="submit" name="excluir_dia_nao_trabalhado" class="btn btn-outline-danger btn-sm">Excluir</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Pessoas cadastradas</h2>
                    <?php if (count($pessoas) === 0): ?>
                        <div class="alert alert-secondary mb-0">Nenhuma pessoa cadastrada.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th>E-mail</th>
                                        <th>Supervisor</th>
                                        <th>Categoria</th>
                                        <th>Acoes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pessoas as $pessoa): ?>
                                        <tr>
                                            <form method="POST">
                                                <input type="hidden" name="pessoa_id" value="<?php echo (int)$pessoa['id']; ?>">
                                                <td><input type="text" name="nome" class="form-control form-control-sm" value="<?php echo htmlspecialchars($pessoa['nome']); ?>" required></td>
                                                <td><input type="text" name="cpf" class="form-control form-control-sm" value="<?php echo htmlspecialchars($pessoa['cpf']); ?>" maxlength="14" required></td>
                                                <td><input type="email" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($pessoa['email']); ?>" required></td>
                                                <td><input type="text" name="supervisor" class="form-control form-control-sm" value="<?php echo htmlspecialchars($pessoa['supervisor']); ?>" required></td>
                                                <td>
                                                    <select name="categoria" class="form-select form-select-sm" required>
                                                        <option value="servidor" <?php echo $pessoa['categoria'] === 'servidor' ? 'selected' : ''; ?>>Servidor</option>
                                                        <option value="estagiario" <?php echo $pessoa['categoria'] === 'estagiario' ? 'selected' : ''; ?>>Estagiario</option>
                                                    </select>
                                                </td>
                                                <td><button type="submit" name="salvar_pessoa" class="btn btn-primary btn-sm">Salvar</button></td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
