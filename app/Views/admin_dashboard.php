<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex bg-gray-50 relative z-10" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <?php
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

    <main class="flex-1 p-10">
        <header>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mb-8">Visao geral do sistema BeFlow</p>
        </header>

        <section class="grid grid-cols-5 gap-6 mb-8">
            <?php foreach ($dashboardCards as $card): ?>
                <?php
                $cardTitle = $card['title'];
                $cardValue = $card['value'];

                if ($cardTitle === 'Usuarios') {
                    $cardValue = $stats['alunos'] ?? 0;
                } elseif ($cardTitle === 'Linhas e Onibus') {
                    $cardValue = isset($linhas_com_pontos) ? count($linhas_com_pontos) : 0;
                } elseif ($cardTitle === 'Pontos de parada') {
                    $cardValue = $stats['pontos'] ?? 0;
                } elseif ($cardTitle === 'Horarios das linhas') {
                    $cardTitle = 'Motoristas';
                    $cardValue = $stats['motoristas'] ?? 0;
                }
                ?>
                <article class="bg-white rounded-[20px] p-6 shadow-sm border border-gray-100 flex flex-col">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $card['icon_bg'] ?> <?= $card['icon_fg'] ?>">
                        <?= dashboardIcon($card['icon']) ?>
                    </div>
                    <p class="mt-8 text-sm text-gray-500"><?= htmlspecialchars($cardTitle) ?></p>
                    <h2 class="mt-2 text-4xl font-bold text-gray-900"><?= htmlspecialchars((string) $cardValue) ?></h2>
                    <span class="mt-5 bg-green-100 text-green-600 text-[10px] px-2 py-1 rounded-full w-fit"><?= htmlspecialchars($card['change']) ?></span>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="grid grid-cols-3 gap-6">
            <article class="col-span-2 bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Resumo do sistema</h3>
                    <p class="text-sm text-gray-500">Informacoes em tempo real</p>
                </div>

                <div class="relative h-80 rounded-[20px] bg-gradient-to-t from-blue-50 to-transparent overflow-hidden">
                    <div class="absolute top-14 left-[53%] -translate-x-1/2 bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-full shadow-lg">
                        Abril 897
                    </div>

                    <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" class="w-full h-full">
                        <path d="M<?= $polyline ?>" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        <?php foreach ($points as $point): ?>
                            <circle cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="6" fill="#2563eb"/>
                        <?php endforeach; ?>
                        <?php foreach ($points as $point): ?>
                            <text x="<?= $point['x'] - 10 ?>" y="295" class="fill-gray-400 text-xs"><?= htmlspecialchars($point['label']) ?></text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </article>

            <aside class="col-span-1 bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Resumo do sistema</h3>
                    <p class="text-sm text-gray-500">Ultimos registros no sistema</p>
                </div>

                <div class="flex flex-col gap-6 mt-6">
                    <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold <?= activityAvatarClasses($activity['type']) ?>">
                                <?= htmlspecialchars($activity['avatar']) ?>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800">
                                    <?php if ($activity['type'] === 'line'): ?>
                                        Nova linha cadastrada
                                    <?php elseif ($activity['type'] === 'stop'): ?>
                                        Novo ponto registrado
                                    <?php else: ?>
                                        Novo usuario cadastrado
                                    <?php endif; ?>
                                </p>
                                <p class="text-sm text-gray-400">
                                    <?php
                                    $parts = explode(' ', $activity['text']);
                                    echo htmlspecialchars(implode(' ', array_slice($parts, -3)));
                                    ?>
                                </p>
                            </div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider">ha 2h</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </section>
    </main>

</body>
</html>
