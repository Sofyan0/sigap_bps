<div class="space-y-5">
    <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-3">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                    Harga yang Perlu Diwaspadai dan Ditinjau Lanjut
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Sumber: data harga SP2KP {{ $reportAssessment?->market_name ? '· '.$reportAssessment->market_name : '' }}
                    · Periode {{ $startDate }} s/d {{ $endDate }}
                    @if($reportAssessment?->observed_on)
                        · Data terakhir {{ \Carbon\Carbon::parse($reportAssessment->observed_on)->format('d/m/Y') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('dashboard', ['tab' => 'ews', 'region' => $curRegion, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-blue-500">
                <i class="fa-solid fa-shield-halved"></i> Buka EWS
            </a>
        </div>

        @if($reportAssessment === null)
            <div class="py-10 text-center">
                <i class="fa-solid fa-database text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada data harga SP2KP pada filter ini.</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih wilayah atau rentang tanggal lain, atau pastikan data SP2KP sudah dimuat.</p>
            </div>
        @elseif($reportMetrics->isEmpty())
            <div class="py-10 text-center">
                <i class="fa-solid fa-circle-check text-3xl text-emerald-500"></i>
                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada komoditas yang mencapai status Waspada atau Intervensi.</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Hasil ini hanya berlaku untuk data dan periode yang dipilih; tetap lakukan pemantauan rutin.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4">
                <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">Perlu ditinjau segera</p>
                    <p class="mt-1 text-xl font-extrabold text-red-600 dark:text-red-400">
                        {{ $reportMetrics->where('alert_status', 'Intervensi')->count() }} <span class="text-xs font-semibold">komoditas</span>
                    </p>
                </div>
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Perlu diwaspadai</p>
                    <p class="mt-1 text-xl font-extrabold text-amber-600 dark:text-amber-400">
                        {{ $reportMetrics->where('alert_status', 'Waspada')->count() }} <span class="text-xs font-semibold">komoditas</span>
                    </p>
                </div>
                <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Skor risiko keseluruhan</p>
                    <p class="mt-1 text-xl font-extrabold text-blue-600 dark:text-blue-400">
                        {{ $reportAssessment->overall_risk_score }} <span class="text-xs font-semibold">/ 100</span>
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead class="border-y border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Komoditas</th>
                            <th class="px-3 py-3 font-semibold">Harga awal → terkini</th>
                            <th class="px-3 py-3 font-semibold">Perubahan</th>
                            <th class="px-3 py-3 font-semibold">Volatilitas (CV)</th>
                            <th class="px-3 py-3 font-semibold">Skor</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Tindak lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                        @foreach($reportMetrics as $metric)
                            @php($isIntervention = $metric->alert_status === 'Intervensi')
                            <tr>
                                <td class="px-3 py-3 font-bold text-slate-800 dark:text-slate-100">
                                    {{ $metric->commodity->name }}
                                    @if($metric->unit)<span class="font-normal text-slate-400">/{{ $metric->unit }}</span>@endif
                                </td>
                                <td class="px-3 py-3 text-slate-600 dark:text-slate-300">
                                    <span>Rp {{ number_format($metric->first_price, 0, ',', '.') }}</span>
                                    <span class="mx-1 text-slate-400">→</span>
                                    <span class="font-semibold">Rp {{ number_format($metric->price, 0, ',', '.') }}</span>
                                    <span class="mt-1 block text-[10px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($metric->first_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($metric->latest_date)->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 font-bold {{ ($metric->inflation_rate ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $metric->inflation_rate === null ? 'Belum cukup data' : (($metric->inflation_rate > 0 ? '+' : '').number_format($metric->inflation_rate, 2, ',', '.').'%') }}
                                </td>
                                <td class="px-3 py-3 text-slate-600 dark:text-slate-300">{{ number_format($metric->volatility_cv, 2, ',', '.') }}%</td>
                                <td class="px-3 py-3 font-extrabold text-slate-800 dark:text-slate-100">{{ $metric->risk_score }}/100</td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 font-bold {{ $isIntervention ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">
                                        {{ $metric->alert_status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-600 dark:text-slate-300">
                                    {{ $isIntervention
                                        ? 'Tinjau segera bersama TPID; verifikasi harga dan kondisi pasar.'
                                        : 'Pantau perubahan harga dan konfirmasi kondisi pasar/distribusi.' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="mt-4 border-t border-slate-200 pt-3 text-[11px] leading-relaxed text-slate-500 dark:border-slate-700 dark:text-slate-400">
            Status berdasarkan perubahan harga dan volatilitas SP2KP pada rentang terpilih. Ini indikator untuk peninjauan awal, bukan angka inflasi resmi IHK atau bukti penyebab kenaikan harga. Verifikasi kondisi lapangan sebelum menentukan tindak lanjut.
        </p>
    </div>
</div>
