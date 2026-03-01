<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koneksi Terputus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        body { @apply font-sans; }
    </style>
</head>

<body class="antialiased font-sans h-full bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center border border-slate-100">
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-slate-100 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-12 h-12 text-slate-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Anda Sedang Offline</h2>
        <p class="text-slate-500 mb-8">Koneksi internet Anda terputus. Silakan periksa jaringan Wi-Fi atau data seluler
            Anda dan coba muat ulang halaman.</p>
        <button onclick="window.location.reload()"
            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors">
            Coba Lagi
        </button>
        <div class="mt-6">
            <a href="/" class="text-sm text-sky-600 hover:text-sky-500 font-medium">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </div>
</body>

</html>