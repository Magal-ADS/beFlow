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
        <div class="bg-white/90 backdrop-blur p-4 rounded-2xl shadow-lg flex justify-between items-center max-w-lg mx-auto pointer-events-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#4A7DDF] rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                    <?= substr($_SESSION['usuario_nome'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-800">Olá, <?= explode(' ', $_SESSION['usuario_nome'])[0]; ?>!</h1>
                    <p class="text-[10px] text-blue-500 uppercase font-bold tracking-wider" id="statusMotorista">Aguardando Início 🟡</p>
                </div>
            </div>
            <a href="/beFlow/logout" class="p-2 text-gray-400 hover:text-red-500 transition">
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
        var map = L.map('map', { zoomControl: false }).setView([-21.4059, -48.5052], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

        var marcadoresLayer = L.layerGroup().addTo(map);
        var emViagem = false; // Controle de estado da viagem

        // FUNÇÃO PARA DESENHAR OS PONTOS
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
                var marker = L.marker([ponto.latitude, ponto.longitude], {icon: busIcon}).bindPopup(`<b>${ponto.nome}</b><br>${ponto.total_alunos} alunos aguardando.`);
                marcadoresLayer.addLayer(marker);
            });
        }

        var pontosIniciais = <?= json_encode($pontos); ?>;
        desenharPontos(pontosIniciais);

        map.locate({setView: true, maxZoom: 15});
        map.on('locationfound', function(e) {
            L.circleMarker(e.latlng, { radius: 8, fillColor: "#22c55e", color: "#fff", weight: 3, opacity: 1, fillOpacity: 1 }).addTo(map).bindPopup("Seu ônibus").openPopup();
        });

        // Auto-refresh a cada 10 segundos
        setInterval(function() {
            fetch('/beFlow/api-pontos')
            .then(response => response.json())
            .then(pontosAtualizados => { if(!pontosAtualizados.error) { desenharPontos(pontosAtualizados); } })
            .catch(erro => console.error("Erro ao atualizar o mapa:", erro));
        }, 10000);

        // --- LÓGICA DO BOTÃO DE ROTA (AGORA COM PROTEÇÃO DE ERRO E FETCH DE INÍCIO) ---
        function toggleRota() {
            const btn = document.getElementById('btnRota');
            const icon = document.getElementById('iconRota');
            const text = document.getElementById('textRota');
            const status = document.getElementById('statusMotorista');

            if (!emViagem) {
                // AQUI ESTÁ O CONSERTO: Agora ele avisa o banco que a rota iniciou!
                fetch('/beFlow/iniciar-rota', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // INICIAR ROTA
                        emViagem = true;
                        btn.classList.replace('bg-green-500', 'bg-red-500');
                        btn.classList.replace('hover:bg-green-600', 'hover:bg-red-600');
                        btn.classList.replace('shadow-[0_10px_20px_rgba(34,197,94,0.4)]', 'shadow-[0_10px_20px_rgba(239,68,68,0.4)]');
                        
                        text.innerText = "Finalizar Rota";
                        status.innerHTML = "Em Rota <span class='animate-pulse'>🟢</span>";
                        icon.innerHTML = `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/>`;
                        
                        Swal.fire({ title: 'Rota Iniciada!', text: 'Dirija com segurança.', icon: 'success', timer: 2000, showConfirmButton: false });
                    }
                })
                .catch(erro => console.error("Erro ao iniciar a rota:", erro));
            } else {
                // FINALIZAR ROTA
                Swal.fire({
                    title: 'Finalizar Rota?',
                    text: "Você chegou ao destino final da viagem?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4A7DDF',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, finalizar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        
                        // Caminho absoluto para garantir que acha a rota certa
                        fetch('/beFlow/finalizar-rota', { method: 'POST' })
                        .then(async response => {
                            const text = await response.text();
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error("===========================================");
                                console.error("O PHP QUEBROU! VEJA O ERRO ABAIXO:");
                                console.error(text);
                                console.error("===========================================");
                                throw new Error("Erro no backend! Aperte F12 e veja o Console vermelho.");
                            }
                        })
                        .then(data => {
                            if(data.success) {
                                Swal.fire('Finalizada!', 'A rota foi concluída com sucesso.', 'success')
                                .then(() => window.location.reload());
                            } else {
                                Swal.fire('Ops!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Ocorreu um Erro', error.message, 'error');
                        });
                    }
                });
            }
        }
    </script>
</body>
</html>