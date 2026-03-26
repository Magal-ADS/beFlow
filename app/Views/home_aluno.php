<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow - Início</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        #map { height: 100%; width: 100%; z-index: 0; }
        .leaflet-control-container { z-index: 5 !important; }
        /* Customizando a barra de rolagem da lista de pontos */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #4A7DDF; border-radius: 10px; }
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
        <div class="flex gap-2 mb-6">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" placeholder="Buscar no mapa..." class="w-full pl-12 pr-4 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button class="w-14 h-14 bg-blue-400 text-white rounded-2xl flex items-center justify-center shadow-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
        </div>

        <h3 class="font-semibold text-gray-700 mb-4 text-center">Selecione seu ponto de embarque:</h3>
        
        <div class="space-y-3 max-h-48 overflow-y-auto pr-2 pb-4 custom-scroll">
            <?php if (empty($pontos)): ?>
                <p class="text-center text-gray-400 text-sm py-4">Nenhum ponto encontrado.</p>
            <?php else: ?>
                <?php foreach ($pontos as $p): ?>
                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-2xl bg-white shadow-sm hover:border-blue-200 transition">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="bg-blue-500 text-white text-[10px] font-bold px-2 py-1 rounded-md uppercase">
                                <?= htmlspecialchars($p['nome_linha'] ?? 'Linha'); ?>
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($p['nome']); ?></p>
                        <p class="text-xs text-gray-400 italic">Ponto de parada oficial</p>
                    </div>
                    <button onclick="confirmarPonto(<?= $p['id']; ?>, this)" class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-md active:scale-95 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-40 hidden opacity-0 transition-opacity duration-300" onclick="toggleSidebar()"></div>
    <div id="sidebarMenu" class="fixed top-0 left-0 h-full w-[85%] max-w-[320px] bg-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out rounded-r-[2.5rem] shadow-2xl flex flex-col">
        <button id="closeSidebarBtn" onclick="toggleSidebar()" class="absolute top-6 -right-10 bg-[#4A7DDF] text-white p-2 rounded-r-xl shadow-md opacity-0 transition-opacity duration-300 pointer-events-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <div class="p-8 flex flex-col items-center">
            <div class="w-20 h-20 bg-gray-200 rounded-full mb-4 overflow-hidden border-2 border-gray-100">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario_nome']); ?>&background=random" alt="Avatar">
            </div>
            <h2 class="text-xl font-bold text-gray-800 text-center">👋 Olá, <?= htmlspecialchars($_SESSION['usuario_nome']); ?>!</h2>
            <button class="mt-6 w-full bg-[#4A7DDF] text-white text-sm font-semibold py-3 rounded-xl shadow-md hover:bg-blue-600 transition">Personalizar Perfil</button>
        </div>

        <div class="flex-1 px-4 space-y-2 mt-2">
            <a href="#" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl font-medium transition">Minhas Viagens</a>
            <a href="/beFlow/logout" class="flex items-center gap-4 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl font-medium mt-auto mb-8 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sair
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
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

        // --- MAPA ---
        var map = L.map('map', { zoomControl: false }).setView([-21.4059, -48.5052], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        map.locate({setView: true, maxZoom: 16});
        map.on('locationfound', function(e) {
            L.circleMarker(e.latlng, {
                radius: 8, fillColor: "#4A7DDF", color: "#fff", weight: 3, opacity: 1, fillOpacity: 1
            }).addTo(map).bindPopup("Você está aqui").openPopup();
        });

        // --- PONTOS DO BANCO ---
        var pontosDoBanco = <?= json_encode($pontos); ?>;
        var busIcon = L.divIcon({
            html: `<div style="background-color: #4A7DDF; padding: 6px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                    <svg style="width: 14px; height: 14px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                   </div>`,
            className: '', iconSize: [28, 28], iconAnchor: [14, 14]
        });

        pontosDoBanco.forEach(function(ponto) {
            if(ponto.latitude && ponto.longitude) {
                L.marker([ponto.latitude, ponto.longitude], {icon: busIcon})
                 .addTo(map)
                 .bindPopup(`<strong>${ponto.nome}</strong><br><span style="color: #666;">${ponto.nome_linha || 'Linha BeFlow'}</span>`);
            }
        });

        // --- CONFIRMAÇÃO COM SWEETALERT2 ---
        function confirmarPonto(pontoId, elemento) {
            fetch('confirmar-presenca', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ponto_id: pontoId })
            })
            .then(async response => {
                const text = await response.text();
                try { return JSON.parse(text); } 
                catch (e) { throw new Error("O servidor enviou um erro em vez de JSON."); }
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Confirmado!', text: '👋 O motorista já recebeu seu aviso de embarque.',
                        icon: 'success', confirmButtonColor: '#4A7DDF', confirmButtonText: 'Beleza!'
                    });
                    elemento.classList.remove('bg-blue-600');
                    elemento.classList.add('bg-green-500');
                    elemento.onclick = null;
                } else {
                    Swal.fire({ title: 'Ops!', text: data.message, icon: 'error', confirmButtonColor: '#4A7DDF' });
                }
            })
            .catch(error => {
                Swal.fire({ title: 'Erro de Servidor', text: 'Não foi possível confirmar.', icon: 'warning', confirmButtonColor: '#4A7DDF' });
            });
        }

        // ========================================================================
        // --- SISTEMA DE NOTIFICAÇÃO DO ALUNO (Consertado) ---
        // ========================================================================
        
        var notificadoSaida = false;
        var notificadoChegada = false;
        var ultimoStatus = 'aguardando';

        // O que acontece quando o aluno clica no sininho
        function mostrarNotificacao() {
            if (ultimoStatus === 'em_rota') {
                Swal.fire({ title: '🚌 O ônibus já saiu!', text: 'Fique atento ao seu ponto de embarque.', icon: 'info', confirmButtonColor: '#4A7DDF' });
            } else if (ultimoStatus === 'finalizada') {
                Swal.fire({ title: '✅ Viagem Concluída', text: 'O ônibus já finalizou a rota de hoje.', icon: 'success', confirmButtonColor: '#4A7DDF' });
            } else {
                Swal.fire({ title: 'Sem Novidades', text: 'O ônibus ainda não iniciou a rota.', icon: 'question', confirmButtonColor: '#4A7DDF' });
            }
            document.getElementById('bolinhaNotificacao').classList.add('hidden');
        }

        // Fica checando o status no servidor a cada 10 segundos
        setInterval(function() {
            fetch('/beFlow/status-viagem')
            .then(response => response.json())
            .then(data => {
                ultimoStatus = data.status;

                // 1. Ônibus acabou de SAIR
                if (data.status === 'em_rota' && !notificadoSaida) {
                    notificadoSaida = true; // Não repete o alerta de saída
                    notificadoChegada = false; // Reseta o de chegada
                    
                    document.getElementById('bolinhaNotificacao').classList.remove('hidden');
                    document.getElementById('bolinhaEstatica').classList.remove('hidden');

                    Swal.fire({
                        title: 'O ônibus saiu! 🚌',
                        text: 'O motorista acabou de iniciar a rota. Dirija-se ao seu ponto.',
                        iconHtml: '<img src="https://media.giphy.com/media/l0HlU5b1A0rXgPzH2/giphy.gif" style="width: 150px; border-radius: 50%;">',
                        customClass: { icon: 'no-border' },
                        confirmButtonColor: '#4A7DDF',
                        confirmButtonText: 'Beleza, tô indo!'
                    });
                }
                
                // 2. Ônibus CHEGOU / FINALIZOU
                else if (data.status === 'finalizada' && !notificadoChegada && notificadoSaida) {
                    notificadoChegada = true; // Não repete o alerta de chegada
                    notificadoSaida = false; // Reseta o de saída para amanhã
                    
                    document.getElementById('bolinhaNotificacao').classList.add('hidden');
                    document.getElementById('bolinhaEstatica').classList.add('hidden');

                    Swal.fire({
                        title: 'Viagem Finalizada ✅',
                        text: 'O motorista encerrou a rota.',
                        icon: 'info',
                        confirmButtonColor: '#4A7DDF',
                        confirmButtonText: 'Ok'
                    });
                }
            })
            .catch(erro => console.error("Erro ao buscar status:", erro));
        }, 10000); // Checa a cada 10 segundos
    </script>
</body>
</html>