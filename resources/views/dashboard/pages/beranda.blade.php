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
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-scale-balanced text-blue-500"></i> Perbandingan Harga SIKEPO dan SP2KP
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ $startDate }} s/d {{ $endDate }} &bull; Menampilkan semua komoditas dari kedua sumber. Selisih dihitung sebagai SP2KP dikurangi SIKEPO jika tanggal dan satuan cocok.
                        </p>
                    </div>

                    @if($priceComparisons->isEmpty())
                        <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            Belum ada data komoditas SIKEPO atau SP2KP di database pada rentang terpilih.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left text-xs">
                                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Komoditas</th>
                                        <th class="px-3 py-2 font-semibold">Tanggal SIKEPO</th>
                                        <th class="px-3 py-2 font-semibold">Tanggal SP2KP</th>
                                        <th class="px-3 py-2 font-semibold">SIKEPO (Pasar Panji)</th>
                                        <th class="px-3 py-2 font-semibold">SP2KP</th>
                                        <th class="px-3 py-2 font-semibold">Selisih (SP2KP − SIKEPO)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                                    @foreach($priceComparisons as $comparison)
                                        <tr>
                                            <td class="px-3 py-2.5 font-semibold text-slate-800 dark:text-slate-200">{{ $comparison['commodity'] }}</td>
                                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">
                                                {{ $comparison['sikepo_date'] ? \Carbon\Carbon::parse($comparison['sikepo_date'])->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">
                                                {{ $comparison['sp2kp_date'] ? \Carbon\Carbon::parse($comparison['sp2kp_date'])->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200">
                                                @if($comparison['sikepo_price'] !== null)
                                                    Rp {{ number_format($comparison['sikepo_price'], 0, ',', '.') }}
                                                    @if($comparison['sikepo_unit'])<span class="text-slate-400">/{{ $comparison['sikepo_unit'] }}</span>@endif
                                                @else
                                                    <span class="text-slate-400">Tidak tersedia</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200">
                                                @if($comparison['sp2kp_price'] !== null)
                                                    Rp {{ number_format($comparison['sp2kp_price'], 0, ',', '.') }}
                                                    @if($comparison['sp2kp_unit'])<span class="text-slate-400">/{{ $comparison['sp2kp_unit'] }}</span>@endif
                                                @else
                                                    <span class="text-slate-400">Tidak tersedia</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 font-bold {{ $comparison['difference'] > 0 ? 'text-red-600 dark:text-red-400' : ($comparison['difference'] < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500') }}">
                                                @if($comparison['difference'] === null)
                                                    <span class="text-amber-600 dark:text-amber-400">{{ $comparison['difference_reason'] }}</span>
                                                @else
                                                    {{ $comparison['difference'] > 0 ? '+' : ($comparison['difference'] < 0 ? '-' : '') }}Rp {{ number_format(abs($comparison['difference']), 0, ',', '.') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-emerald-500"></i> Grafik Pergerakan Harga Harian SIKEPO dan SP2KP
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Filter: {{ $startDate }} s/d {{ $endDate }} &bull; Garis utuh: SIKEPO; garis putus-putus: SP2KP. Hanya tanggal dengan data database yang ditampilkan.</p>
                        </div>
                        <a href="?tab=monitoring_harga" class="text-xs font-bold text-blue-500 hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="relative h-[420px]">
                        @if(empty($priceSourceChart['datasets']))
                            <div class="flex h-full items-center justify-center text-center text-sm text-slate-500 dark:text-slate-400">
                                Belum ada data SIKEPO atau SP2KP dari database untuk komoditas dan rentang tanggal terpilih.
                            </div>
                        @else
                            <canvas id="priceChart"></canvas>
                        @endif
                    </div>
                </div>

            <!-- TAB 2: MONITORING HARGA (SIKEPO & SP2KP) -->
