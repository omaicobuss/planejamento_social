(() => {
    function marcarRadio(nomeCampo, valor, fallbackId) {
        const radios = document.querySelectorAll(`input[name="${nomeCampo}"]`);
        let encontrou = false;

        radios.forEach((radio) => {
            const marcar = valor !== '' && radio.value === valor;
            radio.checked = marcar;
            if (marcar) {
                encontrou = true;
            }
        });

        if (!encontrou) {
            const fallback = document.getElementById(fallbackId);
            if (fallback) {
                fallback.checked = true;
            }
        }
    }

    function formatarDataPtBr(dataIso) {
        if (!dataIso) {
            return '';
        }

        const dataObj = new Date(`${dataIso}T00:00:00`);
        if (Number.isNaN(dataObj.getTime())) {
            return dataIso;
        }

        return dataObj.toLocaleDateString('pt-BR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    const modalAcesso = document.getElementById('modalAcesso');
    if (modalAcesso) {
        modalAcesso.addEventListener('show.bs.modal', (event) => {
            const botaoOrigem = event.relatedTarget;
            if (!botaoOrigem) {
                return;
            }

            const funcionarioId = botaoOrigem.getAttribute('data-funcionario-id') || '';
            const nomeFuncionario = botaoOrigem.getAttribute('data-funcionario-nome') || '';

            const inputFuncionarioId = document.getElementById('acessoFuncionarioId');
            const outputFuncionarioNome = document.getElementById('acessoFuncionarioNome');
            const inputCpfPrefixo = document.getElementById('acessoCpfPrefixo');

            if (inputFuncionarioId) {
                inputFuncionarioId.value = funcionarioId;
            }

            if (outputFuncionarioNome) {
                outputFuncionarioNome.textContent = nomeFuncionario;
            }

            if (inputCpfPrefixo) {
                inputCpfPrefixo.value = '';
            }
        });

        modalAcesso.addEventListener('shown.bs.modal', () => {
            const inputCpfPrefixo = document.getElementById('acessoCpfPrefixo');
            if (inputCpfPrefixo) {
                inputCpfPrefixo.focus();
            }
        });
    }

    const modalDia = document.getElementById('modalDia');
    if (modalDia) {
        modalDia.addEventListener('show.bs.modal', (event) => {
            const diaOrigem = event.relatedTarget;
            if (!diaOrigem) {
                return;
            }

            const data = diaOrigem.getAttribute('data-dia') || '';
            const funcionarioId = diaOrigem.getAttribute('data-funcionario-id') || '';
            const statusManha = diaOrigem.getAttribute('data-status-manha') || '';
            const statusTarde = diaOrigem.getAttribute('data-status-tarde') || '';

            const inputData = document.getElementById('modalData');
            const inputFuncionarioId = document.getElementById('modalFuncionarioId');
            const outputData = document.getElementById('modalDataExibicao');

            if (inputData) {
                inputData.value = data;
            }

            if (inputFuncionarioId) {
                inputFuncionarioId.value = funcionarioId;
            }

            if (outputData) {
                outputData.textContent = formatarDataPtBr(data);
            }

            marcarRadio('status_manha', statusManha, 'manha_nao_definido');
            marcarRadio('status_tarde', statusTarde, 'tarde_nao_definido');
        });
    }
})();
