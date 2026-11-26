                @php
                    $interventionMetrics = $interventionAssessment?->metrics ?? collect();
                    $interventionPriorityCount = $interventionMetrics
                        ->where('alert_status', 'Intervensi')
                        ->count();
                    $interventionWatchCount = $interventionMetrics
                        ->where('alert_status', 'Waspada')
                        ->count();
                    $interventionRisingCount = $interventionMetrics
                        ->filter(fn ($metric) => ($metric->inflation_rate ?? 0) > 0)
                        ->count();
                    $priorityMetric = $interventionMetrics->first();
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-bullseye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prioritas Intervensi</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">{{ $interventionPriorityCount }} Komoditas</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Risiko harga tinggi dari SP2KP</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-eye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perlu Diwaspadai</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">{{ $interventionWatchCount }} Komoditas</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Pantau perkembangan harga</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Harga Naik</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">{{ $interventionRisingCount }} Komoditas</p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Dibanding awal periode terpilih</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <i class="fa-solid fa-calendar-day text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data SP2KP Terakhir</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">
                                {{ $interventionAssessment?->observed_on ? \Carbon\Carbon::parse($interventionAssessment->observed_on)->translatedFormat('d M Y') : '—' }}
                            </p>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $interventionAssessment?->market_name ?? 'Belum ada data' }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-bullhorn text-blue-500"></i> Prioritas Respons TPID berdasarkan SP2KP
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Indikator perubahan harga untuk mendukung strategi 4K; bukan catatan realisasi program.
                                </p>
                            </div>
                            <span class="text-xs bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 px-2.5 py-1 rounded-lg font-bold">
                                {{ $interventionAssessment?->market_name ?? 'SP2KP' }}
                            </span>
                        </div>

                        <div class="overflow-x-auto text-[11px] max-h-[400px] overflow-y-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                    <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                        <th class="pb-2">Komoditas</th>
                                        <th class="pb-2 text-right">Harga SP2KP Terakhir</th>
                                        <th class="pb-2 text-center">Perubahan Periode</th>
                                        <th class="pb-2">Rekomendasi Tindak Lanjut</th>
                                        <th class="pb-2 text-center">Status Harga</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                                    @forelse($interventionMetrics as $metric)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                            <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $metric->commodity->name }}</td>
                                            <td class="py-2.5 text-right font-semibold text-slate-900 dark:text-white">
                                                Rp {{ number_format((float) $metric->price, 0, ',', '.') }}{{ $metric->unit ? '/'.$metric->unit : '' }}
                                            </td>
                                            <td class="py-2.5 text-center font-semibold {{ ($metric->inflation_rate ?? 0) > 0 ? 'text-red-500' : (($metric->inflation_rate ?? 0) < 0 ? 'text-emerald-500' : 'text-slate-500 dark:text-slate-400') }}">
                                                {{ $metric->inflation_rate === null ? 'Belum cukup data' : number_format((float) $metric->inflation_rate, 1, ',', '.').'%' }}
                                            </td>
                                            <td class="py-2.5 text-slate-600 dark:text-slate-300">
                                                {{ $metric->alert_status === 'Intervensi'
                                                    ? 'Verifikasi pasokan; pertimbangkan operasi pasar atau GPM.'
                                                    : ($metric->alert_status === 'Waspada'
                                                        ? 'Pantau harga dan koordinasikan ketersediaan.'
                                                        : 'Lanjutkan pemantauan rutin.') }}
                                            </td>
                                            <td class="py-2.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full font-bold {{ $metric->alert_status === 'Intervensi' ? 'bg-red-500/10 text-red-600' : ($metric->alert_status === 'Waspada' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600') }}">
                                                    {{ $metric->alert_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-6 text-center text-slate-500 dark:text-slate-400">
                                                Belum ada data harga SP2KP untuk wilayah, komoditas, dan periode yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="lg:col-span-4 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-line text-blue-500"></i> Perubahan Harga Periode
                                </h3>
                                @if($priorityMetric?->inflation_rate !== null)
                                    <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $priorityMetric->inflation_rate > 0 ? 'bg-red-500/10 text-red-600' : 'bg-emerald-500/10 text-emerald-600' }}">
                                        {{ number_format((float) $priorityMetric->inflation_rate, 1, ',', '.') }}%
                                    </span>
                                @endif
                            </div>

                            @if($priorityMetric)
                                <p class="mt-3 text-xs font-bold text-slate-800 dark:text-white">{{ $priorityMetric->commodity->name }}</p>
                                @if($priorityMetric->inflation_rate !== null)
                                    <div class="mt-3 space-y-2 text-xs">
                                        <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700/60 space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-slate-600 dark:text-slate-300">Awal periode · {{ \Carbon\Carbon::parse($priorityMetric->first_date)->translatedFormat('d M Y') }}</span>
                                                <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format((float) $priorityMetric->first_price, 0, ',', '.') }}/{{ $priorityMetric->unit }}</span>
                                            </div>
                                            <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-700">
                                                <span class="font-semibold text-slate-600 dark:text-slate-300">Akhir periode · {{ \Carbon\Carbon::parse($priorityMetric->latest_date)->translatedFormat('d M Y') }}</span>
                                                <span class="font-bold text-slate-800 dark:text-white">Rp {{ number_format((float) $priorityMetric->price, 0, ',', '.') }}/{{ $priorityMetric->unit }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Belum cukup titik data dalam periode untuk membandingkan harga.</p>
                                @endif
                            @else
                                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Belum ada data harga SP2KP pada filter ini.</p>
                            @endif
                            <p class="mt-3 text-[10px] text-slate-500 dark:text-slate-400">
                                Perubahan harga tidak membuktikan dampak program. Pengukuran dampak memerlukan jadwal intervensi dan data harga sebelum/sesudah pelaksanaan.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                            <a href="?tab=monitoring_harga&amp;source=sp2kp" class="w-full block text-center bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 rounded-xl transition shadow-md">
                                <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Buka Monitoring Harga SP2KP
                            </a>
                        </div>
                    </div>
                </div>

            <!-- TAB 8: ANALISIS & PREDIKSI -->
