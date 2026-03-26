<?php
// Pega a URL atual para saber qual menu deixar "Ativo" (azul)
$uri = $_SERVER['REQUEST_URI'];
$isDashboard = strpos($uri, '/admin/dashboard') !== false;
$isUsuarios  = strpos($uri, '/admin/usuarios') !== false;
$isRotas     = strpos($uri, '/admin/rotas') !== false;
?>

<aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
    <div class="p-8">
        <h1 class="text-2xl font-black text-blue-600 tracking-tighter italic">BeFlow <span class="text-gray-300 font-light">Admin</span></h1>
    </div>
    
    <nav class="flex-1 px-4 space-y-2">
        <a href="/beFlow/admin/dashboard" class="flex items-center gap-3 p-3 rounded-2xl transition <?= $isDashboard ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-500 hover:bg-gray-50' ?>">
            Dashboard
        </a>
        
        <a href="/beFlow/admin/usuarios" class="flex items-center gap-3 p-3 rounded-2xl transition <?= $isUsuarios ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-500 hover:bg-gray-50' ?>">
            Usuários
        </a>
        
        <a href="/beFlow/admin/rotas" class="flex items-center gap-3 p-3 rounded-2xl transition <?= $isRotas ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-500 hover:bg-gray-50' ?>">
            Rotas e Pontos
        </a>
    </nav>

    <div class="p-4 border-t border-gray-100">
        <a href="/beFlow/logout" class="flex items-center gap-3 p-3 text-red-500 hover:bg-red-50 rounded-2xl transition font-bold">
            Sair
        </a>
    </div>
</aside>