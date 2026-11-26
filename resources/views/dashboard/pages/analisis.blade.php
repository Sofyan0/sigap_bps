                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <i class="fa-solid fa-robot text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Model Forecasting</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">{{ $analysisRun?->model_name ?? 'Belum tersedia' }}</p>
                            <span class="text-[10px] text-slate-400 font-bold">
                                @if($analysisRun?->model_accuracy !== null)
                                    Akurasi Model {{ number_format((float) $analysisRun->model_accuracy, 1, ',', '.') }}%
                                @else
                                    Belum ada hasil evaluasi
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-chart-line-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proyeksi Puncak Harga</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">{{ $analysisRun?->projected_peak_date?->translatedFormat('d M Y') ?? 'Belum tersedia' }}</p>
                            <span class="text-[10px] text-slate-400">Tanggal puncak dari hasil analisis tersimpan</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-percent text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perkiraan Inflasi M-o-M</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">
                                @if($analysisRun?->expected_inflation_percent !== null)
                                    {{ number_format((float) $analysisRun->expected_inflation_percent, 2, ',', '.') }}%
                                @else
                                    Belum tersedia
                                @endif
                            </p>
                            <span class="text-[10px] text-slate-400">Perkiraan inflasi dari hasil analisis tersimpan</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-bullseye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Faktor Pengaruh Utama</p>
                            <p class="text-base font-black text-blue-600 dark:text-blue-400">{{ $analysisRun?->main_factor ?? 'Belum tersedia' }}</p>
                            <span class="text-[10px] text-slate-400">
                                @if($analysisRun?->factor_correlation_percent !== null)
                                    Korelasi {{ number_format((float) $analysisRun->factor_correlation_percent, 1, ',', '.') }}%
                                @else
                                    Belum ada data faktor
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-area text-blue-500"></i> Analisis Harga Komoditas SP2KP
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Harga aktual SP2KP {{ $selectedRegion }} pada periode terpilih; proyeksi hanya ditampilkan jika tersedia dari model tersimpan.
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1 text-xs">
                                <span class="text-slate-400">Komoditas:</span>
                                <span class="font-bold text-amber-500">{{ $analysisCommodity }}</span>
                            </div>
                        </div>

                        @if($analysisChartLabels !== [])
                            <div class="relative h-[300px]">
                                <canvas id="chartPrediksiAnalisis"></canvas>
                            </div>
                        @else
                            <div class="flex h-[300px] items-center justify-center rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                Belum ada harga SP2KP atau proyeksi model untuk komoditas dan periode yang dipilih.
                            </div>
                        @endif
                    </div>

                    <div class="lg:col-span-4 space-y-4">
                        <div class="bg-gradient-to-br from-indigo-900/80 to-slate-900 p-4 rounded-2xl border border-indigo-500/40 shadow-xl space-y-2">
                            <h3 class="text-xs font-extrabold text-amber-400 flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Insight Analisis
                            </h3>
                            <p class="text-[11px] text-slate-200 leading-relaxed italic">
                                {{ $analysisRun?->insight ?? 'Belum ada insight analisis yang tersimpan. Grafik menampilkan riwayat harga aktual yang tersedia.' }}
                            </p>
                        </div>
                    </div>
                </div>

            <!-- TAB 9: LAPORAN -->
