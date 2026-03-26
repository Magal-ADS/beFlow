<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-800 tracking-tighter">Gerenciar Usuários</h2>
                <p class="text-gray-500">Listagem de alunos e motoristas cadastrados.</p>
            </div>
            
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="inputPesquisa" onkeyup="filtrarTabela()" placeholder="Buscar usuário..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                </div>
                
                <button onclick="abrirModal('novo')" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition shrink-0 active:scale-95">
                    + Novo Usuário
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left" id="tabelaUsuarios">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Nome</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">E-mail</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Tipo</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(!empty($listaUsuarios)): ?>
                        <?php foreach($listaUsuarios as $u): ?>
                        <tr class="hover:bg-gray-50 transition linha-usuario">
                            <td class="px-6 py-4 font-bold text-gray-700"><?= htmlspecialchars($u['nome']) ?></td>
                            <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider 
                                    <?= $u['tipo_usuario'] == 'motorista' ? 'bg-green-100 text-green-600' : ($u['tipo_usuario'] == 'aluno' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600') ?>">
                                    <?= htmlspecialchars($u['tipo_usuario']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-3">
                                <button onclick="abrirModal('editar', <?= $u['id'] ?>, '<?= addslashes($u['nome']) ?>', '<?= addslashes($u['email']) ?>', '<?= $u['tipo_usuario'] ?>')" class="text-blue-400 hover:text-blue-600 transition" title="Editar Usuário">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button onclick="deletarUsuario(<?= $u['id'] ?>)" class="text-gray-300 hover:text-red-500 transition" title="Excluir Usuário">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 font-bold italic">Nenhum usuário encontrado na base.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalUsuario" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
            <h3 id="tituloModal" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Novo Cadastro</h3>
            
            <form id="formUsuario" class="space-y-4">
                <input type="hidden" name="id" id="inputId">
                <input type="hidden" name="acao" id="inputAcao" value="novo">

                <input type="text" id="inputNome" name="nome" placeholder="Nome Completo" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none" required>
                <input type="email" id="inputEmail" name="email" placeholder="E-mail" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none" required>
                
                <div>
                    <input type="password" id="inputSenha" name="senha" placeholder="Senha" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none">
                    <p id="dicaSenha" class="text-xs text-gray-400 mt-1 hidden ml-2">*Deixe em branco para manter a senha atual.</p>
                </div>

                <select id="inputTipo" name="tipo_usuario" onchange="toggleCamposAluno()" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none font-bold text-gray-500">
                    <option value="aluno">Aluno</option>
                    <option value="motorista">Motorista</option>
                    <option value="admin_empresa">Admin Empresa</option>
                </select>

                <div id="camposAluno" class="space-y-4 pt-2">
                    <input type="text" name="escola" placeholder="Escola / Instituição" class="w-full p-4 rounded-2xl bg-blue-50/50 border border-blue-100 focus:ring-2 focus:ring-blue-400 outline-none">
                    <select name="turno" class="w-full p-4 rounded-2xl bg-blue-50/50 border border-blue-100 focus:ring-2 focus:ring-blue-400 outline-none">
                        <option value="Matutino">Matutino</option>
                        <option value="Vespertino">Vespertino</option>
                        <option value="Noturno">Noturno</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="fecharModal()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-blue-600 text-white shadow-lg shadow-blue-200 active:scale-95 transition">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- FILTRO DE PESQUISA ---
        function filtrarTabela() {
            let input = document.getElementById("inputPesquisa").value.toLowerCase();
            let linhas = document.querySelectorAll(".linha-usuario");
            linhas.forEach(linha => {
                let texto = linha.innerText.toLowerCase();
                linha.style.display = texto.includes(input) ? "" : "none";
            });
        }

        // --- EXIBIR CAMPOS DE ALUNO ---
        function toggleCamposAluno() {
            const tipo = document.getElementById('inputTipo').value;
            const campos = document.getElementById('camposAluno');
            campos.style.display = (tipo === 'aluno') ? 'block' : 'none';
        }

        // --- CONTROLE DO MODAL ---
        function abrirModal(modo, id = '', nome = '', email = '', tipo = '') {
            const modal = document.getElementById('modalUsuario');
            const titulo = document.getElementById('tituloModal');
            const form = document.getElementById('formUsuario');
            const acao = document.getElementById('inputAcao');
            const inputSenha = document.getElementById('inputSenha');
            const dicaSenha = document.getElementById('dicaSenha');

            form.reset();

            if (modo === 'editar') {
                titulo.innerText = "Editar Usuário";
                acao.value = "editar";
                document.getElementById('inputId').value = id;
                document.getElementById('inputNome').value = nome;
                document.getElementById('inputEmail').value = email;
                document.getElementById('inputTipo').value = tipo;
                inputSenha.required = false;
                dicaSenha.classList.remove('hidden');
            } else {
                titulo.innerText = "Novo Cadastro";
                acao.value = "novo";
                document.getElementById('inputId').value = '';
                inputSenha.required = true;
                dicaSenha.classList.add('hidden');
            }

            toggleCamposAluno();
            modal.classList.replace('hidden', 'flex');
        }

        function fecharModal() { 
            document.getElementById('modalUsuario').classList.replace('flex', 'hidden'); 
        }

        // --- SALVAR (AJAX) ---
        document.getElementById('formUsuario').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const rota = formData.get('acao') === 'editar' ? '/beFlow/admin/editar-usuario' : '/beFlow/admin/salvar-usuario';

            fetch(rota, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', confirmButtonColor: '#2563eb' })
                    .then(() => location.reload());
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            });
        };

        // --- EXCLUIR ---
        function deletarUsuario(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação é irreversível e removerá todos os dados vinculados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('/beFlow/admin/deletar-usuario', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Excluído!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Erro', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>