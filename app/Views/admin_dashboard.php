<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <main class="flex-1 overflow-y-auto p-10">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black text-gray-800 tracking-tighter">Visão Geral</h2>
                <p class="text-gray-500 font-medium">Bem-vindo ao centro de controle da BeFlow.</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-100 px-3 py-1 rounded-lg inline-block mb-1">Empresa Vinculada</p>
                <p class="text-sm font-black text-blue-600 italic">Viação BeFlow Local</p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-xl transition-all duration-300">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mb-2">Alunos Ativos</p>
                    <h3 class="text-6xl font-black text-gray-800 tracking-tighter"><?= $stats['alunos'] ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-green-500 text-xs font-bold">
                        <span class="bg-green-100 px-2 py-1 rounded-lg">↑ 100% no sistema</span>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 text-blue-500 opacity-[0.03] group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-xl transition-all duration-300">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mb-2">Motoristas</p>
                    <h3 class="text-6xl font-black text-gray-800 tracking-tighter"><?= $stats['motoristas'] ?></h3>
                    <p class="text-[10px] text-gray-300 font-bold mt-4">DISPONÍVEIS NA FROTA</p>
                </div>
                <div class="absolute -right-4 -bottom-4 text-green-500 opacity-[0.03] group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-xl transition-all duration-300">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mb-2">Pontos de Parada</p>
                    <h3 class="text-6xl font-black text-gray-800 tracking-tighter"><?= $stats['pontos'] ?></h3>
                    <p class="text-[10px] text-gray-300 font-bold mt-4 italic">Status: <?= htmlspecialchars($stats['viagem_status']) ?></p>
                </div>
                <div class="absolute -right-4 -bottom-4 text-red-500 opacity-[0.03] group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-[#1e293b] p-10 rounded-[3rem] shadow-2xl min-h-[400px] flex flex-col items-center justify-center text-center relative overflow-hidden border border-slate-700">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-blue-500 rounded-full animate-ping"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] border border-blue-400 rounded-full animate-pulse"></div>
            </div>

            <div class="relative z-10">
                <div class="w-24 h-24 bg-blue-500/20 rounded-full flex items-center justify-center mb-8 mx-auto border border-blue-500/30">
                    <div class="w-12 h-12 bg-blue-500 rounded-full shadow-[0_0_30px_rgba(59,130,246,0.5)]"></div>
                </div>
                <h4 class="text-white text-2xl font-black mb-3 tracking-tight">Monitoramento em Tempo Real</h4>
                <p class="text-slate-400 max-w-sm mx-auto font-medium leading-relaxed">
                    Em breve, você poderá visualizar os ônibus se movendo no mapa e a ocupação de cada ponto em tempo real.
                </p>
                <div class="mt-8">
                    <span class="bg-slate-800 text-blue-400 text-[10px] font-black uppercase tracking-[0.2em] px-4 py-2 rounded-full border border-slate-700">Módulo em Desenvolvimento</span>
                </div>
            </div>
        </div>
    </main>

</body>
</html>