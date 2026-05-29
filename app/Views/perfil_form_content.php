<?php
$tipoUsuario = $_SESSION['tipo_usuario'] ?? '';
$voltarUrl = BASE_URL . '/login';

if ($tipoUsuario === 'aluno') {
    $voltarUrl = BASE_URL . '/home-aluno';
} elseif ($tipoUsuario === 'motorista') {
    $voltarUrl = BASE_URL . '/home-motorista';
} elseif (in_array($tipoUsuario, ['admin_empresa', 'admin_geral'], true)) {
    $voltarUrl = BASE_URL . '/admin/dashboard';
}
?>

<div class="flex items-center justify-between gap-4 mb-8">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-600">Conta</p>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Meu Perfil</h1>
        <p class="text-gray-500 mt-2">Atualize seus dados de acesso e contato.</p>
    </div>
    <a href="<?= $voltarUrl ?>" class="shrink-0 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
        Voltar
    </a>
</div>

<?php if (!empty($flash)): ?>
    <div class="mb-6 rounded-3xl border px-5 py-4 <?= !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="rounded-[2rem] border border-gray-100 bg-white p-6 sm:p-8 shadow-sm">
    <form action="<?= BASE_URL ?>/perfil/salvar" method="POST" class="space-y-5">
        <div>
            <label for="nome" class="mb-2 block text-sm font-bold text-gray-700">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
        </div>

        <div>
            <label for="email" class="mb-2 block text-sm font-bold text-gray-700">E-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
        </div>

        <div>
            <label for="telefone" class="mb-2 block text-sm font-bold text-gray-700">Telefone</label>
            <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" placeholder="(16) 99999-9999" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="senha" class="mb-2 block text-sm font-bold text-gray-700">Nova senha</label>
                <input type="password" id="senha" name="senha" placeholder="Deixe em branco para manter" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="confirmar_senha" class="mb-2 block text-sm font-bold text-gray-700">Confirmar nova senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a nova senha" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
            </div>
        </div>

        <div class="rounded-2xl bg-blue-50 px-4 py-3 text-sm text-blue-700">
            Seu tipo de usuario atual: <span class="font-bold"><?= htmlspecialchars($usuario['tipo_usuario'] ?? $tipoUsuario) ?></span>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= $voltarUrl ?>" class="rounded-2xl px-5 py-4 text-center font-bold text-gray-500 hover:bg-gray-100">Cancelar</a>
            <button type="submit" class="rounded-2xl bg-blue-600 px-6 py-4 font-bold text-white shadow-lg shadow-blue-200 hover:bg-blue-700">
                Salvar Alteracoes
            </button>
        </div>
    </form>
</div>
