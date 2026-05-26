<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>body { background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); }</style>
</head>
<body class="font-sans min-h-screen flex items-center justify-center p-4">

<?php if (!empty($kesalahan)): ?>
<div id="toast" class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-sm font-medium bg-red-50 text-red-800 border border-red-200 shadow-md">
    <?= htmlspecialchars($kesalahan) ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('toast'); if(t) t.remove(); }, 4000);</script>
<?php endif; ?>

<div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-[#1E40AF] rounded-2xl mb-4 shadow-sm">
            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900"><?= APP_NAME ?></h2>
        <p class="text-sm text-slate-500 mt-1">SMK Nusantara Rantepao</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-6">Masuk ke Sistem</h3>

        <form method="POST" action="<?= APP_URL ?>/login" novalidate>
            <!-- Username -->
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-slate-700 mb-1">
                    Username
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars($inputUsername ?? '') ?>"
                    required
                    autocomplete="username"
                    class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                           focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]
                           transition-colors"
                    placeholder="Masukkan username">
            </div>

            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                           focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]
                           transition-colors"
                    placeholder="Masukkan password">
            </div>

            <button
                type="submit"
                class="w-full h-10 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold
                       rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-300">
                Masuk
            </button>
        </form>

        <?php if (!empty($akunDemo)): ?>
        <div class="mt-6 pt-5 border-t border-slate-200">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Akun demo</p>
                    <p class="text-xs text-slate-500 mt-0.5">Klik akun untuk mengisi form otomatis.</p>
                </div>
                <span class="shrink-0 px-2 py-1 rounded-md bg-blue-50 text-[11px] font-semibold text-[#1E40AF] border border-blue-100">
                    Seeder
                </span>
            </div>

            <div class="grid gap-2">
                <?php foreach ($akunDemo as $akun): ?>
                <button
                    type="button"
                    data-demo-username="<?= htmlspecialchars($akun['username']) ?>"
                    data-demo-password="<?= htmlspecialchars($akun['password']) ?>"
                    class="demo-account w-full text-left px-3 py-2.5 rounded-md border border-slate-200
                           hover:border-[#1E40AF] hover:bg-blue-50 focus:outline-none focus:ring-2
                           focus:ring-blue-200 transition-colors">
                    <span class="flex items-center justify-between gap-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900 truncate">
                                <?= htmlspecialchars($akun['nama']) ?>
                            </span>
                            <span class="block text-xs text-slate-500 truncate">
                                <?= htmlspecialchars($akun['username']) ?> / <?= htmlspecialchars($akun['password']) ?>
                            </span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded px-2 py-1">
                            <?= htmlspecialchars($akun['role']) ?>
                        </span>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.demo-account').forEach((button) => {
    button.addEventListener('click', () => {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        usernameInput.value = button.dataset.demoUsername || '';
        passwordInput.value = button.dataset.demoPassword || '';
        passwordInput.focus();
    });
});
</script>

</body>
</html>
