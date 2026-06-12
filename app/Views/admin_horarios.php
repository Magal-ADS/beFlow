<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Horarios</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/assets/branding/icone.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto">
        <header class="sticky top-0 z-30 lg:hidden bg-gray-50/95 backdrop-blur border-b border-gray-200 px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Horarios das Linhas</h1>
                <p class="text-xs text-gray-500">BeFlow Admin</p>
            </div>
            <button type="button" onclick="toggleAdminSidebar(true)" class="w-11 h-11 rounded-2xl bg-white shadow-sm border border-gray-200 text-gray-700 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-4 sm:p-6 lg:p-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <h2 class="text-3xl font-black text-gray-800 tracking-tighter">Horarios das Linhas</h2>
                    <p class="text-gray-500">Gerencie os horarios em tabela, com filtros por linha e turno.</p>
                </div>

                <div class="flex items-center gap-4 w-full md:w-auto flex-wrap">
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="inputPesquisaHorario" onkeyup="filtrarHorarios()" placeholder="Buscar horario..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    </div>

                    <select id="filtroLinhaHorario" onchange="filtrarHorarios()" class="w-full md:w-56 px-4 py-3 rounded-2xl bg-white border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 text-gray-600 font-bold">
                        <option value="">Todas as linhas</option>
                        <?php foreach ($linhas as $linha): ?>
                            <option value="<?= strtolower($linha['nome']) ?>"><?= htmlspecialchars($linha['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="filtroTurnoHorario" onchange="filtrarHorarios()" class="w-full md:w-44 px-4 py-3 rounded-2xl bg-white border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 text-gray-600 font-bold">
                        <option value="">Todos os turnos</option>
                        <option value="matutino">Matutino</option>
                        <option value="vespertino">Vespertino</option>
                        <option value="noturno">Noturno</option>
                    </select>

                    <button onclick="abrirModalHorario()" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition shrink-0 active:scale-95">
                        + Novo Horario
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left" id="tabelaHorarios">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Linha</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Turno</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Hora de Ida</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Hora de Volta</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($horarios)): ?>
                            <?php foreach ($horarios as $horario): ?>
                                <tr class="hover:bg-gray-50 transition linha-horario" data-linha="<?= strtolower($horario['linha_nome']) ?>" data-turno="<?= strtolower($horario['turno']) ?>">
                                    <td class="px-6 py-4 font-bold text-gray-700"><?= htmlspecialchars($horario['linha_nome']) ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($horario['turno']) ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars(substr($horario['hora_ida'], 0, 5)) ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars(substr($horario['hora_volta'], 0, 5)) ?></td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-3">
                                        <button onclick='abrirModalHorario(<?= json_encode($horario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)' class="text-blue-400 hover:text-blue-600 transition" title="Editar Horario">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button onclick="deletarHorario(<?= (int) $horario['id'] ?>)" class="text-gray-300 hover:text-red-500 transition" title="Excluir Horario">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 font-bold italic">Nenhum horario encontrado na base.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalHorario" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
            <h3 id="tituloModalHorario" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Novo Horario</h3>

            <form id="formHorario" class="space-y-4">
                <input type="hidden" name="id" id="horarioId">

                <select id="horarioLinhaId" name="linha_id" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none font-bold text-gray-500" required>
                    <option value="">Selecione a Linha</option>
                    <?php foreach ($linhas as $linha): ?>
                        <option value="<?= (int) $linha['id'] ?>"><?= htmlspecialchars($linha['nome']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="horarioTurno" name="turno" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none font-bold text-gray-500" required>
                    <option value="Matutino">Matutino</option>
                    <option value="Vespertino">Vespertino</option>
                    <option value="Noturno">Noturno</option>
                </select>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="horarioHoraIda" class="block text-sm font-bold text-gray-600 mb-2">Hora de Ida</label>
                        <input type="time" id="horarioHoraIda" name="hora_ida" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none" step="60" required>
                    </div>
                    <div>
                        <label for="horarioHoraVolta" class="block text-sm font-bold text-gray-600 mb-2">Hora de Volta</label>
                        <input type="time" id="horarioHoraVolta" name="hora_volta" class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-blue-400 outline-none" step="60" required>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="fecharModalHorario()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-blue-600 text-white shadow-lg shadow-blue-200 active:scale-95 transition">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filtrarHorarios() {
            const busca = document.getElementById('inputPesquisaHorario').value.toLowerCase();
            const linhaSelecionada = document.getElementById('filtroLinhaHorario').value.toLowerCase();
            const turnoSelecionado = document.getElementById('filtroTurnoHorario').value.toLowerCase();

            document.querySelectorAll('.linha-horario').forEach(function (linha) {
                const texto = linha.innerText.toLowerCase();
                const nomeLinha = linha.getAttribute('data-linha') || '';
                const turno = linha.getAttribute('data-turno') || '';
                const matchBusca = texto.includes(busca);
                const matchLinha = linhaSelecionada === '' || nomeLinha === linhaSelecionada;
                const matchTurno = turnoSelecionado === '' || turno === turnoSelecionado;

                linha.style.display = matchBusca && matchLinha && matchTurno ? '' : 'none';
            });
        }

        function abrirModalHorario(dados = null) {
            const form = document.getElementById('formHorario');
            form.reset();

            const horario = dados || {};
            document.getElementById('horarioId').value = horario.id || '';
            document.getElementById('horarioLinhaId').value = horario.linha_id || '';
            document.getElementById('horarioTurno').value = horario.turno || 'Matutino';
            document.getElementById('horarioHoraIda').value = horario.hora_ida ? String(horario.hora_ida).slice(0, 5) : '';
            document.getElementById('horarioHoraVolta').value = horario.hora_volta ? String(horario.hora_volta).slice(0, 5) : '';
            document.getElementById('tituloModalHorario').innerText = horario.id ? 'Editar Horario' : 'Novo Horario';
            document.getElementById('modalHorario').classList.replace('hidden', 'flex');
        }

        function fecharModalHorario() {
            document.getElementById('modalHorario').classList.replace('flex', 'hidden');
        }

        document.getElementById('formHorario').onsubmit = function (e) {
            e.preventDefault();
            fetch('<?= BASE_URL ?>/admin/salvar-horario', { method: 'POST', body: new FormData(this) })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', confirmButtonColor: '#2563eb' })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                });
        };

        function deletarHorario(id) {
            Swal.fire({
                title: 'Excluir horario?',
                text: 'Esta acao removera este horario da linha.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('<?= BASE_URL ?>/admin/deletar-horario', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Excluido!', data.message, 'success').then(() => location.reload());
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
