<div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-table text-amber-500"></i>
                Rekapitulasi Nilai Perubahan IPH Mingguan
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Wilayah Tapal Kuda - periode M4 September sampai M5 Oktober 2026 (%)</p>
        </div>
    </div>

    <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
        <h4 class="text-sm font-bold text-slate-800 dark:text-white mb-3">Grafik Perubahan IPH Mingguan Antar Kabupaten/Kota</h4>
        <div class="relative h-[380px] w-full">
            <canvas id="iphWeeklyChart" aria-label="Grafik nilai IPH mingguan tujuh kabupaten dan kota" role="img"></canvas>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="min-w-[1450px] w-full border-collapse text-xs">
            <thead>
                <tr class="bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-200">
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-center">No.</th>
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-left">Kode BPS</th>
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-left">Kabupaten / Kota</th>
                    <th scope="colgroup" colspan="{{ count($iphWeekColumns) }}" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2 text-center">Perubahan IPH Mingguan (%)</th>
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-right">Rata-rata</th>
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-right">Tertinggi</th>
                    <th scope="col" rowspan="2" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-3 text-right">Terendah</th>
                    <th scope="col" rowspan="2" class="border-b border-slate-200 dark:border-slate-700 px-3 py-3 text-left">Status &amp; sumber data</th>
                </tr>
                <tr class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    @foreach($iphWeekColumns as $week)
                        <th scope="col" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2 text-right whitespace-nowrap">{{ $week }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($iphWeeklySummary as $index => $row)
                    @php
                        $rowStyle = $row['type'] === 'Proksi'
                            ? 'bg-amber-50/80 dark:bg-amber-950/25'
                            : ($row['type'] === 'Pembanding' ? 'bg-slate-100 dark:bg-slate-900/80 font-bold' : 'bg-white dark:bg-slate-800/70');
                    @endphp
                    <tr class="{{ $rowStyle }} hover:bg-blue-50 dark:hover:bg-slate-700/60">
                        <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-center text-slate-500">{{ $index + 1 }}</td>
                        <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">{{ $row['code'] }}</td>
                        <th scope="row" class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-left text-slate-800 dark:text-white whitespace-nowrap">{{ $row['name'] }}</th>
                        @foreach($row['values'] as $value)
                            <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-right tabular-nums {{ $value > 0 ? 'text-red-600 dark:text-red-400' : ($value < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500') }}">
                                {{ number_format((float) $value, 2, ',', '.') }}
                            </td>
                        @endforeach
                        <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-right font-bold tabular-nums text-slate-800 dark:text-white">{{ number_format($row['average'], 2, ',', '.') }}</td>
                        <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-right tabular-nums text-red-600 dark:text-red-400">{{ number_format($row['highest'], 2, ',', '.') }}</td>
                        <td class="border-b border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 text-right tabular-nums text-slate-700 dark:text-slate-300">{{ number_format($row['lowest'], 2, ',', '.') }}</td>
                        <td class="border-b border-slate-200 dark:border-slate-700 px-3 py-2.5 min-w-[250px]">
                            <span class="mr-1 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $row['type'] === 'Proksi' ? 'bg-amber-200/80 text-amber-800 dark:bg-amber-400/20 dark:text-amber-300' : ($row['type'] === 'Aktual' ? 'bg-blue-100 text-blue-800 dark:bg-blue-400/20 dark:text-blue-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200') }}">{{ $row['type'] }}</span>
                            <span class="text-slate-600 dark:text-slate-300">{{ $row['source'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-[11px] text-slate-500 dark:text-slate-400">
        Rata-rata, nilai tertinggi, dan terendah dihitung dari enam nilai mingguan. Baris biru/putih menunjukkan data aktual; baris kuning menunjukkan nilai proksi dari sheet rincian workbook. Geser tabel ke samping untuk melihat seluruh kolom.
    </p>
</div>

<!-- PERBANDINGAN HARGA 20 KOMODITAS SP2KP -->
<div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-3">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-emerald-500"></i>
                Perbandingan Harga Antar Kabupaten/Kota Sekarkijang - SP2KP
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Tren {{ $sekarkijangSelectedCommodity ?? 'komoditas' }} hingga {{ \Carbon\Carbon::parse($sekarkijangComparisonEndDate)->translatedFormat('d M Y') }}.
                Situbondo memakai data SP2KP bila tersedia; enam kabupaten/kota lain memakai data Excel aktual.
            </p>
        </div>

        <form method="GET" action="" class="flex flex-wrap items-center gap-2 bg-slate-50 dark:bg-slate-900 p-2 rounded-xl border border-slate-200 dark:border-slate-700/80">
            <input type="hidden" name="tab" value="sekarkijang">
            <input type="hidden" name="source" value="sp2kp">
            <input type="hidden" name="commodity" value="all">

            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                <label for="sekarkijangCommodity">Komoditas:</label>
                <select id="sekarkijangCommodity" name="sekarkijang_commodity" onchange="this.form.submit()" class="max-w-[220px] bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none">
                    @foreach($sekarkijangCommodityOptions as $commodityName)
                        <option value="{{ $commodityName }}" {{ $sekarkijangSelectedCommodity === $commodityName ? 'selected' : '' }}>{{ $commodityName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2">
                <label for="sekarkijangMonth">Bulan:</label>
                <input id="sekarkijangMonth" type="month" value="{{ $periodStart->format('Y-m') }}" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                <label for="sekarkijangWeek">Minggu:</label>
                <select id="sekarkijangWeek" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                    <option value="" {{ $selectedSekarkijangWeek === '' ? 'selected' : '' }}>Pilih minggu</option>
                    <option value="all" {{ $selectedSekarkijangWeek === 'all' ? 'selected' : '' }}>Semua minggu</option>
                    <option value="1" {{ $selectedSekarkijangWeek === '1' ? 'selected' : '' }}>Minggu Pertama</option>
                    <option value="2" {{ $selectedSekarkijangWeek === '2' ? 'selected' : '' }}>Minggu Kedua</option>
                    <option value="3" {{ $selectedSekarkijangWeek === '3' ? 'selected' : '' }}>Minggu Ketiga</option>
                    <option value="4" {{ $selectedSekarkijangWeek === '4' ? 'selected' : '' }}>Minggu Keempat</option>
                    <option value="5" {{ $selectedSekarkijangWeek === '5' ? 'selected' : '' }}>Minggu Kelima</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2">
                <span>Tanggal:</span>
                <input id="sekarkijangStartDate" type="date" name="start_date" value="{{ $sekarkijangComparisonStartDate }}" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                <span>s/d</span>
                <input id="sekarkijangEndDate" type="date" name="end_date" value="{{ $sekarkijangComparisonEndDate }}" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif
    @error('excel_file')
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400">
            {{ $message }}
        </div>
    @enderror

    <details class="rounded-xl border border-slate-200 dark:border-slate-700">
        <summary class="cursor-pointer list-none rounded-xl bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600"></i>
            Tambah Excel / Upload Data Kabupaten-Kota
        </summary>
        <form method="POST" action="{{ route('sekarkijang-prices.upload') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
            @csrf
            <div>
                <label for="sekarkijangRegionCode" class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300">Kabupaten / Kota</label>
                <select id="sekarkijangRegionCode" name="region_code" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach($sekarkijangUploadRegions as $region)
                        <option value="{{ $region['code'] }}" {{ old('region_code') === $region['code'] ? 'selected' : '' }}>{{ $region['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sekarkijangExcelFile" class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300">File Excel SP2KP</label>
                <input id="sekarkijangExcelFile" name="excel_file" type="file" accept=".xlsx,.xls,.csv,.txt" required class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-500">
                <p class="mt-1 text-[10px] text-slate-400">Mendukung format SP2KP dengan kolom komoditas, satuan, harga dan tanggal. Maksimal 10 MB.</p>
            </div>
            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow transition hover:bg-emerald-500">
                <i class="fa-solid fa-upload mr-1"></i> Upload Data
            </button>
        </form>
    </details>

    @if(count($sekarkijangComparisonDatasets) > 0)
        <div class="overflow-x-auto">
            <div class="relative h-[480px] min-w-[900px]">
                <canvas id="sekarkijangRegionComparisonChart"></canvas>
            </div>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Situbondo menampilkan data {{ $sekarkijangSitubondoSource }}. Data wilayah lain berasal dari Excel yang tersimpan di MySQL; wilayah tanpa data akan kosong sampai file diunggah.
        </p>
    @else
        <div class="flex min-h-[300px] items-center justify-center rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Belum ada data harga untuk komoditas ini. Unggah file Excel wilayah atau pastikan data SP2KP Situbondo tersedia.
        </div>
    @endif
    
</div>
