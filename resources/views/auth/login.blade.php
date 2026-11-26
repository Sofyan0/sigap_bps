@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Masuk ke SIGAP</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gunakan akun Anda untuk melanjutkan ke dashboard TPID.</p>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-300" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="nama@email.com">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="Masukkan kata sandi">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Ingat saya
        </label>

        <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500">
            <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:text-blue-500 dark:text-blue-400">Daftar sekarang</a>
    </p>
@endsection
