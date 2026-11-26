                @php
                    $stockMetrics = $stockAssessment?->metrics ?? collect();
                    $risingPriceCount = $stockMetrics
                        ->filter(fn ($metric) => ($metric->inflation_rate ?? 0) > 0)
                        ->count();
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-boxes-stacked text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas SP2KP</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">{{ $stockMetrics->count() }}</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Sesuai filter komoditas dan tanggal</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Harga Naik</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">{{ $risingPriceCount }} Komoditas</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Dibanding awal periode terpilih</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-calendar-day text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data SP2KP Terakhir</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">
                                {{ $stockAssessment?->observed_on ? \Carbon\Carbon::parse($stockAssessment->observed_on)->translatedFormat('d M Y') : '—' }}
                            </p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $stockAssessment?->market_name ?? 'Belum ada data' }}</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 rounded-xl">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sumber Pemantauan</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">SP2KP</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Harga pasar, bukan data stok</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-12 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-scale-balanced text-blue-500"></i> Pemantauan Harga dan Indikasi Pasokan
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Sumber data: SP2KP · {{ $stockAssessment?->market_name ?? $selectedRegion }} · Data stok dan kebutuhan tidak tersedia dari sumber ini.
                                </p>
                            </div>
                            <span class="text-xs bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 px-2.5 py-1 rounded-lg font-bold">
                                Harga SP2KP
                            </span>
                        </div>

                        <div class="overflow-x-auto text-[11px] max-h-[400px] overflow-y-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                    <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                        <th class="pb-2">Komoditas</th>
                                        <th class="pb-2 text-right">Harga SP2KP Terakhir</th>
                                        <th class="pb-2 text-center">Perubahan Harga Periode</th>
                                        <th class="pb-2 text-center">Stok Gudang</th>
                                        <th class="pb-2 text-center">Kebutuhan/Bulan</th>
                                        <th class="pb-2 text-center">Ketahanan Stok</th>
                                        <th class="pb-2 text-center">Status Harga</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                                    @forelse($stockMetrics as $metric)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                            <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $metric->commodity->name }}</td>
                                            <td class="py-2.5 text-right font-semibold text-slate-900 dark:text-white">
                                                Rp {{ number_format((float) $metric->price, 0, ',', '.') }}{{ $metric->unit ? '/'.$metric->unit : '' }}
                                            </td>
                                            <td class="py-2.5 text-center font-semibold {{ ($metric->inflation_rate ?? 0) > 0 ? 'text-red-500' : (($metric->inflation_rate ?? 0) < 0 ? 'text-emerald-500' : 'text-slate-500 dark:text-slate-400') }}">
                                                {{ $metric->inflation_rate === null ? 'Belum cukup data' : number_format((float) $metric->inflation_rate, 1, ',', '.').'%' }}
                                            </td>
                                            <td class="py-2.5 text-center text-slate-400">Tidak tersedia</td>
                                            <td class="py-2.5 text-center text-slate-400">Tidak tersedia</td>
                                            <td class="py-2.5 text-center text-slate-400">Tidak tersedia</td>
                                            <td class="py-2.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full font-bold {{ $metric->alert_status === 'Intervensi' ? 'bg-red-500/10 text-red-600' : ($metric->alert_status === 'Waspada' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600') }}">
                                                    {{ $metric->alert_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-6 text-center text-slate-500 dark:text-slate-400">
                                                Belum ada data harga SP2KP untuk wilayah, komoditas, dan periode yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">
                            Status harga menunjukkan tekanan perubahan harga, bukan konfirmasi defisit atau surplus fisik. Data stok, kebutuhan, dan distribusi memerlukan sumber kuantitas tersendiri.
                        </p>
                    </div>
                </div>

            <!-- TAB 7: INTERVENSI TPID -->
