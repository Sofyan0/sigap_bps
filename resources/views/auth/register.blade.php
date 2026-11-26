@extends('layouts.auth')

@section('title', 'Registrasi')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Buat akun</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Daftarkan akun untuk mengakses dashboard TPID.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-300" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="Nama Anda">
        </div>

        <div>
            <label for="employee_id" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">NIP / ID Pegawai <span class="font-normal text-slate-400">(opsional)</span></label>
            <input id="employee_id" name="employee_id" type="text" value="{{ old('employee_id') }}" maxlength="50" autocomplete="off"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="NIP atau ID pegawai">
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="nama@email.com">
        </div>

        <div>
            <label for="institution" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Instansi</label>
            <input id="institution" name="institution" type="text" value="{{ old('institution') }}" required maxlength="255" autocomplete="organization"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="Nama instansi">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="Minimal 8 karakter">
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                placeholder="Ulangi kata sandi">
        </div>

        <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500">
            <i class="fa-solid fa-user-plus mr-2"></i>Daftar dan lanjutkan
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-500 dark:text-blue-400">Masuk</a>
    </p>
@endsection
