<?php
$uri = $_SERVER['REQUEST_URI'];
$isDashboard = strpos($uri, '/admin/dashboard') !== false;
$isUsuarios = strpos($uri, '/admin/usuarios') !== false;
$isRotas = strpos($uri, '/admin/rotas') !== false;

$menuItems = [
    [
        'label' => 'Dashboard',
        'href' => BASE_URL . '/admin/dashboard',
        'active' => $isDashboard,
        'icon' => 'grid',
    ],
    [
        'label' => 'Usuarios',
        'href' => BASE_URL . '/admin/usuarios',
        'active' => $isUsuarios,
        'icon' => 'users',
    ],
    [
        'label' => 'Linhas e Pontos',
        'href' => BASE_URL . '/admin/rotas',
        'active' => $isRotas,
        'icon' => 'map',
    ],
];
?>

<?php
function renderSidebarIcon($name) {
    switch ($name) {
        case 'grid':
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h7v6H4zm9 0h7v6h-7zM4 13h7v6H4zm9 0h7v6h-7z"/></svg>';
        case 'users':
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zm-8 0c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 2.239-5 5v2h10v-2c0-2.761-2.239-5-5-5zm8 0c-.697 0-1.359.117-1.975.332A6.979 6.979 0 0117 18v2h4v-2c0-2.761-2.239-5-5-5z"/></svg>';
        default:
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-6-4.35-6-10a6 6 0 1112 0c0 5.65-6 10-6 10z"/></svg>';
    }
}
?>

<aside class="w-[308px] bg-[#0b1220] text-slate-200 flex flex-col shrink-0 px-6 py-7">
    <div class="flex items-center gap-4 mb-10">
        <div class="w-16 h-16 rounded-[22px] bg-[#1f6bff] flex items-center justify-center shadow-[0_18px_40px_rgba(31,107,255,0.28)]">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17h10m-9 3h8m-9-6h10l1-6H6l1 6zm1-6V7a3 3 0 013-3h2a3 3 0 013 3v1"/>
                <circle cx="9" cy="15" r="1.2" fill="currentColor" stroke="none"/>
                <circle cx="15" cy="15" r="1.2" fill="currentColor" stroke="none"/>
            </svg>
        </div>
        <div>
            <p class="text-xs uppercase tracking-[0.28em] text-slate-500 font-bold">BeFlow</p>
            <h1 class="text-2xl font-black text-white tracking-tight">Admin Panel</h1>
        </div>
    </div>

    <div class="bg-[#111a2b] border border-white/5 rounded-[28px] p-5 mb-8">
        <p class="text-sm text-slate-400 font-medium mb-1">Ola, <span class="text-[#3d8bff] font-bold"><?= htmlspecialchars($currentAdmin['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuario')) ?>!</span></p>
        <p class="text-lg text-white font-black tracking-tight"><?= htmlspecialchars(($currentAdmin['telefone'] ?? '') !== '' ? $currentAdmin['telefone'] : ($currentAdmin['email'] ?? 'Sem contato')) ?></p>
    </div>

    <nav class="flex-1 space-y-3">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?= $item['href'] ?>" class="relative flex items-center gap-3 px-5 py-4 rounded-full transition-all duration-200 <?= $item['active'] ? 'bg-[#1f6bff] text-white font-bold shadow-[0_18px_40px_rgba(31,107,255,0.24)]' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5' ?>">
                <?php if ($item['active']): ?>
                    <span class="absolute -right-6 top-0 w-10 h-10 rounded-full bg-white"></span>
                    <span class="absolute -right-6 bottom-0 w-10 h-10 rounded-full bg-white"></span>
                    <span class="absolute right-0 top-0 h-full w-8 bg-[#1f6bff]"></span>
                <?php endif; ?>
                <span class="relative z-10"><?= renderSidebarIcon($item['icon']) ?></span>
                <span class="relative z-10 text-[15px]"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="pt-8 mt-8 border-t border-white/5">
        <a href="<?= BASE_URL ?>/logout" class="flex items-center justify-center gap-3 px-5 py-4 rounded-full bg-[#ff5a6b] text-white font-bold shadow-[0_18px_40px_rgba(255,90,107,0.22)] hover:bg-[#ff4a5d] transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12H3m0 0l4-4m-4 4l4 4m6-9h4a2 2 0 012 2v6a2 2 0 01-2 2h-4"/>
            </svg>
            <span>Sair</span>
        </a>
        <p class="text-center text-xs text-slate-500 font-medium mt-5">Termos de uso v1.1.1</p>
    </div>
</aside>
