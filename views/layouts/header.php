<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulHalaman ?? APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primer:     '#1E40AF',
                        'primer-h': '#1D4ED8',
                        sukses:     '#15803D',
                        peringatan: '#B45309',
                        bahaya:     '#B91C1C',
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #F8FAFC; color: #0F172A; }
        .badge-hadir        { background:#DCFCE7; color:#15803D; border:1px solid #86EFAC; }
        .badge-terlambat    { background:#FEF3C7; color:#B45309; border:1px solid #FCD34D; }
        .badge-tidak-hadir  { background:#FEE2E2; color:#B91C1C; border:1px solid #FCA5A5; }
    </style>
</head>
<body class="font-sans antialiased">

<!-- Toast notifikasi -->
<?php $flash = Response::ambilFlash(); ?>
<?php if ($flash): ?>
<div id="toast"
     class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-sm font-medium shadow-md
            <?= $flash['tipe'] === 'sukses' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
    <?= htmlspecialchars($flash['pesan']) ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('toast'); if(t) t.remove(); }, 3000);</script>
<?php endif; ?>

<div class="flex h-screen overflow-hidden">
