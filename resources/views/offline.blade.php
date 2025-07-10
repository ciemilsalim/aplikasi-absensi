<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - {{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="antialiased font-sans h-full">
    <main class="grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8">
        <div class="text-center">
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl">Koneksi Terputus</h1>
            <p class="mt-6 text-base leading-7 text-gray-600">Maaf, Anda sedang offline. Silakan periksa koneksi internet Anda.</p>
            <div class="mt-10 flex items-center justify-center">
                <a href="/" class="rounded-md bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Coba Lagi</a>
            </div>
        </div>
    </main>
</body>
</html>