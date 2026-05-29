<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow - Meu Perfil</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/assets/branding/icone.svg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <?php if (in_array($_SESSION['tipo_usuario'] ?? '', ['admin_empresa', 'admin_geral'], true)): ?>
        <div class="flex min-h-screen">
            <?php include __DIR__ . '/sidebar_admin.php'; ?>
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-10 py-8 sm:py-10">
                    <?php include __DIR__ . '/perfil_form_content.php'; ?>
                </div>
            </main>
        </div>
    <?php else: ?>
        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
            <?php include __DIR__ . '/perfil_form_content.php'; ?>
        </main>
    <?php endif; ?>
</body>
</html>
