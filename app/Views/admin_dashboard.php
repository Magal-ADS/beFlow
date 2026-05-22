<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b1220] h-screen overflow-hidden text-slate-900 flex" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <?php
    function dashboardIcon($name) {
        switch ($name) {
            case 'users':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zm-8 0c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 2.239-5 5v2h10v-2c0-2.761-2.239-5-5-5zm8 0c-.697 0-1.359.117-1.975.332A6.979 6.979 0 0117 18v2h4v-2c0-2.761-2.239-5-5-5z"/></svg>';
            case 'bus':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M7 17h10m-9 3h8m-9-6h10l1-6H6l1 6zm1-6V7a3 3 0 013-3h2a3 3 0 013 3v1"/><circle cx="9" cy="15" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1.2" fill="currentColor" stroke="none"/></svg>';
            case 'pin':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 21s-6-4.35-6-10a6 6 0 1112 0c0 5.65-6 10-6 10z"/><circle cx="12" cy="11" r="2.5" fill="currentColor" stroke="none"/></svg>';
            case 'clock':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v5l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            case 'building':
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 21V7a2 2 0 012-2h4v16m0 0h4m-4 0v-6h4v6m0 0h4V11a2 2 0 00-2-2h-2"/></svg>';
            default:
                return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 21s-6-4.35-6-10a6 6 0 1112 0c0 5.65-6 10-6 10z"/></svg>';
        }
    }

    function activityPalette($type) {
        $map = [
            'user' => ['avatar' => 'bg-blue-100 text-blue-600', 'icon' => 'bg-blue-50 text-blue-600'],
            'line' => ['avatar' => 'bg-emerald-100 text-emerald-600', 'icon' => 'bg-emerald-50 text-emerald-600'],
            'stop' => ['avatar' => 'bg-violet-100 text-violet-600', 'icon' => 'bg-violet-50 text-violet-600'],
        ];

        return $map[$type] ?? ['avatar' => 'bg-slate-100 text-slate-600', 'icon' => 'bg-slate-50 text-slate-600'];
    }

    $maxValue = max(array_column($summarySeries, 'value'));
    $chartHeight = 300;
    $chartWidth = 720;
    $leftPadding = 28;
    $bottomPadding = 24;
    $usableHeight = $chartHeight - 60;
    $usableWidth = $chartWidth - ($leftPadding * 2);
    $pointCount = max(count($summarySeries) - 1, 1);
    $points = [];

    foreach ($summarySeries as $index => $point) {
        $x = $leftPadding + ($usableWidth / $pointCount) * $index;
        $y = 24 + $usableHeight - (($point['value'] / max($maxValue, 1)) * $usableHeight);
        $points[] = ['x' => round($x, 2), 'y' => round($y, 2), 'label' => $point['label'], 'value' => $point['value']];
    }

    $polyline = implode(' ', array_map(function ($point) {
        return $point['x'] . ',' . $point['y'];
    }, $points));

    $peakIndex = 0;
    foreach ($points as $index => $point) {
        if ($point['value'] > $points[$peakIndex]['value']) {
            $peakIndex = $index;
        }
    }

    $peak = $points[$peakIndex];
    ?>

    <main class="h-screen flex-1 overflow-y-auto bg-white rounded-l-[42px] px-10 py-10 md:px-12 md:py-12">
        <header class="mb-10">
            <h2 class="text-[42px] leading-none font-black tracking-[-0.04em] text-slate-900">Dashboard</h2>
            <p class="mt-3 text-base font-medium text-slate-500">Visao geral do sistema BeFlow</p>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">
            <?php foreach ($dashboardCards as $card): ?>
                <article class="rounded-[32px] border border-slate-100 bg-white px-6 py-6">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $card['icon_bg'] ?> <?= $card['icon_fg'] ?>">
                        <?= dashboardIcon($card['icon']) ?>
                    </div>
                    <div class="mt-8 text-[42px] leading-none font-black tracking-[-0.05em] text-slate-900"><?= htmlspecialchars($card['value']) ?></div>
                    <h3 class="mt-4 text-[17px] leading-snug font-semibold text-slate-700"><?= htmlspecialchars($card['title']) ?></h3>
                    <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-[12px] font-bold text-emerald-600">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <?= htmlspecialchars($card['change']) ?>
                    </span>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_380px] gap-6">
            <article class="rounded-[34px] border border-slate-100 bg-[#f8fbff] p-7">
                <div class="flex items-start justify-between gap-4 mb-8">
                    <div>
                        <h3 class="text-[28px] font-black tracking-[-0.04em] text-slate-900">Resumo do sistema</h3>
                        <p class="mt-2 text-sm font-medium text-slate-500">Dados reais do banco neste momento</p>
                    </div>
                </div>

                <div class="relative rounded-[28px] bg-white px-6 py-6">
                    <div class="absolute z-10 rounded-full bg-[#1f6bff] px-4 py-2 text-sm font-bold text-white shadow-[0_18px_36px_rgba(31,107,255,0.22)]" style="left: calc(<?= $peak['x'] ?>px - 26px); top: calc(<?= $peak['y'] ?>px - 10px); transform: translate(-50%, -100%);">
                        <?= htmlspecialchars($peakPoint['label']) ?> <?= $peakPoint['value'] ?>
                    </div>

                    <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" class="w-full h-[320px]" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="lineFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#1f6bff" stop-opacity="0.24"/>
                                <stop offset="100%" stop-color="#1f6bff" stop-opacity="0.02"/>
                            </linearGradient>
                        </defs>

                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php $lineY = 24 + ($usableHeight / 4) * $i; ?>
                            <line x1="<?= $leftPadding ?>" y1="<?= $lineY ?>" x2="<?= $chartWidth - $leftPadding ?>" y2="<?= $lineY ?>" stroke="#e8eef8" stroke-dasharray="4 8"/>
                        <?php endfor; ?>

                        <path d="M <?= $points[0]['x'] ?> <?= $chartHeight - $bottomPadding ?> L <?= $polyline ?> L <?= end($points)['x'] ?> <?= $chartHeight - $bottomPadding ?> Z" fill="url(#lineFill)"/>
                        <polyline points="<?= $polyline ?>" fill="none" stroke="#1f6bff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>

                        <?php foreach ($points as $point): ?>
                            <circle cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="6.5" fill="#ffffff" stroke="#1f6bff" stroke-width="3"/>
                            <text x="<?= $point['x'] ?>" y="<?= $chartHeight - 2 ?>" text-anchor="middle" font-size="13" font-weight="700" fill="#94a3b8"><?= $point['label'] ?></text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </article>

            <aside class="rounded-[34px] border border-slate-100 bg-white p-7">
                <div class="mb-7">
                    <h3 class="text-[28px] font-black tracking-[-0.04em] text-slate-900">Ultimos registros no sistema</h3>
                    <p class="mt-2 text-sm font-medium text-slate-500">Ultimos registros reais persistidos</p>
                </div>

                <div class="space-y-4">
                    <?php foreach ($recentActivities as $activity): ?>
                        <?php $palette = activityPalette($activity['type']); ?>
                        <article class="flex items-start gap-4 rounded-[24px] bg-slate-50 px-4 py-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-black <?= $palette['avatar'] ?>">
                                <?= htmlspecialchars($activity['avatar']) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center <?= $palette['icon'] ?>">
                                        <?= dashboardIcon($activity['type'] === 'line' ? 'bus' : ($activity['type'] === 'stop' ? 'pin' : 'users')) ?>
                                    </span>
                                </div>
                                <p class="text-[15px] leading-6 font-semibold text-slate-800"><?= htmlspecialchars($activity['text']) ?></p>
                            </div>
                            <span class="shrink-0 pt-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-400"><?= htmlspecialchars($activity['meta']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </aside>
        </section>
    </main>

</body>
</html>
