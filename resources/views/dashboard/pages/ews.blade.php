                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-amber-500"></i> Early Warning System (EWS) Inflasi
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Data SP2KP {{ $ewsAssessment?->market_name ? '· '.$ewsAssessment->market_name : '' }}
                                {{ $ewsAssessment?->observed_on ? '· Data terakhir '.$ewsAssessment->observed_on : '' }}
                            </p>
                        </div>
                        <span class="text-xs bg-red-500/10 text-red-500 border border-red-500/20 px-3 py-1 rounded-full font-bold">
                            Risiko Inflasi: {{ $ewsAssessment?->overall_risk_score ?? '—' }} ({{ $ewsAssessment?->overall_status ?? 'Belum ada data' }})
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Indikator perubahan harga dari awal hingga akhir periode dan volatilitas harga (CV). Skor risiko: 70% kenaikan harga dan 30% CV; status Waspada mulai 45 dan Intervensi mulai 70. Ini indikator tekanan harga, bukan angka inflasi resmi IHK.
                    </p>

                    <div class="overflow-x-auto text-[11px] max-h-[450px] overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                    <th class="pb-2">Komoditas</th>
                                    <th class="pb-2 text-right">Harga Terakhir (SP2KP)</th>
                                    <th class="pb-2 text-center">Perubahan Harga Periode</th>
                                    <th class="pb-2 text-center">Volatilitas (CV)</th>
                                    <th class="pb-2 text-center">Risk Score (0-100)</th>
                                    <th class="pb-2 text-center">Status Peringatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-semibold">
                                @forelse(($ewsAssessment?->metrics ?? collect()) as $metric)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                        <td class="py-2.5 text-slate-800 dark:text-slate-200 font-bold">{{ $metric->commodity->name }}</td>
                                        <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">Rp {{ number_format((float) $metric->price, 0, ',', '.') }}{{ $metric->unit ? '/'.$metric->unit : '' }}</td>
                                        <td class="py-2.5 text-center {{ ($metric->inflation_rate ?? 0) > 0 ? 'text-red-500' : (($metric->inflation_rate ?? 0) < 0 ? 'text-emerald-500' : 'text-slate-500 dark:text-slate-400') }}">
                                            {{ $metric->inflation_rate === null ? 'Belum cukup data' : number_format((float) $metric->inflation_rate, 1, ',', '.').'%' }}
                                        </td>
                                        <td class="py-2.5 text-center text-slate-500 dark:text-slate-400">{{ number_format((float) $metric->volatility_cv, 1, ',', '.') }}%</td>
                                        <td class="py-2.5 text-center font-black {{ $metric->risk_score >= 70 ? 'text-red-500' : ($metric->risk_score >= 45 ? 'text-amber-500' : 'text-emerald-500') }}">{{ $metric->risk_score }}</td>
                                        <td class="py-2.5 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $metric->alert_status === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($metric->alert_status === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                                {{ $metric->alert_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-slate-500 dark:text-slate-400">
                                            Belum ada data SP2KP untuk komoditas dan periode yang dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- TAB 6: STOK DAN PASOKAN -->
