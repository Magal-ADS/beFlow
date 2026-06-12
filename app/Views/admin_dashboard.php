<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/assets/branding/icone.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <?php
    $currentSection = $_GET['section'] ?? 'dashboard';

    function dashboardIcon($name) {
        switch ($name) {
            case 'users':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zm-8 0c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 2.239-5 5v2h10v-2c0-2.761-2.239-5-5-5zm8 0c-.697 0-1.359.117-1.975.332A6.979 6.979 0 0117 18v2h4v-2c0-2.761-2.239-5-5-5z"/></svg>';
            case 'bus':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17h10m-9 3h8m-9-6h10l1-6H6l1 6zm1-6V7a3 3 0 013-3h2a3 3 0 013 3v1"/><circle cx="9" cy="15" r="1.1" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1.1" fill="currentColor" stroke="none"/></svg>';
            case 'pin':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-6-4.35-6-10a6 6 0 1112 0c0 5.65-6 10-6 10z"/><circle cx="12" cy="11" r="2.5" fill="currentColor" stroke="none"/></svg>';
            case 'clock':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v5l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            default:
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21V7a2 2 0 012-2h4v16m0 0h4m-4 0v-6h4v6m0 0h4V11a2 2 0 00-2-2h-2"/></svg>';
        }
    }

    function activityAvatarClasses($type) {
        if ($type === 'line') {
            return 'bg-green-100 text-green-700';
        }
        if ($type === 'stop') {
            return 'bg-purple-100 text-purple-700';
        }
        return 'bg-blue-100 text-blue-700';
    }

    $maxValue = max(array_column($summarySeries, 'value'));
    $chartHeight = 320;
    $chartWidth = 800;
    $leftPadding = 40;
    $usableHeight = 210;
    $usableWidth = 700;
    $points = [];
    $steps = max(count($summarySeries) - 1, 1);

    foreach ($summarySeries as $index => $point) {
        $x = $leftPadding + ($usableWidth / $steps) * $index;
        $y = 250 - (($point['value'] / max($maxValue, 1)) * $usableHeight);
        $points[] = ['x' => round($x, 2), 'y' => round($y, 2), 'label' => $point['label'], 'value' => $point['value']];
    }

    $peakIndex = 0;
    foreach ($points as $index => $point) {
        if ($point['value'] > $points[$peakIndex]['value']) {
            $peakIndex = $index;
        }
    }

    $peak = $points[$peakIndex];
    $peakLabel = $points[$peakIndex]['label'];
    $polyline = implode(' ', array_map(function ($point) {
        return $point['x'] . ',' . $point['y'];
    }, $points));
    ?>

    <main class="flex-1 overflow-y-auto min-w-0">
        <header class="sticky top-0 z-30 lg:hidden bg-gray-50/95 backdrop-blur border-b border-gray-200 px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900"><?= $currentSection === 'veiculos' ? 'Veiculos' : 'Dashboard' ?></h1>
                <p class="text-xs text-gray-500">BeFlow Admin</p>
            </div>
            <button type="button" onclick="toggleAdminSidebar(true)" class="w-11 h-11 rounded-2xl bg-white shadow-sm border border-gray-200 text-gray-700 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <?php if ($currentSection === 'veiculos'): ?>
            <div class="p-4 sm:p-6 lg:p-10">
                <header class="hidden lg:block mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Veiculos</h1>
                    <p class="text-sm text-gray-500">Gerencie a frota separadamente das linhas e pontos.</p>
                </header>

                <section class="bg-white rounded-[28px] border border-gray-100 shadow-sm p-5 sm:p-6 lg:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                        <div class="max-w-2xl">
                            <p class="text-[11px] uppercase tracking-[0.28em] text-slate-400 font-bold">Frota</p>
                            <h2 class="mt-3 text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Cadastro de Veiculos</h2>
                            <p class="mt-3 text-sm lg:text-base text-slate-500">Use esta tela para organizar a frota da empresa antes da configuracao das viagens dos motoristas.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <a href="<?= BASE_URL ?>/admin/rotas" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 px-5 py-3 font-bold hover:bg-slate-50 transition">
                                Ver Linhas e Pontos
                            </a>
                            <button type="button" onclick="abrirModalVeiculo()" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-5 py-3 font-bold shadow-lg shadow-slate-200 hover:bg-slate-800 transition">
                                + Novo Veiculo
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Veiculos</p>
                            <p class="mt-3 text-4xl font-black text-slate-900"><?= count($veiculos ?? []) ?></p>
                        </div>
                        <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Linhas</p>
                            <p class="mt-3 text-4xl font-black text-slate-900"><?= count($linhas_com_pontos ?? []) ?></p>
                        </div>
                        <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Pontos</p>
                            <p class="mt-3 text-4xl font-black text-slate-900"><?= (int) ($stats['pontos'] ?? 0) ?></p>
                        </div>
                        <div class="rounded-[24px] bg-slate-50 border border-slate-100 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Motoristas</p>
                            <p class="mt-3 text-4xl font-black text-slate-900"><?= (int) ($stats['motoristas'] ?? 0) ?></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="relative max-w-xl">
                            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" id="buscaVeiculo" onkeyup="filtrarVeiculos()" placeholder="Buscar por placa ou identificador..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4" id="listaVeiculos">
                        <?php if (!empty($veiculos)): ?>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <article class="card-veiculo rounded-[26px] border border-gray-100 bg-slate-50 p-5 lg:p-6 hover:bg-white hover:shadow-lg transition" data-search="<?= strtolower(htmlspecialchars(($veiculo['numero_identificador'] ?? '') . ' ' . ($veiculo['placa'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400 font-bold">Veiculo</p>
                                            <h3 class="mt-3 text-2xl font-black text-slate-900 break-words"><?= htmlspecialchars($veiculo['numero_identificador']) ?></h3>
                                            <p class="mt-4 inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-sm font-bold border border-blue-100"><?= htmlspecialchars($veiculo['placa']) ?></p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" onclick="abrirModalVeiculo(<?= htmlspecialchars(json_encode($veiculo), ENT_QUOTES, 'UTF-8') ?>)" class="w-11 h-11 rounded-2xl bg-white text-blue-600 border border-blue-100 flex items-center justify-center hover:bg-blue-50 transition" title="Editar veiculo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button type="button" onclick="excluirVeiculo(<?= (int) $veiculo['id'] ?>)" class="w-11 h-11 rounded-2xl bg-white text-red-500 border border-red-100 flex items-center justify-center hover:bg-red-50 transition" title="Excluir veiculo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="md:col-span-2 2xl:col-span-3 rounded-[26px] border-2 border-dashed border-gray-200 bg-slate-50 p-10 text-center">
                                <p class="text-xl font-black text-slate-700">Nenhum veiculo cadastrado.</p>
                                <p class="text-sm text-gray-500 mt-2">Crie a frota da empresa para liberar a configuracao dos motoristas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php else: ?>
            <div class="p-4 sm:p-6 lg:p-10">
                <header class="hidden lg:block">
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500 mb-8">Visao geral do sistema BeFlow</p>
                </header>

                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <?php foreach ($dashboardCards as $card): ?>
                        <article class="bg-white rounded-[20px] p-5 lg:p-6 shadow-sm border border-gray-100 flex flex-col">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $card['icon_bg'] ?> <?= $card['icon_fg'] ?>">
                                <?= dashboardIcon($card['icon']) ?>
                            </div>
                            <p class="mt-6 lg:mt-8 text-sm text-gray-500"><?= htmlspecialchars($card['title']) ?></p>
                            <h2 class="mt-2 text-3xl lg:text-4xl font-bold text-gray-900 break-words"><?= htmlspecialchars((string) $card['value']) ?></h2>
                            <span class="mt-4 lg:mt-5 bg-green-100 text-green-600 text-[10px] px-2 py-1 rounded-full w-fit"><?= htmlspecialchars($card['change']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="grid grid-cols-1 xl:grid-cols-3 gap-4 lg:gap-6">
                    <article class="xl:col-span-2 bg-white rounded-[24px] p-5 lg:p-8 shadow-sm border border-gray-100 min-w-0">
                        <div class="mb-6">
                            <h3 class="text-lg lg:text-xl font-bold text-gray-900">Resumo do sistema</h3>
                            <p class="text-sm text-gray-500">Informacoes em tempo real</p>
                        </div>

                        <div class="relative h-72 lg:h-80 rounded-[20px] bg-gradient-to-t from-blue-50 to-transparent overflow-hidden">
                            <div class="absolute top-4 right-4 lg:top-14 lg:left-[53%] lg:right-auto lg:-translate-x-1/2 bg-blue-600 text-white text-xs lg:text-sm font-semibold px-3 py-2 rounded-full shadow-lg">
                                <?= htmlspecialchars($peakLabel) ?> <?= htmlspecialchars((string) $peak['value']) ?>
                            </div>

                            <div class="w-full h-full overflow-x-auto">
                                <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" class="min-w-[680px] w-full h-full">
                                    <path d="M<?= $polyline ?>" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <?php foreach ($points as $point): ?>
                                        <circle cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="6" fill="#2563eb"/>
                                    <?php endforeach; ?>
                                    <?php foreach ($points as $point): ?>
                                        <text x="<?= $point['x'] - 18 ?>" y="295" class="fill-gray-400 text-xs"><?= htmlspecialchars($point['label']) ?></text>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        </div>
                    </article>

                    <aside class="bg-white rounded-[24px] p-5 lg:p-8 shadow-sm border border-gray-100">
                        <div>
                            <h3 class="text-lg lg:text-xl font-bold text-gray-900">Atividade recente</h3>
                            <p class="text-sm text-gray-500">Ultimos registros no sistema</p>
                        </div>

                        <div class="flex flex-col gap-5 lg:gap-6 mt-6">
                            <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold shrink-0 <?= activityAvatarClasses($activity['type']) ?>">
                                        <?= htmlspecialchars($activity['avatar']) ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800">
                                            <?php if ($activity['type'] === 'line'): ?>
                                                Nova linha cadastrada
                                            <?php elseif ($activity['type'] === 'stop'): ?>
                                                Novo ponto registrado
                                            <?php else: ?>
                                                Novo usuario cadastrado
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-sm text-gray-400 break-words">
                                            <?php
                                            $parts = explode(' ', $activity['text']);
                                            echo htmlspecialchars(implode(' ', array_slice($parts, -3)));
                                            ?>
                                        </p>
                                    </div>
                                    <span class="text-[10px] lg:text-xs text-gray-400 uppercase tracking-wider shrink-0">ha 2h</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                </section>

                <section class="mt-6 lg:mt-8 grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6">
                    <article class="bg-white rounded-[24px] p-6 lg:p-8 shadow-sm border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.25em] text-gray-400 font-bold">Institucional</p>
                                <h3 class="text-2xl font-black text-gray-900 mt-2">Empresas</h3>
                                <p class="text-sm text-gray-500 mt-2">Gerencie os dados cadastrais das empresas que operam dentro da plataforma.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/empresas" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-5 py-3 font-bold shadow-lg shadow-slate-200 hover:bg-slate-800 transition">
                                Abrir tela
                            </a>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Empresas</p>
                                <p class="mt-3 text-3xl font-black text-slate-900"><?= (int) ($stats['empresas'] ?? 0) ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Usuarios</p>
                                <p class="mt-3 text-3xl font-black text-slate-900"><?= (int) ($stats['usuarios'] ?? 0) ?></p>
                            </div>
                        </div>
                    </article>

                    <article class="bg-white rounded-[24px] p-6 lg:p-8 shadow-sm border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.25em] text-gray-400 font-bold">Operacao</p>
                                <h3 class="text-2xl font-black text-gray-900 mt-2">Linhas e Pontos</h3>
                                <p class="text-sm text-gray-500 mt-2">Gerencie as linhas do dia e seus pontos de parada em uma tela dedicada.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/rotas" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 text-white px-5 py-3 font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition">
                                Abrir tela
                            </a>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Linhas</p>
                                <p class="mt-3 text-3xl font-black text-slate-900"><?= count($linhas_com_pontos ?? []) ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Pontos</p>
                                <p class="mt-3 text-3xl font-black text-slate-900"><?= (int) ($stats['pontos'] ?? 0) ?></p>
                            </div>
                        </div>
                    </article>

                    <article class="bg-white rounded-[24px] p-6 lg:p-8 shadow-sm border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.25em] text-gray-400 font-bold">Frota</p>
                                <h3 class="text-2xl font-black text-gray-900 mt-2">Veiculos</h3>
                                <p class="text-sm text-gray-500 mt-2">Acesse a tela exclusiva para cadastrar e administrar a frota da empresa.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/dashboard?section=veiculos" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-5 py-3 font-bold shadow-lg shadow-slate-200 hover:bg-slate-800 transition">
                                Abrir tela
                            </a>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Veiculos</p>
                                <p class="mt-3 text-3xl font-black text-slate-900"><?= count($veiculos ?? []) ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-bold">Status</p>
                                <p class="mt-3 text-lg font-black text-slate-900"><?= htmlspecialchars((string) ($stats['viagem_status'] ?? 'Sem viagens ativas')) ?></p>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <div id="modalVeiculo" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
            <h3 id="tituloModalVeiculo" class="text-2xl font-black text-gray-800 mb-6 tracking-tighter">Novo Veiculo</h3>
            <form id="formVeiculo" class="space-y-4">
                <input type="hidden" name="id" id="veiculoId">
                <div>
                    <label for="veiculoIdentificador" class="block text-sm font-bold text-gray-600 mb-2">Identificador</label>
                    <input type="text" name="numero_identificador" id="veiculoIdentificador" placeholder="Ex: VAN 03 ou FROTA-BASE" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="veiculoPlaca" class="block text-sm font-bold text-gray-600 mb-2">Placa</label>
                    <input type="text" name="placa" id="veiculoPlaca" placeholder="Ex: ABC1D23" class="w-full p-4 rounded-2xl bg-gray-50 border-none outline-none focus:ring-2 focus:ring-blue-400 uppercase" required>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="fecharModalVeiculo()" class="flex-1 p-4 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="flex-1 p-4 rounded-2xl font-bold bg-slate-900 text-white shadow-lg shadow-slate-200 active:scale-95 transition">Salvar Veiculo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalVeiculo(veiculo = null) {
            const form = document.getElementById('formVeiculo');
            form.reset();

            document.getElementById('veiculoId').value = veiculo && veiculo.id ? veiculo.id : '';
            document.getElementById('veiculoIdentificador').value = veiculo && veiculo.numero_identificador ? veiculo.numero_identificador : '';
            document.getElementById('veiculoPlaca').value = veiculo && veiculo.placa ? veiculo.placa : '';
            document.getElementById('tituloModalVeiculo').innerText = veiculo && veiculo.id ? 'Editar Veiculo' : 'Novo Veiculo';
            document.getElementById('modalVeiculo').classList.replace('hidden', 'flex');
        }

        function fecharModalVeiculo() {
            document.getElementById('modalVeiculo').classList.replace('flex', 'hidden');
        }

        function filtrarVeiculos() {
            const busca = document.getElementById('buscaVeiculo');
            if (!busca) {
                return;
            }

            const termo = busca.value.toLowerCase();
            document.querySelectorAll('.card-veiculo').forEach(card => {
                card.style.display = card.getAttribute('data-search').includes(termo) ? 'block' : 'none';
            });
        }

        const formVeiculo = document.getElementById('formVeiculo');
        if (formVeiculo) {
            formVeiculo.addEventListener('submit', function (e) {
                e.preventDefault();
                fetch('<?= BASE_URL ?>/admin/salvar-veiculo', {
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

        function excluirVeiculo(id) {
            Swal.fire({
                title: 'Excluir veiculo?',
                text: 'Essa acao remove o veiculo da frota cadastrada.',
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

                fetch('<?= BASE_URL ?>/admin/deletar-veiculo', {
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
