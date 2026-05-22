<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Motorista - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #map { height: 100vh; width: 100%; z-index: 0; }
        .count-badge {
            position: absolute; top: -10px; right: -10px;
            background: #ef4444; color: white;
            font-size: 12px; font-weight: bold;
            width: 22px; height: 22px;
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden">

    <div class="absolute top-0 left-0 right-0 p-4 z-[1000] pointer-events-none">
        <div class="bg-white/90 backdrop-blur p-4 rounded-2xl shadow-lg flex justify-between items-center max-w-3xl mx-auto pointer-events-auto gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-[#4A7DDF] rounded-full flex items-center justify-center text-white font-bold shadow-sm shrink-0">
                    <?= substr($_SESSION['usuario_nome'], 0, 1); ?>
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm font-bold text-gray-800 truncate">Ola, <?= explode(' ', $_SESSION['usuario_nome'])[0]; ?>!</h1>
                    <p class="text-[10px] text-blue-500 uppercase font-bold tracking-wider" id="statusMotorista">
                        <?= !empty($viagemAtual['status']) && $viagemAtual['status'] === 'em_rota' ? 'Em rota' : 'Aguardando inicio' ?>
                    </p>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-3 text-xs font-bold uppercase tracking-wider text-gray-500">
                <span id="linhaAtualBadge" class="px-3 py-2 rounded-full bg-slate-100 border border-slate-200">
                    <?= !empty($viagemAtual['nome_linha']) ? htmlspecialchars($viagemAtual['nome_linha']) : 'Linha nao definida' ?>
                </span>
                <span id="numeroOnibusBadge" class="px-3 py-2 rounded-full bg-slate-100 border border-slate-200">
                    Onibus <?= !empty($viagemAtual['numero_onibus']) ? htmlspecialchars($viagemAtual['numero_onibus']) : '--' ?>
                </span>
                <button onclick="abrirConfiguracaoViagem(false)" class="px-4 py-2 rounded-full bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition normal-case tracking-normal">
                    Trocar configuracao
                </button>
            </div>

            <a href="<?= BASE_URL ?>/logout" class="p-2 text-gray-400 hover:text-red-500 transition shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </a>
        </div>
    </div>

    <div class="absolute bottom-8 left-0 right-0 flex justify-center z-[1000] pointer-events-none">
        <button id="btnRota" onclick="toggleRota()" class="pointer-events-auto bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-full shadow-[0_10px_20px_rgba(34,197,94,0.4)] transform transition active:scale-95 flex items-center gap-2 text-lg">
            <svg id="iconRota" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
            <span id="textRota">Iniciar Rota</span>
        </button>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const linhasDisponiveis = <?= json_encode($linhasDisponiveis, JSON_UNESCAPED_UNICODE) ?>;
        const viagemAtual = <?= json_encode($viagemAtual, JSON_UNESCAPED_UNICODE) ?>;
        const configurado = !!(viagemAtual && viagemAtual.linha_id && viagemAtual.numero_onibus);
        let emViagem = !!(viagemAtual && viagemAtual.status === 'em_rota');

        var map = L.map('map', { zoomControl: false }).setView([-21.4059, -48.5052], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

        var marcadoresLayer = L.layerGroup().addTo(map);

        function desenharPontos(pontosArray) {
            marcadoresLayer.clearLayers();
            pontosArray.forEach(function(ponto) {
                var cor = ponto.total_alunos > 0 ? '#ef4444' : '#4A7DDF';
                var busIcon = L.divIcon({
                    html: `
                        <div style="background-color: ${cor}; padding: 7px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); position: relative;">
                            <svg style="width: 14px; height: 14px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            ${ponto.total_alunos > 0 ? `<div class="count-badge">${ponto.total_alunos}</div>` : ''}
                        </div>`,
                    className: '', iconSize: [30, 30], iconAnchor: [15, 15]
                });
                var marker = L.marker([ponto.latitude, ponto.longitude], {icon: busIcon}).bindPopup(`<b>${ponto.nome}</b><br>${ponto.nome_linha}<br>${ponto.total_alunos} alunos aguardando.<br>${ponto.total_retorno || 0} alunos confirmaram volta.`);
                marcadoresLayer.addLayer(marker);
            });
        }

        var pontosIniciais = <?= json_encode($pontos, JSON_UNESCAPED_UNICODE) ?>;
        desenharPontos(pontosIniciais);

        map.locate({setView: true, maxZoom: 15});
        map.on('locationfound', function(e) {
            L.circleMarker(e.latlng, { radius: 8, fillColor: '#22c55e', color: '#fff', weight: 3, opacity: 1, fillOpacity: 1 }).addTo(map).bindPopup('Seu onibus').openPopup();
        });

        function atualizarCabecalhoViagem(linhaNome, numeroOnibus) {
            document.getElementById('linhaAtualBadge').innerText = linhaNome || 'Linha nao definida';
            document.getElementById('numeroOnibusBadge').innerText = 'Onibus ' + (numeroOnibus || '--');
        }

        function abrirConfiguracaoViagem(forcarAbertura) {
            const options = linhasDisponiveis.map(function(linha) {
                const selected = viagemAtual && Number(viagemAtual.linha_id) === Number(linha.id) ? 'selected' : '';
                return `<option value="${linha.id}" ${selected}>${linha.nome} (${linha.cor})</option>`;
            }).join('');

            Swal.fire({
                title: 'Configurar viagem de hoje',
                html: `
                    <div class="space-y-4 text-left">
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-2">Linha do dia</label>
                            <select id="swalLinha" class="swal2-select !w-full !m-0">${options}</select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-2">Numero do onibus</label>
                            <input id="swalOnibus" class="swal2-input !w-full !m-0" placeholder="Ex: 302" value="${viagemAtual && viagemAtual.numero_onibus ? viagemAtual.numero_onibus : ''}">
                        </div>
                    </div>
                `,
                allowOutsideClick: !forcarAbertura,
                allowEscapeKey: !forcarAbertura,
                confirmButtonText: 'Salvar configuracao',
                showCancelButton: !forcarAbertura,
                cancelButtonText: 'Fechar',
                focusConfirm: false,
                preConfirm: () => {
                    const linhaId = document.getElementById('swalLinha').value;
                    const numeroOnibus = document.getElementById('swalOnibus').value.trim();

                    if (!linhaId || !numeroOnibus) {
                        Swal.showValidationMessage('Selecione a linha e informe o numero do onibus.');
                        return false;
                    }

                    const body = new URLSearchParams();
                    body.append('linha_id', linhaId);
                    body.append('numero_onibus', numeroOnibus);

                    return fetch('<?= BASE_URL ?>/motorista/configurar-viagem', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Nao foi possivel configurar a viagem.');
                        }

                        return { linhaId, numeroOnibus };
                    })
                    .catch(error => {
                        Swal.showValidationMessage(error.message);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const linhaSelecionada = linhasDisponiveis.find(linha => Number(linha.id) === Number(result.value.linhaId));
                    atualizarCabecalhoViagem(linhaSelecionada ? linhaSelecionada.nome : 'Linha definida', result.value.numeroOnibus);
                    window.location.reload();
                } else if (forcarAbertura) {
                    setTimeout(() => abrirConfiguracaoViagem(true), 200);
                }
            });
        }

        function atualizarBotaoEstado() {
            const btn = document.getElementById('btnRota');
            const icon = document.getElementById('iconRota');
            const text = document.getElementById('textRota');
            const status = document.getElementById('statusMotorista');

            if (emViagem) {
                btn.classList.remove('bg-green-500', 'hover:bg-green-600', 'shadow-[0_10px_20px_rgba(34,197,94,0.4)]');
                btn.classList.add('bg-red-500', 'hover:bg-red-600', 'shadow-[0_10px_20px_rgba(239,68,68,0.4)]');
                text.innerText = 'Finalizar Rota';
                status.innerHTML = 'Em rota';
                icon.innerHTML = `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/>`;
            } else {
                btn.classList.remove('bg-red-500', 'hover:bg-red-600', 'shadow-[0_10px_20px_rgba(239,68,68,0.4)]');
                btn.classList.add('bg-green-500', 'hover:bg-green-600', 'shadow-[0_10px_20px_rgba(34,197,94,0.4)]');
                text.innerText = 'Iniciar Rota';
                status.innerHTML = configurado ? 'Aguardando inicio' : 'Defina a linha do dia';
                icon.innerHTML = `<path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>`;
            }
        }

        atualizarBotaoEstado();

        if (!configurado) {
            setTimeout(() => abrirConfiguracaoViagem(true), 300);
        }

        setInterval(function() {
            fetch('<?= BASE_URL ?>/api-pontos')
                .then(response => response.json())
                .then(pontosAtualizados => {
                    if (!pontosAtualizados.error) {
                        desenharPontos(pontosAtualizados);
                    }
                })
                .catch(erro => console.error('Erro ao atualizar o mapa:', erro));
        }, 10000);

        function toggleRota() {
            if (!configurado) {
                abrirConfiguracaoViagem(true);
                return;
            }

            if (!emViagem) {
                fetch('<?= BASE_URL ?>/iniciar-rota', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            emViagem = true;
                            atualizarBotaoEstado();
                            Swal.fire({ title: 'Rota iniciada!', text: 'Linha do dia configurada e rota liberada.', icon: 'success', timer: 2000, showConfirmButton: false });
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    })
                    .catch(erro => console.error('Erro ao iniciar a rota:', erro));
            } else {
                Swal.fire({
                    title: 'Finalizar rota?',
                    text: 'Voce chegou ao destino final da viagem?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4A7DDF',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, finalizar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('<?= BASE_URL ?>/finalizar-rota', { method: 'POST' })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Finalizada!', 'A rota foi concluida com sucesso.', 'success')
                                        .then(() => window.location.reload());
                                } else {
                                    Swal.fire('Ops!', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Ocorreu um erro', error.message, 'error');
                            });
                    }
                });
            }
        }
    </script>
</body>
</html>
