(() => {
    function marcarRadio(nomeCampo, valor, fallbackId, container = document) {
        const radios = container.querySelectorAll(`input[name="${nomeCampo}"]`);
        let encontrou = false;

        radios.forEach((radio) => {
            const marcar = valor !== '' && radio.value === valor;
            radio.checked = marcar;
            if (marcar) {
                encontrou = true;
            }
        });

        if (!encontrou) {
            const fallback = container.querySelector(`#${fallbackId}`) || document.getElementById(fallbackId);
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

            marcarRadio('status_manha', statusManha, 'manha_nao_definido', modalDia);
            marcarRadio('status_tarde', statusTarde, 'tarde_nao_definido', modalDia);
        });
    }

    const calendario = document.getElementById('calendarioEscala');
    const botaoToggleMultiplo = document.getElementById('toggleSelecaoMultipla');
    const botaoAbrirModalMultiplo = document.getElementById('abrirModalMultiplo');
    const contadorSelecaoMultipla = document.getElementById('contadorSelecaoMultipla');
    const modalMultiplo = document.getElementById('modalMultiplo');
    const listaDatasMultiplo = document.getElementById('modalMultiploListaDatas');
    const quantidadeDatasMultiplo = document.getElementById('modalMultiploQuantidade');
    const containerInputsDatas = document.getElementById('modalMultiploDatasInputs');

    if (calendario && botaoToggleMultiplo && botaoAbrirModalMultiplo && contadorSelecaoMultipla) {
        let modoMultiploAtivo = false;

        const obterDiasSelecionados = () => Array.from(
            calendario.querySelectorAll('.dia[data-dia].selecionado')
        ).map((dia) => dia.getAttribute('data-dia') || '').filter(Boolean);

        const atualizarResumoSelecao = () => {
            const diasSelecionados = obterDiasSelecionados();
            const quantidade = diasSelecionados.length;

            botaoAbrirModalMultiplo.disabled = quantidade === 0;
            contadorSelecaoMultipla.textContent = quantidade === 0
                ? 'Nenhum dia selecionado.'
                : `${quantidade} dia(s) selecionado(s).`;
        };

        const limparSelecaoMultipla = () => {
            calendario.querySelectorAll('.dia[data-dia].selecionado').forEach((dia) => {
                dia.classList.remove('selecionado');
                const checkbox = dia.querySelector('.dia-checkbox');
                if (checkbox) {
                    checkbox.checked = false;
                }
            });
            atualizarResumoSelecao();
        };

        const atualizarModoMultiplo = () => {
            calendario.classList.toggle('modo-multiplo', modoMultiploAtivo);
            botaoAbrirModalMultiplo.classList.toggle('d-none', !modoMultiploAtivo);
            contadorSelecaoMultipla.classList.toggle('d-none', !modoMultiploAtivo);
            botaoToggleMultiplo.classList.toggle('btn-outline-primary', !modoMultiploAtivo);
            botaoToggleMultiplo.classList.toggle('btn-outline-secondary', modoMultiploAtivo);
            botaoToggleMultiplo.textContent = modoMultiploAtivo
                ? 'Cancelar cadastro múltiplo'
                : 'Cadastrar múltiplos dias de escala iguais';

            calendario.querySelectorAll('.dia[data-dia]').forEach((dia) => {
                dia.classList.toggle('modo-multiplo', modoMultiploAtivo);
                if (modoMultiploAtivo) {
                    dia.removeAttribute('data-bs-toggle');
                    dia.removeAttribute('data-bs-target');
                } else {
                    dia.setAttribute('data-bs-toggle', 'modal');
                    dia.setAttribute('data-bs-target', '#modalDia');
                }
            });

            if (!modoMultiploAtivo) {
                limparSelecaoMultipla();
            } else {
                atualizarResumoSelecao();
            }
        };

        botaoToggleMultiplo.addEventListener('click', () => {
            modoMultiploAtivo = !modoMultiploAtivo;
            atualizarModoMultiplo();
        });

        calendario.addEventListener('click', (event) => {
            if (!modoMultiploAtivo) {
                return;
            }

            const dia = event.target.closest('.dia[data-dia]');
            if (!dia) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            dia.classList.toggle('selecionado');
            const checkbox = dia.querySelector('.dia-checkbox');
            if (checkbox) {
                checkbox.checked = dia.classList.contains('selecionado');
            }

            atualizarResumoSelecao();
        });

        atualizarModoMultiplo();

        if (modalMultiplo && listaDatasMultiplo && quantidadeDatasMultiplo && containerInputsDatas) {
            modalMultiplo.addEventListener('show.bs.modal', (event) => {
                const diasSelecionados = obterDiasSelecionados().sort();

                if (diasSelecionados.length === 0) {
                    event.preventDefault();
                    return;
                }

                listaDatasMultiplo.innerHTML = '';
                containerInputsDatas.innerHTML = '';
                quantidadeDatasMultiplo.textContent = String(diasSelecionados.length);
                marcarRadio('status_manha', '', 'multiplo_manha_nao_definido', modalMultiplo);
                marcarRadio('status_tarde', '', 'multiplo_tarde_nao_definido', modalMultiplo);

                diasSelecionados.forEach((dataIso) => {
                    const item = document.createElement('li');
                    item.textContent = formatarDataPtBr(dataIso);
                    listaDatasMultiplo.appendChild(item);

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'datas[]';
                    input.value = dataIso;
                    containerInputsDatas.appendChild(input);
                });
            });

            modalMultiplo.addEventListener('hidden.bs.modal', () => {
                containerInputsDatas.innerHTML = '';
            });
        }
    }
})();
