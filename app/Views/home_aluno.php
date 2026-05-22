<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow - Inicio</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        #map { height: 100%; width: 100%; z-index: 0; }
        .leaflet-control-container { z-index: 5 !important; }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #4A7DDF; border-radius: 10px; }
        .bottom-sheet-toggle-icon { transition: transform 0.3s ease; }
        .bottom-sheet-conteudo { transition: max-height 0.35s ease, opacity 0.3s ease; }
        .bottom-sheet-handle {
            width: 40px;
            height: 5px;
            display: block;
            background-color: #cbd5e1;
            border-radius: 9999px;
            margin: 0 auto 15px auto;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
        }
        .bottom-sheet-pontos .bottom-sheet-conteudo {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            margin-top: 0;
            padding-bottom: 0;
        }
        .bottom-sheet-pontos.aberto .bottom-sheet-conteudo {
            max-height: 500px;
            overflow: visible;
            opacity: 1;
        }
        @media (max-width: 767px) {
            .bottom-sheet-pontos.aberto .bottom-sheet-conteudo {
                max-height: 500px;
            }
        }
    </style>
</head>
<body class="h-screen w-full relative font-sans overflow-hidden bg-gray-100">

    <div id="map" class="absolute inset-0"></div>

    <div class="absolute top-0 left-0 right-0 p-6 flex justify-between items-center z-10 pointer-events-none">
        <button onclick="toggleSidebar()" class="w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-md text-blue-500 pointer-events-auto hover:bg-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>

        <button id="btnNotificacao" onclick="mostrarNotificacao()" class="w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-md text-red-500 relative pointer-events-auto hover:bg-white transition">
            <span id="bolinhaNotificacao" class="hidden absolute top-2 right-2 w-2 h-2 bg-red-600 rounded-full animate-ping"></span>
            <span id="bolinhaEstatica" class="hidden absolute top-2 right-2 w-2 h-2 bg-red-600 rounded-full"></span>

            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </button>
    </div>

    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[2.5rem] p-6 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] z-20 transition-transform duration-300" id="bottomSheet">
        <button type="button" id="bottomSheetHandle" class="w-full flex justify-center items-center gap-2 mb-4 cursor-pointer" aria-label="Expandir ou minimizar painel" aria-expanded="true">
            <span class="bottom-sheet-handle"></span>
            <svg id="bottomSheetToggleIcon" class="bottom-sheet-toggle-icon w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div class="flex gap-2 mb-6">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="buscarPonto" placeholder="Buscar ponto ou linha..." class="w-full pl-12 pr-4 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button class="w-14 h-14 bg-blue-400 text-white rounded-2xl flex items-center justify-center shadow-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
        </div>

        <div id="bottomSheetPontos" class="bottom-sheet-pontos">
            <div id="bottomSheetConteudo" class="bottom-sheet-conteudo">
                <h3 class="font-semibold text-gray-700 mb-4 text-center">Selecione seu ponto de embarque:</h3>
                <div id="painelEstadoAluno" class="hidden mb-4"></div>
                <div id="listaPontosWrapper">
                    <div id="listaPontos" class="space-y-3 max-h-48 overflow-y-auto pr-2 pb-4 custom-scroll">
                    <?php if (empty($pontos)): ?>
                        <p class="text-center text-gray-400 text-sm py-4">Nenhum ponto encontrado.</p>
                    <?php else: ?>
                        <?php foreach ($pontos as $p): ?>
                        <div class="card-ponto flex items-center justify-between p-4 border border-gray-100 rounded-2xl bg-white shadow-sm hover:border-blue-200 transition" data-ponto-id="<?= $p['id']; ?>" data-search="<?= htmlspecialchars(($p['nome'] ?? '') . ' ' . ($p['nome_linha'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="bg-blue-500 text-white text-[10px] font-bold px-2 py-1 rounded-md uppercase">
                                        <?= htmlspecialchars($p['nome_linha'] ?? 'Linha'); ?>
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($p['nome']); ?></p>
                                <p class="text-xs text-gray-400 italic">
                                    Ponto de parada oficial
                                    <?php if (!empty($p['horario_aproximado'])): ?>
                                        • Horario aprox.: <?= htmlspecialchars(substr($p['horario_aproximado'], 0, 5)); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button onclick="confirmarPonto(<?= $p['id']; ?>)" class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-md active:scale-95 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                    <p id="nenhumResultadoPontos" class="hidden text-center text-gray-400 text-sm py-4">Nenhum ponto corresponde a busca.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 hidden opacity-0" onclick="toggleSidebar()"></div>
    <div id="sidebarMenu" class="fixed top-0 left-0 h-full w-[80%] max-w-sm bg-white rounded-r-[2rem] shadow-2xl z-50 flex flex-col p-8 transition-transform duration-300 ease-in-out -translate-x-full">
        <button id="closeSidebarBtn" onclick="toggleSidebar()" class="absolute top-12 -right-5 w-10 h-10 bg-blue-500 text-white rounded-lg flex items-center justify-center shadow-md opacity-0 transition-opacity duration-300 pointer-events-none">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <div>
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 text-2xl mb-6 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>

            <h2 class="text-xl">
                <span class="text-gray-800">Olá, </span><span class="text-blue-600 font-black"><?= htmlspecialchars($currentAluno['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Aluno')) ?>!</span>
            </h2>
            <p class="text-sm font-black text-gray-900 tracking-wide mt-1"><?= htmlspecialchars(($currentAluno['telefone'] ?? '') !== '' ? $currentAluno['telefone'] : ($currentAluno['email'] ?? 'Sem contato')) ?></p>

            <div class="h-px w-full bg-gray-100 my-6"></div>
        </div>

        <div>
            <button type="button" class="w-full bg-blue-500 text-white rounded-xl py-3.5 flex items-center justify-center gap-3 font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-600 mb-6 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2.5 2.5 0 113.536 3.536L12.536 16.536A4 4 0 019.707 17.707L6 18l.293-3.707A4 4 0 017.464 11.464L14.232 4.696a2.5 2.5 0 013.536 0"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19a6 6 0 10-6-6"></path></svg>
                <span>Personalizar Perfil</span>
            </button>

            <div class="flex flex-col gap-6">
                <a href="#" class="flex items-center gap-4 text-gray-600 font-bold hover:text-blue-600 transition">
                    <svg class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Viagens</span>
                </a>
                <a href="#" class="flex items-center gap-4 text-gray-600 font-bold hover:text-blue-600 transition">
                    <svg class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 15v-3a8 8 0 0116 0v3"></path><path stroke-linecap="round" stroke-linejoin="round" d="M18 17a2 2 0 002-2v-1a2 2 0 00-2-2h-1v5h1zM6 17a2 2 0 01-2-2v-1a2 2 0 012-2h1v5H6z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M9 19h6"></path></svg>
                    <span>Ajuda</span>
                </a>
                <a href="#" class="flex items-center gap-4 text-gray-600 font-bold hover:text-blue-600 transition">
                    <svg class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-7 6l-4 1 1-4V6a2 2 0 012-2h14a2 2 0 012 2v9a2 2 0 01-2 2H8l-3 3z"></path></svg>
                    <span>Fale Conosco</span>
                </a>
            </div>
        </div>

        <div class="mt-auto">
            <a href="<?= BASE_URL ?>/logout" class="w-full bg-[#A53F3F] text-white rounded-xl py-3.5 flex items-center justify-center gap-3 font-bold shadow-md hover:bg-red-800 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H9m4 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Sair</span>
            </a>
            <p class="text-center text-[10px] text-gray-400 mt-4 font-medium">Termos de uso v1.1.1</p>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const estadoConfirmacaoInicial = <?= json_encode($estadoConfirmacao ?? null, JSON_UNESCAPED_UNICODE) ?>;

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const overlay = document.getElementById('sidebarOverlay');
            const bottomSheet = document.getElementById('bottomSheet');
            const closeBtn = document.getElementById('closeSidebarBtn');

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                bottomSheet.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                    closeBtn.classList.remove('opacity-0');
                    closeBtn.classList.remove('pointer-events-none');
                }, 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                closeBtn.classList.add('opacity-0');
                closeBtn.classList.add('pointer-events-none');
                bottomSheet.style.transform = 'translateY(0)';
                setTimeout(() => { overlay.classList.add('hidden'); }, 300);
            }
        }

        (function () {
            const container = document.getElementById('bottomSheetPontos');
            const handle = document.getElementById('bottomSheetHandle');
            const chevronDownPath = 'M19 9l-7 7-7-7';
            const chevronUpPath = 'M5 15l7-7 7 7';
            const iconPath = document.querySelector('#bottomSheetToggleIcon path');

            function setExpanded(expanded) {
                container.classList.toggle('aberto', expanded);
                handle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                handle.setAttribute('aria-label', expanded ? 'Minimizar painel' : 'Expandir painel');
                iconPath.setAttribute('d', expanded ? chevronDownPath : chevronUpPath);
            }

            function togglePanel() {
                setExpanded(!container.classList.contains('aberto'));
            }

            handle.addEventListener('click', togglePanel);
            setExpanded(true);
        })();

        var map = L.map('map', { zoomControl: false }).setView([-21.4059, -48.5052], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        map.locate({ setView: true, maxZoom: 16 });
        map.on('locationfound', function (e) {
            L.circleMarker(e.latlng, {
                radius: 8, fillColor: '#4A7DDF', color: '#fff', weight: 3, opacity: 1, fillOpacity: 1
            }).addTo(map).bindPopup('Voce esta aqui').openPopup();
        });

        var pontosDoBanco = <?= json_encode($pontos); ?>;
        var buscaInput = document.getElementById('buscarPonto');
        var cardsPontos = Array.from(document.querySelectorAll('.card-ponto'));
        var nenhumResultadoPontos = document.getElementById('nenhumResultadoPontos');
        var listaPontosWrapper = document.getElementById('listaPontosWrapper');
        var painelEstadoAluno = document.getElementById('painelEstadoAluno');
        var estadoConfirmacaoAtual = estadoConfirmacaoInicial;
        var busIcon = L.divIcon({
            html: `<div style="background-color: #4A7DDF; padding: 6px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                    <svg style="width: 14px; height: 14px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                   </div>`,
            className: '', iconSize: [28, 28], iconAnchor: [14, 14]
        });
        var marcadoresPorPonto = {};

        pontosDoBanco.forEach(function (ponto) {
            if (ponto.latitude && ponto.longitude) {
                marcadoresPorPonto[ponto.id] = L.marker([ponto.latitude, ponto.longitude], { icon: busIcon })
                    .addTo(map)
                    .bindPopup(`<strong>${ponto.nome}</strong><br><span style="color: #666;">${ponto.nome_linha || 'Linha BeFlow'}</span>${ponto.horario_aproximado ? `<br><span style="color: #b45309;">Horario aprox.: ${String(ponto.horario_aproximado).slice(0, 5)}</span>` : ''}`);
            }
        });

        function filtrarPontos() {
            if (estadoConfirmacaoAtual) {
                return;
            }

            var termo = (buscaInput.value || '').trim().toLowerCase();
            var idsVisiveis = [];

            cardsPontos.forEach(function (card) {
                var textoBusca = (card.dataset.search || '').toLowerCase();
                var pontoId = card.dataset.pontoId;
                var corresponde = termo === '' || textoBusca.indexOf(termo) !== -1;

                card.classList.toggle('hidden', !corresponde);

                if (marcadoresPorPonto[pontoId]) {
                    if (corresponde) {
                        marcadoresPorPonto[pontoId].addTo(map);
                    } else {
                        map.removeLayer(marcadoresPorPonto[pontoId]);
                    }
                }

                if (corresponde && marcadoresPorPonto[pontoId]) {
                    idsVisiveis.push(pontoId);
                }
            });

            nenhumResultadoPontos.classList.toggle('hidden', cardsPontos.some(function (card) {
                return !card.classList.contains('hidden');
            }));

            if (idsVisiveis.length === 1) {
                map.setView(marcadoresPorPonto[idsVisiveis[0]].getLatLng(), 16);
            }
        }

        buscaInput.addEventListener('input', filtrarPontos);

        function limparNotificacao() {
            document.getElementById('bolinhaNotificacao').classList.add('hidden');
            document.getElementById('bolinhaEstatica').classList.add('hidden');
        }

        function enviarAcaoConfirmacao(payload) {
            return fetch('confirmar-presenca', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('O servidor enviou um erro em vez de JSON.');
                }
            });
        }

        function getResumoEstado(tipo) {
            if (tipo === 'embarque') {
                return 'Sua presenca neste ponto foi confirmada.';
            }
            if (tipo === 'embarcado') {
                return 'Embarque informado. Agora diga se voce vai voltar de onibus.';
            }
            if (tipo === 'retorno_sim') {
                return 'Seu retorno de onibus foi registrado e sera contabilizado.';
            }
            if (tipo === 'retorno_nao') {
                return 'Voce informou que nao vai voltar de onibus.';
            }
            return '';
        }

        function renderizarEstadoAluno() {
            if (!estadoConfirmacaoAtual) {
                painelEstadoAluno.classList.add('hidden');
                painelEstadoAluno.innerHTML = '';
                listaPontosWrapper.classList.remove('hidden');
                filtrarPontos();
                return;
            }

            const pontoNome = estadoConfirmacaoAtual.ponto_nome || 'Ponto selecionado';
            const linhaNome = estadoConfirmacaoAtual.linha_nome || 'Linha ativa';
            const resumo = getResumoEstado(estadoConfirmacaoAtual.tipo);
            let acoes = '';

            if (estadoConfirmacaoAtual.tipo === 'embarque') {
                acoes = `
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="button" onclick="cancelarPresenca()" class="rounded-2xl border border-red-200 text-red-600 font-bold py-3 hover:bg-red-50 transition">Cancelar</button>
                        <button type="button" onclick="informarEmbarque()" class="rounded-2xl bg-blue-600 text-white font-bold py-3 shadow-md hover:bg-blue-700 transition">Estou no onibus</button>
                    </div>
                `;
            } else if (estadoConfirmacaoAtual.tipo === 'embarcado') {
                acoes = `
                    <div class="grid grid-cols-1 gap-3 mt-4">
                        <button type="button" onclick="informarRetorno(true)" class="rounded-2xl bg-blue-600 text-white font-bold py-3 shadow-md hover:bg-blue-700 transition">Vou voltar de onibus</button>
                        <button type="button" onclick="informarRetorno(false)" class="rounded-2xl border border-gray-200 text-gray-700 font-bold py-3 hover:bg-gray-50 transition">Nao vou voltar de onibus</button>
                    </div>
                `;
            } else if (estadoConfirmacaoAtual.tipo === 'retorno_sim' || estadoConfirmacaoAtual.tipo === 'retorno_nao') {
                acoes = `
                    <div class="mt-4">
                        <button type="button" onclick="informarRetorno(${estadoConfirmacaoAtual.tipo === 'retorno_nao' ? 'true' : 'false'})" class="w-full rounded-2xl border border-gray-200 text-gray-700 font-bold py-3 hover:bg-gray-50 transition">
                            ${estadoConfirmacaoAtual.tipo === 'retorno_sim' ? 'Nao vou voltar de onibus' : 'Vou voltar de onibus'}
                        </button>
                    </div>
                `;
            }

            painelEstadoAluno.innerHTML = `
                <div class="p-4 rounded-[1.5rem] border border-blue-100 bg-blue-50/70 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.2em] font-bold text-blue-600">${linhaNome}</p>
                            <h4 class="mt-1 text-lg font-black text-gray-900">${pontoNome}</h4>
                            <p class="mt-2 text-sm text-gray-600">${resumo}</p>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-white text-blue-600 flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                    ${acoes}
                </div>
            `;
            painelEstadoAluno.classList.remove('hidden');
            listaPontosWrapper.classList.add('hidden');
            nenhumResultadoPontos.classList.add('hidden');
        }

        function confirmarPonto(pontoId) {
            Swal.fire({
                title: 'Confirmar presenca?',
                text: 'Deseja confirmar sua presenca neste ponto de embarque?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, confirmar',
                cancelButtonText: 'Voltar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                enviarAcaoConfirmacao({ acao: 'confirmar_ponto', ponto_id: pontoId })
                    .then(data => {
                        limparNotificacao();

                        if (data.success) {
                            estadoConfirmacaoAtual = data.state || null;
                            renderizarEstadoAluno();
                            Swal.fire('Confirmado!', data.message, 'success');
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    })
                    .catch(() => {
                        limparNotificacao();
                        Swal.fire('Erro de Servidor', 'Nao foi possivel confirmar.', 'warning');
                    });
            });
        }

        function cancelarPresenca() {
            Swal.fire({
                title: 'Cancelar confirmacao?',
                text: 'Voce podera escolher outro ponto de embarque.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, cancelar',
                cancelButtonText: 'Voltar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                enviarAcaoConfirmacao({ acao: 'cancelar_ponto' })
                    .then(data => {
                        if (data.success) {
                            estadoConfirmacaoAtual = data.state || null;
                            renderizarEstadoAluno();
                            Swal.fire('Cancelado', data.message, 'success');
                        } else {
                            Swal.fire('Ops!', data.message, 'error');
                        }
                    });
            });
        }

        function informarEmbarque() {
            enviarAcaoConfirmacao({ acao: 'informar_embarque' })
                .then(data => {
                    if (data.success) {
                        estadoConfirmacaoAtual = data.state || null;
                        renderizarEstadoAluno();
                        Swal.fire('Perfeito', data.message, 'success');
                    } else {
                        Swal.fire('Ops!', data.message, 'error');
                    }
                });
        }

        function informarRetorno(vaiVoltar) {
            enviarAcaoConfirmacao({ acao: vaiVoltar ? 'retorno_sim' : 'retorno_nao' })
                .then(data => {
                    if (data.success) {
                        estadoConfirmacaoAtual = data.state || null;
                        renderizarEstadoAluno();
                        Swal.fire('Informacao salva', data.message, 'success');
                    } else {
                        Swal.fire('Ops!', data.message, 'error');
                    }
                });
        }

        renderizarEstadoAluno();

        var notificadoSaida = false;
        var notificadoChegada = false;
        var ultimoStatus = 'aguardando';

        function mostrarNotificacao() {
            if (ultimoStatus === 'em_rota') {
                Swal.fire({ title: 'O onibus ja saiu!', text: 'Fique atento ao seu ponto de embarque.', icon: 'info', confirmButtonColor: '#4A7DDF' });
            } else if (ultimoStatus === 'aguardando_encerramento' || ultimoStatus === 'finalizada') {
                Swal.fire({ title: 'Viagem Concluida', text: 'O onibus ja finalizou a rota de hoje.', icon: 'success', confirmButtonColor: '#4A7DDF' });
            } else {
                Swal.fire({ title: 'Sem Novidades', text: 'O onibus ainda nao iniciou a rota.', icon: 'question', confirmButtonColor: '#4A7DDF' });
            }
            limparNotificacao();
        }

        setInterval(function () {
            fetch('<?= BASE_URL ?>/status-viagem')
            .then(response => response.json())
            .then(data => {
                ultimoStatus = data.status;

                if (data.status === 'em_rota' && !notificadoSaida) {
                    notificadoSaida = true;
                    notificadoChegada = false;

                    document.getElementById('bolinhaNotificacao').classList.remove('hidden');
                    document.getElementById('bolinhaEstatica').classList.remove('hidden');

                    Swal.fire({
                        title: 'O onibus saiu!',
                        text: 'O motorista acabou de iniciar a rota. Dirija-se ao seu ponto.',
                        iconHtml: '<img src="https://media.giphy.com/media/l0HlU5b1A0rXgPzH2/giphy.gif" style="width: 150px; border-radius: 50%;">',
                        customClass: { icon: 'no-border' },
                        confirmButtonColor: '#4A7DDF',
                        confirmButtonText: 'Beleza, to indo!'
                    });
                } else if ((data.status === 'aguardando_encerramento' || data.status === 'finalizada') && !notificadoChegada && notificadoSaida) {
                    notificadoChegada = true;
                    notificadoSaida = false;

                    limparNotificacao();

                    Swal.fire({
                        title: 'Viagem Finalizada',
                        text: 'O motorista encerrou a rota.',
                        icon: 'info',
                        confirmButtonColor: '#4A7DDF',
                        confirmButtonText: 'Ok'
                    });
                }
            })
            .catch(erro => console.error('Erro ao buscar status:', erro));
        }, 10000);
    </script>
</body>
</html>
