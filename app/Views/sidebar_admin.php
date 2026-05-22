<?php
$uri = $_SERVER['REQUEST_URI'];
$isDashboard = strpos($uri, '/admin/dashboard') !== false;
$isUsuarios = strpos($uri, '/admin/usuarios') !== false;
$isRotas = strpos($uri, '/admin/rotas') !== false;

if (!isset($currentAdmin) && isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/../../config/database.php';

    $db = (new Database())->getConnection();
    $stmtAdmin = $db->prepare("SELECT nome, telefone, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmtAdmin->execute(['id' => $_SESSION['usuario_id']]);
    $currentAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC) ?: [
        'nome' => $_SESSION['usuario_nome'] ?? 'Usuario',
        'telefone' => '',
        'email' => '',
    ];
}
?>

<?php
function renderSidebarIcon($name) {
    switch ($name) {
        case 'dashboard':
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h7v6H4zm9 0h7v6h-7zM4 13h7v6H4zm9 0h7v6h-7z"/></svg>';
        case 'users':
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zm-8 0c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 2.239-5 5v2h10v-2c0-2.761-2.239-5-5-5zm8 0c-.697 0-1.359.117-1.975.332A6.979 6.979 0 0117 18v2h4v-2c0-2.761-2.239-5-5-5z"/></svg>';
        case 'bus':
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17h10m-9 3h8m-9-6h10l1-6H6l1 6zm1-6V7a3 3 0 013-3h2a3 3 0 013 3v1"/><circle cx="9" cy="15" r="1.1" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1.1" fill="currentColor" stroke="none"/></svg>';
        default:
            return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12H3m0 0l4-4m-4 4l4 4m6-9h4a2 2 0 012 2v6a2 2 0 01-2 2h-4"/></svg>';
    }
}

function sidebarItemClasses($active) {
    if ($active) {
        return 'w-[calc(100%+2.5rem)] relative z-50 shadow-[8px_0_20px_rgba(37,99,235,0.4)] bg-blue-500 text-white rounded-xl';
    }

    return 'text-gray-300 hover:bg-gray-800';
}
?>

<div id="adminSidebarOverlay" class="fixed inset-0 bg-slate-950/50 z-40 hidden lg:hidden" onclick="toggleAdminSidebar(false)"></div>

<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 shadow-2xl w-72 max-w-[85vw] bg-[#111827] text-gray-300 flex flex-col min-h-screen p-6 transform -translate-x-full transition-transform duration-300 lg:translate-x-0 lg:sticky lg:top-0">
    <div class="flex items-center justify-between gap-3 mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-blue-600 flex items-center justify-center text-white">
                <?= renderSidebarIcon('bus') ?>
            </div>
            <div class="text-white font-bold text-lg leading-tight">
                BeFlow Admin Panel
            </div>
        </div>
        <button type="button" class="lg:hidden w-10 h-10 rounded-2xl bg-gray-800 text-white flex items-center justify-center" onclick="toggleAdminSidebar(false)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="pb-6 mb-6 border-b border-gray-800">
        <p class="text-sm">
            Ola, <span class="text-blue-500 font-bold"><?= htmlspecialchars($currentAdmin['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuario')) ?>!</span>
        </p>
        <p class="text-white text-sm mt-2"><?= htmlspecialchars(($currentAdmin['telefone'] ?? '') !== '' ? $currentAdmin['telefone'] : ($currentAdmin['email'] ?? 'Sem contato')) ?></p>
    </div>

    <nav class="flex flex-col gap-2 pr-6">
        <a href="<?= BASE_URL ?>/admin/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= sidebarItemClasses($isDashboard) ?>">
            <?= renderSidebarIcon('dashboard') ?>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/usuarios" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= sidebarItemClasses($isUsuarios) ?>">
            <?= renderSidebarIcon('users') ?>
            <span>Usuarios</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/rotas" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= sidebarItemClasses($isRotas) ?>">
            <?= renderSidebarIcon('bus') ?>
            <span>Linhas e Onibus</span>
        </a>
    </nav>

    <div class="mt-auto pt-8">
        <a href="<?= BASE_URL ?>/logout" class="bg-red-500 text-white rounded-full px-4 py-3 flex justify-center items-center gap-2 mt-auto hover:bg-red-600 transition">
            <?= renderSidebarIcon('logout') ?>
            <span>Sair</span>
        </a>
        <p class="text-xs text-gray-500 text-center mt-4">Termos de uso v1.1.1</p>
    </div>
</aside>

<script>
    function toggleAdminSidebar(forceOpen) {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('adminSidebarOverlay');

        if (!sidebar || !overlay) {
            return;
        }

        const shouldOpen = typeof forceOpen === 'boolean'
            ? forceOpen
            : sidebar.classList.contains('-translate-x-full');

        if (shouldOpen) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            return;
        }

        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>
