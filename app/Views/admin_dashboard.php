<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow Admin - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
        <div class="p-8">
            <h1 class="text-2xl font-black text-blue-600 tracking-tighter italic">BeFlow <span class="text-gray-300 font-light">Admin</span></h1>
        </div>
        
        <nav class="flex-1 px-4 space-y-2">
            <a href="/beFlow/admin/dashboard" class="flex items-center gap-3 p-3 bg-blue-50 text-blue-600 rounded-2xl font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="/beFlow/admin/usuarios" class="flex items-center gap-3 p-3 text-gray-500 hover:bg-gray-50 rounded-2xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Usuários
            </a>
            <a href="#" class="flex items-center gap-3 p-3 text-gray-500 hover:bg-gray-50 rounded-2xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Rotas e Pontos
            </a>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="/beFlow/logout" class="flex items-center gap-3 p-3 text-red-500 hover:bg-red-50 rounded-2xl transition font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sair do Painel
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-10">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black text-gray-800">Visão Geral</h2>
                <p class="text-gray-500">Bem-vindo ao centro de controle da BeFlow.</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Empresa</p>
                <p class="text-sm font-bold text-blue-600">Viação BeFlow Local</p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-sm uppercase mb-2">Alunos Ativos</p>
                    <h3 class="text-5xl font-black text-gray-800 tracking-tighter"><?= $stats['alunos'] ?></h3>
                </div>
                <div class="absolute -right-4 -bottom-4 text-blue-50 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-sm uppercase mb-2">Motoristas</p>
                    <h3 class="text-5xl font-black text-gray-800 tracking-tighter"><?= $stats['motoristas'] ?></h3>
                </div>
                <div class="absolute -right-4 -bottom-4 text-green-50 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7h8m-8 4h8m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-gray-400 font-bold text-sm uppercase mb-2">Pontos de Parada</p>
                    <h3 class="text-5xl font-black text-gray-800 tracking-tighter"><?= $stats['pontos'] ?></h3>
                </div>
                <div class="absolute -right-4 -bottom-4 text-red-50 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-[#1e293b] p-10 rounded-[2.5rem] shadow-2xl min-h-[400px] flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-blue-500/20 rounded-full flex items-center justify-center mb-6 animate-pulse">
                <div class="w-10 h-10 bg-blue-500 rounded-full"></div>
            </div>
            <h4 class="text-white text-xl font-bold mb-2">Monitoramento em Tempo Real</h4>
            <p class="text-gray-400 max-w-sm">Em breve, você poderá visualizar os ônibus se movendo no mapa e a ocupação de cada ponto aqui mesmo.</p>
        </div>
    </main>

</body>
</html>