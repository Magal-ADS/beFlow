<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Rotas e Pontos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-800 tracking-tighter">Rotas e Pontos</h2>
                <p class="text-gray-500">Gerencie as linhas de ônibus e as paradas do trajeto.</p>
            </div>
            
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="buscaRota" onkeyup="filtrarRotas()" placeholder="Buscar rota..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                </div>
                <button onclick="abrirModalLinha()" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition shrink-0 active:scale-95">
                    + Nova Rota
                </button>
            </div>
        </div>

        <div class="space-y-8" id="containerRotas">
            <?php if(!empty($linhas_com_pontos)): ?>
                <?php foreach($linhas_com_pontos as $linha): ?>
                
                <div class="card-rota bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8" data-nome="<?= strtolower($linha['nome']) ?>">
                    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center font-black text-2xl shadow-inner">
                                🚌
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-800 tracking-tight"><?= htmlspecialchars($linha['nome']) ?></h3>
                                <div class="flex gap-3 mt-1 items-center">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest bg-gray-50 px-2 py-1 rounded-md">ID: #<?= $linha['id'] ?></p>
                                    <button onclick="abrirModalLinha(<?= $linha['id'] ?>, '<?= addslashes($linha['nome']) ?>')" class="text-xs font-bold text-blue-500 hover:underline">Editar Nome</button>
                                    <button onclick="excluirLinha(<?= $linha['id'] ?>)" class="text-xs font-bold text-red-400 hover:underline">Excluir Rota</button>
                                </div>
                            </div>
                        </div>
                        <button onclick="abrirModalPonto(null, <?= $linha['id'] ?>)" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition bg-blue-50 hover:bg-blue-100 px-5 py-3 rounded-2xl border border-blue-100">
                            + Adicionar Ponto
                        </button>
                    </div>

                    <div class="pl-4">
                        <?php if(!empty($linha['pontos'])): ?>
                            <div class="relative border-l-4 border-blue-50 ml-4 space-y-8 pb-4">
                                <?php foreach($linha['pontos'] as $ponto): ?>
                                    <div class="relative pl-10">
                                        <div class="absolute -left-[14px] top-2 w-6 h-6 bg-white border-4 border-blue-500 rounded-full shadow-sm"></div>
                                        
                                        <div class="bg-gray-50 p-5 rounded-[1.5rem] border border-gray-100 hover:bg-white hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 group">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <h4 class="font-black text-gray-800 text-lg flex items-center gap-3">
                                                        <span class="bg-blue-600 text-white text-[10px] px-2 py-1 rounded-lg uppercase tracking-tighter">Parada <?= $ponto['ordem_na_linha'] ?></span>
                                                        <?= htmlspecialchars($ponto['nome']) ?>
                                                    </h4>
                                                    <div class="flex items-center gap-3 mt-2">
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-white px-2 py-1 rounded-md border border-gray-100">Lat: <?= $ponto['latitude'] ?></p>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-white px-2 py-1 rounded-md border border-gray-100">Lng: <?= $ponto['longitude'] ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                                    <button onclick='abrirModalPonto(<?= json_encode($ponto) ?>)' class="p-3 text-blue-400 hover:bg-blue-50 rounded-xl transition" title="Editar Ponto">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </button>
                                                    <button onclick="excluirPonto(<?= $ponto['id'] ?>)" class="p-3 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition" title="Excluir Ponto">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-10 bg-gray-50 rounded-[2rem] border-2 border-dashed border-gray-200">
                                <p class="text-gray-400 font-bold italic">Nenhum ponto de parada cadastrado.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-24 bg-white rounded-[3rem] border border-gray-100 shadow-sm">
                    <div class="text-6xl mb-4">📍</div>
                    <p class="text-gray-500 font-black text-xl tracking-tighter">Nenhuma rota encontrada para esta empresa.</p>
                    <p class="text-gray-400 text-sm mt-1">Comece clicando em "+ Nova Rota" acima.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="modalLinha" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
            <h3 id="tituloModalLinha" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Nova Rota</h3>
            <form id="formLinha" class="space-y-4">
                <input type="hidden" name="id" id="linhaId">
                <input type="text" name="nome" id="linhaNome" placeholder="Ex: Linha 404 - Noturno" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="fecharModais()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-blue-600 text-white shadow-lg shadow-blue-200 active:scale-95 transition">Salvar Rota</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalPonto" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
            <h3 id="tituloModalPonto" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Ponto de Parada</h3>
            <form id="formPonto" class="space-y-4">
                <input type="hidden" name="id" id="pontoId">
                <input type="hidden" name="linha_id" id="pontoLinhaId">
                <input type="text" name="nome" id="pontoNome" placeholder="Nome do Ponto" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="latitude" id="pontoLat" placeholder="Latitude (-21.4...)" class="p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                    <input type="text" name="longitude" id="pontoLng" placeholder="Longitude (-48.5...)" class="p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <input type="number" name="ordem" id="pontoOrdem" placeholder="Ordem na Rota (Ex: 1, 2, 3)" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="fecharModais()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-blue-600 text-white shadow-lg shadow-blue-200 active:scale-95 transition">Salvar Ponto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- FILTRAR ROTAS ---
        function filtrarRotas() {
            let busca = document.getElementById('buscaRota').value.toLowerCase();
            document.querySelectorAll('.card-rota').forEach(card => {
                card.style.display = card.getAttribute('data-nome').includes(busca) ? 'block' : 'none';
            });
        }

        // --- CONTROLE DOS MODAIS ---
        function abrirModalLinha(id = '', nome = '') {
            document.getElementById('linhaId').value = id;
            document.getElementById('linhaNome').value = nome;
            document.getElementById('tituloModalLinha').innerText = id ? 'Editar Rota' : 'Nova Rota';
            document.getElementById('modalLinha').classList.replace('hidden', 'flex');
        }

        function abrirModalPonto(dados = null, linhaId = '') {
            if (dados) {
                document.getElementById('pontoId').value = dados.id;
                document.getElementById('pontoLinhaId').value = dados.linha_id;
                document.getElementById('pontoNome').value = dados.nome;
                document.getElementById('pontoLat').value = dados.latitude;
                document.getElementById('pontoLng').value = dados.longitude;
                document.getElementById('pontoOrdem').value = dados.ordem_na_linha;
                document.getElementById('tituloModalPonto').innerText = 'Editar Ponto';
            } else {
                document.getElementById('formPonto').reset();
                document.getElementById('pontoId').value = '';
                document.getElementById('pontoLinhaId').value = linhaId;
                document.getElementById('tituloModalPonto').innerText = 'Novo Ponto';
            }
            document.getElementById('modalPonto').classList.replace('hidden', 'flex');
        }

        function fecharModais() { 
            document.querySelectorAll('#modalLinha, #modalPonto').forEach(m => m.classList.replace('flex', 'hidden')); 
        }

        // --- REQUISIÇÕES AJAX (SALVAR) ---
        document.getElementById('formLinha').onsubmit = function(e) {
            e.preventDefault();
            fetch('/beFlow/admin/salvar-linha', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { location.reload(); } 
                else { Swal.fire('Erro', data.message, 'error'); }
            });
        }

        document.getElementById('formPonto').onsubmit = function(e) {
            e.preventDefault();
            fetch('/beFlow/admin/salvar-ponto', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { location.reload(); } 
                else { Swal.fire('Erro', data.message, 'error'); }
            });
        }

        // --- EXCLUSÃO (SWEET ALERT) ---
        function excluirLinha(id) {
            Swal.fire({
                title: 'Deletar Rota?',
                text: "Isso vai excluir a rota e TODOS os pontos vinculados a ela!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, excluir'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('id', id);
                    fetch('/beFlow/admin/deletar-linha', { method: 'POST', body: fd })
                    .then(res => res.json()).then(() => location.reload());
                }
            });
        }

        function excluirPonto(id) {
            Swal.fire({
                title: 'Deletar Ponto?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, excluir'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('id', id);
                    fetch('/beFlow/admin/deletar-ponto', { method: 'POST', body: fd })
                    .then(res => res.json()).then(() => location.reload());
                }
            });
        }
    </script>
</body>
</html>