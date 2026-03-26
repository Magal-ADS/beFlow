<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeFlow - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-500 h-screen flex flex-col items-center justify-center font-sans">

    <div class="bg-white w-11/12 max-w-md rounded-[2.5rem] p-8 shadow-2xl mt-8">
        <h2 class="text-2xl font-semibold text-center text-gray-800 mb-6 mt-4">Entre para continuar</h2>

        <form action="/beFlow/autenticar" method="POST" class="space-y-4">
            <div>
                <input type="email" name="email" placeholder="Digite seu e-mail" required
                    class="w-full px-4 py-4 rounded-xl bg-gray-50 border border-gray-100 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <input type="password" name="senha" placeholder="••••••••" required
                    class="w-full px-4 py-4 rounded-xl bg-gray-50 border border-gray-100 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <div class="text-right mt-2">
                    <a href="#" class="text-xs text-gray-400 hover:text-blue-500">esqueceu sua senha?</a>
                </div>
            </div>

            <button type="submit" 
                class="w-full bg-[#4A7DDF] hover:bg-blue-600 text-white font-semibold py-4 rounded-xl transition duration-300 mt-4 shadow-lg shadow-blue-500/30">
                Login
            </button>
        </form>

        <div class="mt-8 text-center">
            <div class="flex items-center justify-center gap-2 mb-6">
                <hr class="w-1/4 border-gray-200">
                <span class="text-xs text-gray-400">Ou faça login com</span>
                <hr class="w-1/4 border-gray-200">
            </div>
            
            <div class="flex justify-center gap-4">
                <button class="w-14 h-14 border border-gray-100 rounded-2xl shadow-sm flex items-center justify-center hover:bg-gray-50 text-xl font-bold text-gray-700">G</button>
                <button class="w-14 h-14 border border-gray-100 rounded-2xl shadow-sm flex items-center justify-center hover:bg-gray-50 text-xl font-bold text-gray-700"></button>
                <button class="w-14 h-14 border border-gray-100 rounded-2xl shadow-sm flex items-center justify-center hover:bg-gray-50 text-xl font-bold text-blue-600">f</button>
            </div>
        </div>

        <p class="text-center text-sm text-gray-600 mt-8 mb-4">
            Se você não possui uma conta <a href="#" class="text-blue-500 font-semibold">Registre-se aqui!</a>
        </p>
    </div>

</body>
</html>