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
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ef4444;
            color: white;
            font-size: 12px;
            font-weight: bold;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="bg-slate-100 overflow-hidden">

    <div class="absolute top-0 left-0 right-0 p-4 z-[1000] pointer-events-none">
        <div class="bg-white/95 backdrop-blur rounded-[2rem] shadow-xl p-4 max-w-md mx-auto pointer-events-auto">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" onclick="toggleSidebar()" class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center md:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div class="w-11 h-11 bg-[#4A7DDF] rounded-2xl flex items-center justify-center text-white font-bold shadow-sm shrink-0">
                        <?= substr($_SESSION['usuario_nome'], 0, 1); ?>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-black text-slate-900 truncate"><?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></h1>
                        <p class="text-[11px] uppercase tracking-[0.2em] text-blue-600 font-bold" id="statusMotorista"></p>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-2">
                    <button onclick="abrirConfiguracaoViagem(false)" class="px-4 py-2 rounded-full bg-blue-50 text-blue-600 border border-blue-100 text-sm font-semibold">
                        Configurar
                    </button>
                    <a href="<?= BASE_URL ?>/logout" class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-[1100] transition-opacity duration-300 hidden opacity-0" onclick="toggleSidebar()"></div>
    <aside id="sidebarMenu" class="fixed top-0 left-0 h-full w-[84%] max-w-sm bg-white rounded-r-[2rem] shadow-2xl z-[1200] transition-transform duration-300 ease-in-out -translate-x-full flex flex-col overflow-hidden">
        <div class="flex-1 min-h-0 overflow-y-auto p-6 pb-36">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Motorista</p>
                <h2 class="text-xl font-black text-slate-900"><?= htmlspecialchars($currentMotorista['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Motorista')) ?></h2>
                <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars(($currentMotorista['telefone'] ?? '') !== '' ? $currentMotorista['telefone'] : ($currentMotorista['email'] ?? 'Sem contato')) ?></p>
            </div>
            <button type="button" onclick="toggleSidebar()" class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="mt-8 space-y-3">
            <div class="rounded-[1.5rem] bg-slate-50 border border-slate-200 p-4">
                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Linha</p>
                <p id="linhaAtualBadge" class="text-base font-black text-slate-900 mt-2 truncate">
                    <?= !empty($viagemAtual['nome_linha']) ? htmlspecialchars($viagemAtual['nome_linha']) : 'Nao definida' ?>
                </p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 border border-slate-200 p-4">
                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Onibus</p>
                <p id="numeroOnibusBadge" class="text-base font-black text-slate-900 mt-2">
                    <?= !empty($viagemAtual['numero_onibus']) ? htmlspecialchars($viagemAtual['numero_onibus']) : '--' ?>
                </p>
            </div>
            <button type="button" onclick="abrirConfiguracaoViagem(false); toggleSidebar();" class="w-full rounded-2xl bg-blue-50 text-blue-700 border border-blue-100 px-4 py-4 text-left font-bold">
                Configurar viagem
            </button>
            <button type="button" id="btnEncerrarDia" onclick="confirmarEncerrarDia()" class="w-full rounded-2xl bg-slate-900 text-white px-4 py-4 text-left font-bold disabled:opacity-40 disabled:cursor-not-allowed">
                Encerrar dia
                <p class="text-sm text-slate-300 font-medium mt-1">Salva a viagem depois que a chegada ja foi finalizada.</p>
            </button>
        </div>

        <div class="mt-6 rounded-[1.5rem] bg-slate-50 border border-slate-200 p-4">
            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Retorno confirmado</p>
            <p class="text-3xl font-black text-slate-900 mt-2" id="contadorRetornoSidebar">0</p>
            <p class="text-sm text-slate-500 mt-1">Atualiza automaticamente para voce acompanhar no celular.</p>
        </div>
        </div>

        <div class="absolute bottom-0 left-0 right-0 p-6 bg-white border-t border-slate-100">
            <a href="<?= BASE_URL ?>/logout" class="w-full rounded-2xl bg-[#A53F3F] text-white px-4 py-4 flex items-center justify-center gap-3 font-bold shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <div class="absolute bottom-8 left-0 right-0 flex justify-center z-[1000] pointer-events-none px-4">
        <button id="btnRota" onclick="toggleRota()" class="w-full max-w-sm pointer-events-auto bg-green-500 text-white font-bold py-4 px-6 rounded-full shadow-[0_10px_20px_rgba(34,197,94,0.4)] transform transition active:scale-95 flex items-center justify-center gap-2 text-lg disabled:bg-slate-300 disabled:shadow-none disabled:text-slate-600 disabled:cursor-not-allowed">
            <svg id="iconRota" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"></svg>
            <span id="textRota">Iniciar rota</span>
        </button>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const linhasDisponiveis = <?= json_encode($linhasDisponiveis, JSON_UNESCAPED_UNICODE) ?>;
        const viagemAtual = <?= json_encode($viagemAtual, JSON_UNESCAPED_UNICODE) ?>;
        let configurado = !!(viagemAtual && viagemAtual.linha_id && viagemAtual.numero_onibus);
        let viagemStatus = viagemAtual && viagemAtual.status ? viagemAtual.status : 'aguardando';
        let linhaAtualId = viagemAtual && viagemAtual.linha_id ? Number(viagemAtual.linha_id) : null;
        let linhaAtualNome = viagemAtual && viagemAtual.nome_linha ? viagemAtual.nome_linha : '';
        let numeroOnibusAtual = viagemAtual && viagemAtual.numero_onibus ? viagemAtual.numero_onibus : '';

        const map = L.map('map', { zoomControl: false }).setView([-21.4059, -48.5052], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

        const marcadoresLayer = L.layerGroup().addTo(map);
        let marcadorMotorista = null;

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        function getStatusLabel() {
            if (!configurado) {
                return 'Defina a linha do dia';
            }
            if (viagemStatus === 'em_rota') {
                return 'Em rota';
            }
            if (viagemStatus === 'aguardando_encerramento') {
                return 'Chegada finalizada';
            }
            if (viagemStatus === 'finalizada') {
                return 'Dia encerrado';
            }
            return 'Aguardando inicio';
        }

        function atualizarCabecalhoViagem() {
            const linhaBadge = document.getElementById('linhaAtualBadge');
            const numeroOnibusBadge = document.getElementById('numeroOnibusBadge');
            const statusMotorista = document.getElementById('statusMotorista');

            if (linhaBadge) {
                linhaBadge.innerText = linhaAtualNome || 'Nao definida';
            }

            if (numeroOnibusBadge) {
                numeroOnibusBadge.innerText = numeroOnibusAtual || '--';
            }

            if (statusMotorista) {
                statusMotorista.innerText = getStatusLabel();
            }
        }

        function atualizarContadorRetorno(pontosArray) {
            const totalRetorno = pontosArray.reduce((total, ponto) => total + Number(ponto.total_retorno || 0), 0);
            const contadorRetornoSidebar = document.getElementById('contadorRetornoSidebar');

            if (contadorRetornoSidebar) {
                contadorRetornoSidebar.innerText = totalRetorno;
            }
        }

        function desenharPontos(pontosArray) {
            marcadoresLayer.clearLayers();
            atualizarContadorRetorno(pontosArray);

            pontosArray.forEach(function (ponto) {
                const cor = Number(ponto.total_alunos || 0) > 0 ? '#ef4444' : '#4A7DDF';
                const busIcon = L.divIcon({
                    html: `
                        <div style="background-color: ${cor}; padding: 7px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); position: relative;">
                            <svg style="width: 14px; height: 14px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            ${Number(ponto.total_alunos || 0) > 0 ? `<div class="count-badge">${ponto.total_alunos}</div>` : ''}
                        </div>`,
                    className: '',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const marker = L.marker([ponto.latitude, ponto.longitude], { icon: busIcon })
                    .bindPopup(`<b>${ponto.nome}</b><br>${ponto.nome_linha}${ponto.horario_aproximado ? `<br>Horario aprox.: ${String(ponto.horario_aproximado).slice(0, 5)}` : ''}<br>${ponto.total_alunos} alunos aguardando.<br>${ponto.total_retorno || 0} alunos confirmaram volta.`);
                marcadoresLayer.addLayer(marker);
            });
        }

        function atualizarBotaoEstado() {
            const btn = document.getElementById('btnRota');
            const icon = document.getElementById('iconRota');
            const text = document.getElementById('textRota');
            const btnEncerrarDia = document.getElementById('btnEncerrarDia');

            btn.disabled = false;

            if (viagemStatus === 'em_rota') {
                btn.className = 'w-full max-w-sm pointer-events-auto bg-red-500 text-white font-bold py-4 px-6 rounded-full shadow-[0_10px_20px_rgba(239,68,68,0.4)] transform transition active:scale-95 flex items-center justify-center gap-2 text-lg disabled:bg-slate-300 disabled:shadow-none disabled:text-slate-600 disabled:cursor-not-allowed';
                text.innerText = 'Finalizar chegada';
                icon.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/>';
            } else if (viagemStatus === 'aguardando_encerramento') {
                btn.disabled = true;
                btn.className = 'w-full max-w-sm pointer-events-auto bg-slate-300 text-slate-600 font-bold py-4 px-6 rounded-full shadow-none transform transition active:scale-95 flex items-center justify-center gap-2 text-lg disabled:bg-slate-300 disabled:shadow-none disabled:text-slate-600 disabled:cursor-not-allowed';
                text.innerText = 'Chegada registrada';
                icon.innerHTML = '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 111.414-1.414l2.543 2.543 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/>';
            } else if (viagemStatus === 'finalizada') {
                btn.disabled = true;
                btn.className = 'w-full max-w-sm pointer-events-auto bg-slate-300 text-slate-600 font-bold py-4 px-6 rounded-full shadow-none transform transition active:scale-95 flex items-center justify-center gap-2 text-lg disabled:bg-slate-300 disabled:shadow-none disabled:text-slate-600 disabled:cursor-not-allowed';
                text.innerText = 'Dia encerrado';
                icon.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
            } else {
                btn.className = 'w-full max-w-sm pointer-events-auto bg-green-500 text-white font-bold py-4 px-6 rounded-full shadow-[0_10px_20px_rgba(34,197,94,0.4)] transform transition active:scale-95 flex items-center justify-center gap-2 text-lg disabled:bg-slate-300 disabled:shadow-none disabled:text-slate-600 disabled:cursor-not-allowed';
                text.innerText = 'Iniciar rota';
                icon.innerHTML = '<path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>';
            }

            btnEncerrarDia.disabled = viagemStatus !== 'aguardando_encerramento';
            atualizarCabecalhoViagem();
        }

        function abrirConfiguracaoViagem(forcarAbertura) {
            const options = linhasDisponiveis.map(function (linha) {
                const selected = linhaAtualId !== null && Number(linha.id) === Number(linhaAtualId) ? 'selected' : '';
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
                            <input id="swalOnibus" class="swal2-input !w-full !m-0" placeholder="Ex: 302" value="${numeroOnibusAtual}">
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
                    linhaAtualId = Number(result.value.linhaId);
                    linhaAtualNome = linhaSelecionada ? linhaSelecionada.nome : 'Linha definida';
                    numeroOnibusAtual = result.value.numeroOnibus;
                    configurado = true;
                    if (viagemStatus === 'finalizada') {
                        viagemStatus = 'aguardando';
                    }
                    atualizarBotaoEstado();
                    atualizarPontos();
                } else if (forcarAbertura) {
                    setTimeout(() => abrirConfiguracaoViagem(true), 200);
                }
            });
        }

        function atualizarPontos() {
            fetch('<?= BASE_URL ?>/api-pontos')
                .then(response => response.json())
                .then(pontosAtualizados => {
                    if (!pontosAtualizados.error) {
                        desenharPontos(Array.isArray(pontosAtualizados) ? pontosAtualizados : []);
                    }
                })
                .catch(erro => console.error('Erro ao atualizar o mapa:', erro));
        }

        function toggleRota() {
            if (!configurado) {
                abrirConfiguracaoViagem(true);
                return;
            }

            if (viagemStatus === 'aguardando') {
                fetch('<?= BASE_URL ?>/iniciar-rota', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viagemStatus = 'em_rota';
                            atualizarBotaoEstado();
                            Swal.fire({ title: 'Rota iniciada', text: 'A viagem saiu da cidade.', icon: 'success', timer: 1800, showConfirmButton: false });
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    })
                    .catch(erro => console.error('Erro ao iniciar a rota:', erro));
                return;
            }

            if (viagemStatus !== 'em_rota') {
                return;
            }

            Swal.fire({
                title: 'Finalizar chegada?',
                text: 'Confirma que voce chegou na outra cidade?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4A7DDF',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, cheguei',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch('<?= BASE_URL ?>/finalizar-rota', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viagemStatus = 'aguardando_encerramento';
                            atualizarBotaoEstado();
                            atualizarPontos();
                            Swal.fire('Chegada registrada', 'Agora acompanhe na tela quantos alunos vao voltar e encerre o dia pela sidebar.', 'success');
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Ocorreu um erro', error.message, 'error');
                    });
            });
        }

        function confirmarEncerrarDia() {
            if (viagemStatus !== 'aguardando_encerramento') {
                return;
            }

            Swal.fire({
                title: 'Encerrar o dia?',
                text: 'Isso conclui a viagem do dia e salva o fechamento.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#111827',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Encerrar dia',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch('<?= BASE_URL ?>/encerrar-dia', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viagemStatus = 'finalizada';
                            atualizarBotaoEstado();
                            toggleSidebar();
                            Swal.fire('Dia encerrado', data.message, 'success');
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Ocorreu um erro', error.message, 'error');
                    });
            });
        }

        const pontosIniciais = <?= json_encode($pontos, JSON_UNESCAPED_UNICODE) ?>;
        desenharPontos(Array.isArray(pontosIniciais) ? pontosIniciais : []);
        atualizarBotaoEstado();

        if (!configurado) {
            setTimeout(() => abrirConfiguracaoViagem(true), 300);
        }

        map.locate({ setView: true, maxZoom: 15 });
        map.on('locationfound', function (e) {
            if (marcadorMotorista) {
                map.removeLayer(marcadorMotorista);
            }

            marcadorMotorista = L.circleMarker(e.latlng, {
                radius: 8,
                fillColor: '#22c55e',
                color: '#fff',
                weight: 3,
                opacity: 1,
                fillOpacity: 1
            }).addTo(map).bindPopup('Seu onibus');
        });

        setInterval(atualizarPontos, 10000);
    </script>
</body>
</html>
