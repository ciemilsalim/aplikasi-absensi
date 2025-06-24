<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script>
        // Menerapkan dark mode berdasarkan preferensi yang tersimpan
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                }
            }
        }
    </script>
    
</head>
<body class="antialiased font-sans h-full">
    <main class="grid min-h-full place-items-center bg-white dark:bg-slate-900 px-6 py-24 sm:py-32 lg:px-8">
        <div class="text-center">
            <img src="https://img.freepik.com/free-vector/404-error-with-landscape-concept-illustration_114360-7898.jpg?w=996" 
                 alt="Ilustrasi halaman 404 tidak ditemukan" 
                 class="mx-auto h-64 w-auto mb-8"
                 onerror="this.style.display='none'">
            <p class="text-base font-semibold text-sky-600 dark:text-sky-400">404</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">Halaman tidak ditemukan</h1>
            <p class="mt-6 text-base leading-7 text-gray-600 dark:text-gray-300">Maaf, kami tidak dapat menemukan halaman yang Anda cari.</p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <a href="{{ route('welcome') }}" class="rounded-md bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </main>
</body>
</html>
