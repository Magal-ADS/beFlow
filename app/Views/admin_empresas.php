<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Empresas</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/assets/branding/icone.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">
    <?php
    $currentSection = 'empresas';
    $totalEmpresas = count($empresas ?? []);
    $totalUsuariosEmpresas = array_sum(array_map(static function ($empresa) {
        return (int) ($empresa['total_usuarios'] ?? 0);
    }, $empresas ?? []));
    $totalLinhasEmpresas = array_sum(array_map(static function ($empresa) {
        return (int) ($empresa['total_linhas'] ?? 0);
    }, $empresas ?? []));
    $totalVeiculosEmpresas = array_sum(array_map(static function ($empresa) {
        return (int) ($empresa['total_veiculos'] ?? 0);
    }, $empresas ?? []));

    $formatCnpj = static function ($cnpj) {
        $digits = preg_replace('/\D+/', '', (string) $cnpj);
        if (strlen($digits) !== 14) {
            return (string) $cnpj;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits);
    };
    ?>

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto min-w-0">
        <header class="sticky top-0 z-30 lg:hidden bg-gray-50/95 backdrop-blur border-b border-gray-200 px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Empresas</h1>
                <p class="text-xs text-gray-500">BeFlow Admin</p>
            </div>
            <button type="button" onclick="toggleAdminSidebar(true)" class="w-11 h-11 rounded-2xl bg-white shadow-sm border border-gray-200 text-gray-700 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-4 sm:p-6 lg:p-10">
            <div class="bg-white rounded-[32px] border border-gray-100 shadow-sm p-5 sm:p-6 lg:p-8">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                    <div class="max-w-3xl">
                        <p class="text-[11px] uppercase tracking-[0.28em] text-slate-400 font-bold">Institucional</p>
                        <h2 class="mt-3 text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Empresas de transporte</h2>
                        <p class="mt-3 text-sm lg:text-base text-slate-500">Centralize o cadastro das empresas que operam no sistema e acompanhe rapidamente usuarios, linhas e frota vinculados.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full xl:w-auto">
                        <div class="relative w-full sm:min-w-[280px]">
                            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" id="buscaEmpresa" onkeyup="filtrarEmpresas()" placeholder="Buscar por nome, CNPJ ou telefone..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                        </div>
                        <?php if (!empty($canManageCompanies)): ?>
                            <button type="button" onclick="abrirModalEmpresa()" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-5 py-3 font-bold shadow-lg shadow-slate-200 hover:bg-slate-800 transition">
                                + Nova Empresa
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Empresas</p>
                        <p class="mt-3 text-4xl font-black text-slate-900"><?= $totalEmpresas ?></p>
                    </div>
                    <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Usuarios</p>
                        <p class="mt-3 text-4xl font-black text-slate-900"><?= $totalUsuariosEmpresas ?></p>
                    </div>
                    <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Linhas</p>
                        <p class="mt-3 text-4xl font-black text-slate-900"><?= $totalLinhasEmpresas ?></p>
                    </div>
                    <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Veiculos</p>
                        <p class="mt-3 text-4xl font-black text-slate-900"><?= $totalVeiculosEmpresas ?></p>
                    </div>
                </div>

                <?php if (empty($canManageCompanies)): ?>
                    <div class="mt-6 rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                        Somente o Administrador Geral pode cadastrar, editar e excluir empresas. Esta tela fica disponivel para consulta no perfil atual.
                    </div>
                <?php endif; ?>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-4" id="listaEmpresas">
                    <?php if (!empty($empresas)): ?>
                        <?php foreach ($empresas as $empresa): ?>
                            <?php
                            $searchData = strtolower(trim(($empresa['nome'] ?? '') . ' ' . ($empresa['cnpj'] ?? '') . ' ' . ($empresa['telefone'] ?? '')));
                            $payload = htmlspecialchars(json_encode($empresa), ENT_QUOTES, 'UTF-8');
                            ?>
                            <article class="card-empresa rounded-[28px] border border-gray-100 bg-white p-5 lg:p-6 shadow-sm hover:shadow-lg transition" data-search="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400 font-bold">Empresa #<?= (int) $empresa['id'] ?></p>
                                        <h3 class="mt-3 text-2xl font-black text-slate-900 break-words"><?= htmlspecialchars($empresa['nome']) ?></h3>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-sm font-bold border border-blue-100">
                                                CNPJ <?= htmlspecialchars($formatCnpj($empresa['cnpj'])) ?>
                                            </span>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-sm font-bold border border-slate-200">
                                                <?= htmlspecialchars(($empresa['telefone'] ?? '') !== '' ? $empresa['telefone'] : 'Sem telefone') ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if (!empty($canManageCompanies)): ?>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" onclick="abrirModalEmpresa(<?= $payload ?>)" class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center hover:bg-blue-100 transition" title="Editar empresa">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button type="button" onclick="excluirEmpresa(<?= (int) $empresa['id'] ?>)" class="w-11 h-11 rounded-2xl bg-red-50 text-red-500 border border-red-100 flex items-center justify-center hover:bg-red-100 transition" title="Excluir empresa">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-6 grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Usuarios</p>
                                        <p class="mt-2 text-2xl font-black text-slate-900"><?= (int) $empresa['total_usuarios'] ?></p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Linhas</p>
                                        <p class="mt-2 text-2xl font-black text-slate-900"><?= (int) $empresa['total_linhas'] ?></p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Frota</p>
                                        <p class="mt-2 text-2xl font-black text-slate-900"><?= (int) $empresa['total_veiculos'] ?></p>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="lg:col-span-2 2xl:col-span-3 rounded-[28px] border-2 border-dashed border-gray-200 bg-slate-50 p-12 text-center">
                            <p class="text-xl font-black text-slate-700">Nenhuma empresa cadastrada.</p>
                            <p class="text-sm text-gray-500 mt-2">Cadastre a primeira empresa para organizar usuarios, linhas e frota na plataforma.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if (!empty($canManageCompanies)): ?>
        <div id="modalEmpresa" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                <h3 id="tituloModalEmpresa" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Nova Empresa</h3>
                <form id="formEmpresa" class="space-y-4">
                    <input type="hidden" name="id" id="empresaId">
                    <div>
                        <label for="empresaNome" class="block text-sm font-bold text-gray-600 mb-2">Nome da empresa</label>
                        <input type="text" name="nome" id="empresaNome" placeholder="Ex: Viacao Centro Oeste" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="empresaCnpj" class="block text-sm font-bold text-gray-600 mb-2">CNPJ</label>
                            <input type="text" name="cnpj" id="empresaCnpj" placeholder="00.000.000/0000-00" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" maxlength="18" required>
                        </div>
                        <div>
                            <label for="empresaTelefone" class="block text-sm font-bold text-gray-600 mb-2">Telefone</label>
                            <input type="text" name="telefone" id="empresaTelefone" placeholder="(16) 99999-9999" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" maxlength="15">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="fecharModalEmpresa()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                        <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-slate-900 text-white shadow-lg shadow-slate-200 active:scale-95 transition">Salvar Empresa</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function filtrarEmpresas() {
            const termo = document.getElementById('buscaEmpresa').value.toLowerCase();
            document.querySelectorAll('.card-empresa').forEach(card => {
                card.style.display = card.getAttribute('data-search').includes(termo) ? 'block' : 'none';
            });
        }

        function formatCnpj(value) {
            const digits = String(value || '').replace(/\D/g, '').slice(0, 14);
            if (digits.length <= 2) return digits;
            if (digits.length <= 5) return digits.replace(/^(\d{2})(\d+)/, '$1.$2');
            if (digits.length <= 8) return digits.replace(/^(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
            if (digits.length <= 12) return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
            return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
        }

        function formatPhone(value) {
            const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return digits.replace(/^(\d{2})(\d+)/, '($1) $2');
            if (digits.length <= 10) return digits.replace(/^(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
            return digits.replace(/^(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
        }

        function abrirModalEmpresa(empresa = null) {
            const form = document.getElementById('formEmpresa');
            form.reset();

            document.getElementById('empresaId').value = empresa && empresa.id ? empresa.id : '';
            document.getElementById('empresaNome').value = empresa && empresa.nome ? empresa.nome : '';
            document.getElementById('empresaCnpj').value = empresa && empresa.cnpj ? formatCnpj(empresa.cnpj) : '';
            document.getElementById('empresaTelefone').value = empresa && empresa.telefone ? formatPhone(empresa.telefone) : '';
            document.getElementById('tituloModalEmpresa').innerText = empresa && empresa.id ? 'Editar Empresa' : 'Nova Empresa';
            document.getElementById('modalEmpresa').classList.replace('hidden', 'flex');
        }

        function fecharModalEmpresa() {
            const modal = document.getElementById('modalEmpresa');
            if (modal) {
                modal.classList.replace('flex', 'hidden');
            }
        }

        const inputEmpresaCnpj = document.getElementById('empresaCnpj');
        if (inputEmpresaCnpj) {
            inputEmpresaCnpj.addEventListener('input', function () {
                this.value = formatCnpj(this.value);
            });
        }

        const inputEmpresaTelefone = document.getElementById('empresaTelefone');
        if (inputEmpresaTelefone) {
            inputEmpresaTelefone.addEventListener('input', function () {
                this.value = formatPhone(this.value);
            });
        }

        const formEmpresa = document.getElementById('formEmpresa');
        if (formEmpresa) {
            formEmpresa.addEventListener('submit', function (e) {
                e.preventDefault();

                fetch('<?= BASE_URL ?>/admin/salvar-empresa', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                        return;
                    }

                    Swal.fire('Erro', data.message, 'error');
                });
            });
        }

        function excluirEmpresa(id) {
            Swal.fire({
                title: 'Excluir empresa?',
                text: 'A exclusao so sera permitida se nao houver usuarios, linhas ou veiculos vinculados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sim, excluir'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const fd = new FormData();
                fd.append('id', id);

                fetch('<?= BASE_URL ?>/admin/deletar-empresa', {
                    method: 'POST',
                    body: fd
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                        return;
                    }

                    Swal.fire('Erro', data.message, 'error');
                });
            });
        }
    </script>
</body>
</html>
