                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-store text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pasar Terpantau</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">{{ $marketStatusCounts['total'] }} Pasar Rakyat</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Intervensi</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">{{ $marketStatusCounts['Intervensi'] }} Pasar</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-eye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Waspada</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">{{ $marketStatusCounts['Waspada'] }} Pasar</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i class="fa-solid fa-circle-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Stabil</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400">{{ $marketStatusCounts['Stabil'] }} Pasar</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-map-location-dot text-indigo-500"></i> Peta Geografis Pasar Rakyat (Situbondo)
                            </h3>
                        </div>
                        <div id="map" class="shadow-inner border border-slate-200 dark:border-slate-700"></div>
                    </div>

                    <div class="lg:col-span-4 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-list-check text-blue-500"></i> Daftar Pasar Utama
                                </h3>
                                <span class="text-xs text-slate-400">Total: {{ count($situbondoMarketsList) }}</span>
                            </div>

                            <div class="mt-3 space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                                @forelse($situbondoMarketsList as $mObj)
                                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700/60 flex items-center justify-between hover:border-blue-500/50 transition cursor-pointer">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $mObj['name'] }}</p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"><i class="fa-solid fa-location-dot text-red-400 mr-1"></i> {{ $mObj['address'] ?? 'Kec. ' . $mObj['district'] }}</p>
                                            @if($mObj['location_verified'] && $mObj['source_url'])
                                                <a href="{{ $mObj['source_url'] }}" target="_blank" rel="noopener noreferrer" class="text-[10px] text-blue-500 hover:underline">Sumber lokasi: OpenStreetMap</a>
                                            @else
                                                <p class="text-[10px] text-amber-600 dark:text-amber-400">Lokasi belum terverifikasi; pin tidak ditampilkan.</p>
                                            @endif
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $mObj['status'] === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($mObj['status'] === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                            {{ $mObj['status'] }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                        Belum ada data pasar aktif di database.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                            <a href="{{ Route::has('kelola-data') ? route('kelola-data') : '#' }}" class="w-full block text-center bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 rounded-xl transition shadow-md">
                                <i class="fa-solid fa-plus-circle mr-1"></i> Kelola Master Data Pasar
                            </a>
                        </div>
                    </div>
                </div>

            <!-- TAB 4: DASHBOARD SEKARKIJANG (GRAFIK PERGERAKAN HARGA HARIAN 7 WILAYAH - LINE CHART) -->
