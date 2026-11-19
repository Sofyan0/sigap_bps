                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Perubahan Harga</p>
                            @if($berandaSummary['average_change'] !== null)
                                @php($averageChange = $berandaSummary['average_change'])
                                <p class="text-sm font-bold {{ $averageChange > 0 ? 'text-red-500' : ($averageChange < 0 ? 'text-emerald-500' : 'text-slate-500') }}">
                                    {{ $averageChange > 0 ? '+' : '' }}{{ number_format($averageChange, 2, ',', '.') }}%
                                    <span class="text-[10px] font-normal text-slate-400">({{ $startDate }} – {{ $endDate }})</span>
                                </p>
                            @else
                                <p class="text-sm font-bold text-slate-500">Belum ada data pembanding</p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Naik</p>
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">{{ $berandaSummary['commodities_up'] }} Komoditas</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-down text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Turun</p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $berandaSummary['commodities_down'] }} Komoditas</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-layer-group text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Terpantau</p>
                            <p class="text-base font-bold text-blue-600 dark:text-blue-400">{{ $berandaSummary['commodities_monitored'] }} Komoditas</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-emerald-500"></i> Grafik Pergerakan Harga Harian Pasar Rakyat Panji (Data Real MySQL)
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Filter Terpasang: {{ $startDate }} s/d {{ $endDate }} &bull; Tanggal tanpa pembaruan harga menampilkan harga terakhir yang tercatat.</p>
                        </div>
                        <a href="?tab=monitoring_harga" class="text-xs font-bold text-blue-500 hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="relative h-[420px]">
                        @if(empty($dbChartDatasets))
                            <div class="flex h-full items-center justify-center text-center text-sm text-slate-500 dark:text-slate-400">
                                Tidak ada data harga untuk komoditas ini pada rentang tanggal terpilih atau sebelum tanggal mulai.
                            </div>
                        @else
                            <canvas id="priceChart"></canvas>
                        @endif
                    </div>
                </div>

            <!-- TAB 2: MONITORING HARGA (SIKEPO & SP2KP) -->
