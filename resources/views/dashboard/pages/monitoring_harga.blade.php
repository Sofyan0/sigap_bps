                <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-2.5 gap-2">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-store text-blue-500"></i>
                                Monitoring Harga Komoditas
                                <span class="px-2 py-0.5 rounded-lg text-xs font-black {{ $dataSource === 'sp2kp' ? 'bg-amber-500/20 text-amber-500 border border-amber-500/30' : 'bg-blue-500/20 text-blue-500 border border-blue-500/30' }}">
                                    Sumber: {{ $dataSource === 'sp2kp' ? 'SP2KP (Kemendag)' : 'SIKEPO (Pasar Panji Real)' }}
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $uploadedMonitoringRows->isNotEmpty() ? ($dataSource === 'sp2kp' ? 'Menampilkan data SP2KP yang tersimpan di database' : 'Menampilkan data SIKEPO yang tersimpan di database') : ($dataSource === 'sp2kp' ? 'Data laporan SP2KP lokal; ambil data terbaru dari Kemendag untuk rentang terpilih' : ($usingSikepoMonitoringData ? 'Data SIKEPO lokal; ambil harga terbaru untuk rentang tanggal terpilih' : 'Data terintegrasi langsung dari tabel MySQL harga_pasar Kabupaten Situbondo')) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="?tab=monitoring_harga&source=sikeppo" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $dataSource === 'sikeppo' ? 'bg-blue-600 text-white shadow' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-white' }}">
                                <i class="fa-solid fa-building-store"></i> SIKEPO (Panji)
                            </a>
                            <a href="?tab=monitoring_harga&source=sp2kp" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $dataSource === 'sp2kp' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-white' }}">
                                <i class="fa-solid fa-globe"></i> SP2KP (Kemendag)
                            </a>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            {{ session('warning') }}
                        </div>
                    @endif
                    @error('sp2kp')
                        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror
                    @error('sikepo')
                        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror
                    @error('excel_file')
                        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror
                    @if($dataSource === 'sp2kp')
                        <form method="POST" action="{{ route('monitoring-harga.fetch-sp2kp') }}" class="flex flex-col sm:flex-row sm:items-end gap-2 rounded-xl border border-slate-200 dark:border-slate-700 p-3">
                            @csrf
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            <p class="flex-1 text-[11px] text-slate-500 dark:text-slate-400">
                                Ambil harga harian SP2KP untuk Situbondo sesuai rentang tanggal yang dipilih.
                            </p>
                            <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-amber-500">
                                <i class="fa-solid fa-cloud-arrow-down mr-1"></i> Ambil Data SP2KP
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('monitoring-harga.fetch-sikepo') }}" class="flex flex-col sm:flex-row sm:items-end gap-2 rounded-xl border border-slate-200 dark:border-slate-700 p-3">
                            @csrf
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            <p class="flex-1 text-[11px] text-slate-500 dark:text-slate-400">
                                Ambil data harga SIKEPO Pasar Rakyat Panji sesuai rentang tanggal yang dipilih.
                            </p>
                            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-blue-500">
                                <i class="fa-solid fa-cloud-arrow-down mr-1"></i> Ambil Data SIKEPO
                            </button>
                        </form>
                    @endif

                    <div class="overflow-x-auto text-[11px] max-h-[380px] overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                    <th class="pb-2">No</th>
                                    <th class="pb-2">Nama Komoditas</th>
                                    <th class="pb-2 text-right">Harga {{ $usingSpreadsheetMonitoringData ? 'Terakhir' : 'Rata-rata' }} ({{ $sourceDisplayName }})</th>
                                    @if($usingSpreadsheetMonitoringData)
                                        <th class="pb-2 text-center">Tanggal Data</th>
                                    @endif
                                    <th class="pb-2 text-right text-amber-500 dark:text-amber-400">HAP (Harga Acuan)</th>
                                    <th class="pb-2 text-center">Satuan</th>
                                    <th class="pb-2 text-center">Perubahan</th>
                                    <th class="pb-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-semibold">
                                @php $rowNo = 1; @endphp
                                @foreach($commoditiesMaster as $c)
                                    @if($usingSpreadsheetMonitoringData
                                        ? ($selectedCommodity === 'all' || $selectedCommodity === $c['name'])
                                        : ($selectedCommodity === 'all' || str_contains(strtolower($c['name']), strtolower($selectedCommodity))))
                                        @php
                                            $finalPrice = $c['price'];
                                            $isOverHap = $c['hap'] !== null && $finalPrice > $c['hap'];
                                        @endphp
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                            <td class="py-2 text-slate-400">{{ $rowNo++ }}</td>
                                            <td class="py-2 text-slate-800 dark:text-slate-200 font-bold">{{ $c['name'] }}</td>
                                            <td class="py-2 text-right font-extrabold text-slate-900 dark:text-white">Rp {{ number_format($finalPrice, 0, ',', '.') }}</td>
                                            @if($usingSpreadsheetMonitoringData)
                                                <td class="py-2 text-center text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($c['date'])->translatedFormat('d M Y') }}</td>
                                            @endif
                                            <td class="py-2 text-right font-bold {{ $isOverHap ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                @if($c['hap'] !== null)
                                                    Rp {{ number_format($c['hap'], 0, ',', '.') }}
                                                @else
                                                    <span class="text-slate-400">Tidak tersedia</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-center text-slate-500 dark:text-slate-400">{{ $c['unit'] === '—' ? '—' : '/' . $c['unit'] }}</td>
                                            <td class="py-2 text-center">
                                                @if($c['change'] > 0)
                                                    <span class="text-red-500 font-bold"><i class="fa-solid fa-arrow-up mr-1"></i>+{{ $c['change'] }}%</span>
                                                @elseif($c['change'] < 0)
                                                    <span class="text-emerald-500 font-bold"><i class="fa-solid fa-arrow-down mr-1"></i>{{ $c['change'] }}%</span>
                                                @else
                                                    <span class="text-slate-400 font-normal">0,0%</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c['status'] === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($c['status'] === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : ($c['status'] === 'Acuan HAP tidak tersedia' ? 'bg-slate-500/10 text-slate-500 border border-slate-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20')) }}">
                                                    {{ $c['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                @if($usingSpreadsheetMonitoringData && $rowNo === 1)
                                    <tr>
                                        <td colspan="8" class="py-4 text-center text-slate-500">Tidak ada harga {{ $sourceDisplayName }} pada rentang tanggal ini.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GRAFIK TREN HARGA LEBAR PENUH -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                    <div class="xl:col-span-12 bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-2.5">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-blue-500"></i> Tren Grafik Pergerakan Harga Komoditas ({{ $sourceDisplayName }})
                            </h3>
                            <span class="text-[10px] bg-slate-100 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded-lg font-semibold">{{ $uploadedMonitoringRows->isNotEmpty() ? 'Data tersimpan di database' : ($usingSikepoMonitoringData ? 'Data harian SIKEPO Pasar Rakyat Panji' : ($usingSp2kpMonitoringData ? 'Harga harian berdasarkan laporan SP2KP Situbondo, 1-17 November 2026' : $sourceDisplayName . ' Live Data')) }}</span>
                        </div>
                        @if(empty($dbChartDatasets))
                            <div class="flex h-[360px] items-center justify-center text-center text-sm text-slate-500 dark:text-slate-400">
                                Tidak ada data harga {{ $sourceDisplayName }} pada rentang tanggal ini maupun sebelum tanggal mulai. Pilih rentang tanggal lain.
                            </div>
                        @else
                            <div class="relative h-[360px]">
                                <canvas id="chartHargaTren"></canvas>
                            </div>
                        @endif
                    </div>
                </div>

            <!-- TAB 3: MONITORING PASAR -->
