                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('dashboard.settings.update') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- KIRI: INFORMASI PROFIL -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white/90 dark:bg-slate-800/90 p-6 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl text-center space-y-4">
                            <div class="relative w-24 h-24 mx-auto">
                                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center font-black text-3xl shadow-xl border-4 border-white dark:border-slate-800">
                                    <i class="fa-solid fa-user-shield"></i>
                                </div>
                                <button type="button" class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-500 text-white p-2 rounded-full shadow-lg transition text-xs" aria-label="Ubah foto profil">
                                    <i class="fa-solid fa-camera"></i>
                                </button>
                            </div>
                            
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $dashboardSettings->admin_name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $dashboardSettings->email ?: 'Email belum diatur' }}</p>
                                <span class="inline-block mt-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[10px] font-bold px-3 py-0.5 rounded-full">
                                    Super Administrator TPID
                                </span>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 text-left text-xs space-y-2 text-slate-600 dark:text-slate-300">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Instansi:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $dashboardSettings->institution }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">NIP:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $dashboardSettings->employee_id ?: 'Belum diatur' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Terakhir Login:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Belum tersedia</span>
                                </div>
                            </div>
                        </div>

                        <!-- KARTU PREFERENSI NOTIFIKASI -->
                        <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3 text-xs">
                            <h4 class="font-extrabold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-700/60 pb-2 flex items-center gap-2">
                                <i class="fa-solid fa-bell text-amber-500"></i> Notifikasi Sektor Inflasi
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Alert Email EWS Kenaikan Harga</span>
                                    <span class="flex items-center gap-2">
                                        <input type="hidden" name="email_ews_enabled" value="0">
                                        <input type="checkbox" name="email_ews_enabled" value="1" {{ old('email_ews_enabled', $dashboardSettings->email_ews_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    </span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Laporan Mingguan IPH Otomatis</span>
                                    <span class="flex items-center gap-2">
                                        <input type="hidden" name="weekly_iph_enabled" value="0">
                                        <input type="checkbox" name="weekly_iph_enabled" value="1" {{ old('weekly_iph_enabled', $dashboardSettings->weekly_iph_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: FORM EDIT PROFIL -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="bg-white/90 dark:bg-slate-800/90 p-6 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                            <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-user-pen text-blue-500"></i> Perbarui Data Profil Pengguna
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Ubah informasi personal dan instansi akun penanggung jawab TPID</p>
                            </div>

                            <div class="space-y-4 text-xs font-semibold">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Admin</label>
                                        <input type="text" name="admin_name" value="{{ old('admin_name', $dashboardSettings->admin_name) }}" required maxlength="255" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                        @error('admin_name')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">NIP / ID Pegawai</label>
                                        <input type="text" name="employee_id" value="{{ old('employee_id', $dashboardSettings->employee_id) }}" maxlength="50" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                        @error('employee_id')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Email Admin</label>
                                        <input type="email" name="email" value="{{ old('email', $dashboardSettings->email) }}" maxlength="255" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                        @error('email')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Instansi</label>
                                        <input type="text" name="institution" value="{{ old('institution', $dashboardSettings->institution) }}" required maxlength="255" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                        @error('institution')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
                                    </button>
                                </div>
                            </div>
                            </div>
                        </form>
                    </div>
                </div>
