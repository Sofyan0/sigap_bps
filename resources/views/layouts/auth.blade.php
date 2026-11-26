<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SIGAP SEKARKIJANG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <a href="{{ route('login') }}" class="mb-6 flex items-center justify-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-xl text-white shadow-lg">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
                <span>
                    <span class="block text-xl font-black tracking-wide text-slate-900 dark:text-white">SIGAP</span>
                    <span class="block text-[10px] font-bold tracking-[0.2em] text-blue-600 dark:text-blue-400">SEKARKIJANG</span>
                </span>
            </a>
            <section class="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                @yield('content')
            </section>
            <p class="mt-5 text-center text-xs text-slate-500">
                Sistem Informasi Harga dan Pengendalian Inflasi Daerah
            </p>
        </div>
    </main>
</body>
</html>
