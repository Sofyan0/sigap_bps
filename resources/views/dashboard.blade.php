<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAP SEKARKIJANG - Dashboard TPID Kabupaten Situbondo</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Alpine.js untuk kontrol interaktif dropdown profile & sub-menu sidebar -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #map, .map-container { height: 280px; width: 100%; border-radius: 1rem; z-index: 1; }
        .dark input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.7; cursor: pointer; }
        .sidebar-bg { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }
    </style>
    
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-100 antialiased min-h-screen flex transition-colors duration-300">

    @php
        $currentTab = $activeTab ?? request()->query('tab', 'beranda');
        $curRegion = $selectedRegion ?? request()->query('region', 'Situbondo');
        $dataSource = request()->query('source', 'sikeppo'); // sikeppo atau sp2kp
        
        $startDate = request()->query('start_date', '2026-10-01');
        $endDate = request()->query('end_date', '2026-10-27');
        $selectedCommodity = request()->query('commodity', 'all');

        // PENGELOLAAN RENTANG TANGGAL DINAMIS
        try {
            $periodStart = \Carbon\Carbon::parse($startDate);
            $periodEnd = \Carbon\Carbon::parse($endDate);
            if ($periodStart->gt($periodEnd)) {
                $periodStart = \Carbon\Carbon::parse('2026-10-01');
                $periodEnd = \Carbon\Carbon::parse('2026-10-27');
            }
        } catch (\Exception $e) {
            $periodStart = \Carbon\Carbon::parse('2026-10-01');
            $periodEnd = \Carbon\Carbon::parse('2026-10-27');
        }

        $displayDate = $periodEnd->translatedFormat('d F Y');

        // QUERY DATABASE MYSQL UNTUK SIKEPO / SP2KP
        $query = \Illuminate\Support\Facades\DB::table('harga_pasar')
            ->where('nama_pasar', 'LIKE', '%Panji%')
            ->whereBetween('tanggal_dibuat', [$startDate, $endDate]);

        if ($selectedCommodity !== 'all') {
            $query->where('nama_barang', 'LIKE', '%' . $selectedCommodity . '%');
        }

        $rawDbData = $query->orderBy('tanggal_dibuat', 'asc')->get();

        // Label tanggal unik per hari
        $dbChartLabels = $rawDbData->pluck('tanggal_dibuat')->unique()->map(function($d) {
            return \Carbon\Carbon::parse($d)->translatedFormat('j M');
        })->values()->toArray();

        // Dataset grafik per komoditas dari database
        $groupedCommodities = $rawDbData->groupBy('nama_barang');
        $palette = ['#ef4444', '#f59e0b', '#dc2626', '#d97706', '#ea580c', '#10b981', '#06b6d4', '#8b5cf6', '#059669', '#a855f7', '#b91c1c', '#64748b', '#f43f5e', '#0284c7'];
        $colorIdx = 0;

        $dbChartDatasets = [];
        $uniqueDates = $rawDbData->pluck('tanggal_dibuat')->unique()->values();

        foreach ($groupedCommodities as $bName => $records) {
            $priceMap = $records->pluck('harga', 'tanggal_dibuat')->toArray();
            $dataPoints = [];
            
            foreach ($uniqueDates as $dt) {
                $val = isset($priceMap[$dt]) ? (float)$priceMap[$dt] : null;
                if ($dataSource === 'sp2kp' && $val !== null) {
                    $val = round($val * 1.02);
                }
                $dataPoints[] = $val;
            }

            $cColor = $palette[$colorIdx % count($palette)];
            $colorIdx++;

            $dbChartDatasets[] = [
                'label' => $bName . ($dataSource === 'sp2kp' ? ' (SP2KP)' : ' (SIKEPO)'),
                'data' => $dataPoints,
                'borderColor' => $cColor,
                'backgroundColor' => $cColor,
                'fill' => false,
                'tension' => 0.3
            ];
        }

        // Master 7 Wilayah Sekarkijang
        $regionsList = [
            'Situbondo'       => ['name' => 'Situbondo', 'iph' => 1.18, 'status' => 'Waspada', 'price' => 60000],
            'Jember'          => ['name' => 'Jember', 'iph' => 0.85, 'status' => 'Waspada', 'price' => 65000],
            'Banyuwangi'      => ['name' => 'Banyuwangi', 'iph' => -0.32, 'status' => 'Aman', 'price' => 58000],
            'Bondowoso'       => ['name' => 'Bondowoso', 'iph' => 0.45, 'status' => 'Aman', 'price' => 56000],
            'Lumajang'        => ['name' => 'Lumajang', 'iph' => 0.12, 'status' => 'Aman', 'price' => 55000],
            'Kab_Probolinggo' => ['name' => 'Kab. Probolinggo', 'iph' => 1.42, 'status' => 'Waspada', 'price' => 54500],
            'Kota_Probolinggo'=> ['name' => 'Kota Probolinggo', 'iph' => 0.20, 'status' => 'Aman', 'price' => 54000]
        ];

        // Master Data Komoditas Strategis Pasar Panji dengan Nilai HAP & Simulasi Perbandingan Harga 7 Kabupaten/Kota Sekarkijang
        $commoditiesMaster = [
            [
                'id' => 'Cabe Rawit Merah', 'name' => 'Cabe Rawit Merah', 'price' => 62000, 'hap' => 57000, 'unit' => 'kg', 'change' => 3.2, 'status' => 'Intervensi', 'cv' => '22.4%', 'risk' => 87, 'stok' => '12.4 Ton', 'need' => '28 Ton', 'days' => 13,
                'prices_sekarkijang' => ['Situbondo' => 62000, 'Jember' => 65000, 'Banyuwangi' => 58000, 'Bondowoso' => 60000, 'Lumajang' => 59000, 'Kab_Probolinggo' => 63500, 'Kota_Probolinggo' => 63000]
            ],
            [
                'id' => 'Bawang Merah (Super)', 'name' => 'Bawang Merah (Super)', 'price' => 24000, 'hap' => 22000, 'unit' => 'kg', 'change' => 8.3, 'status' => 'Intervensi', 'cv' => '18.1%', 'risk' => 73, 'stok' => '32 Ton', 'need' => '45 Ton', 'days' => 21,
                'prices_sekarkijang' => ['Situbondo' => 24000, 'Jember' => 26000, 'Banyuwangi' => 23000, 'Bondowoso' => 22500, 'Lumajang' => 23500, 'Kab_Probolinggo' => 25000, 'Kota_Probolinggo' => 24800]
            ],
            [
                'id' => 'Cabe Merah Besar', 'name' => 'Cabe Merah Besar', 'price' => 30000, 'hap' => 25000, 'unit' => 'kg', 'change' => 33.3, 'status' => 'Waspada', 'cv' => '14.8%', 'risk' => 62, 'stok' => '18.5 Ton', 'need' => '22 Ton', 'days' => 25,
                'prices_sekarkijang' => ['Situbondo' => 30000, 'Jember' => 32000, 'Banyuwangi' => 28000, 'Bondowoso' => 29000, 'Lumajang' => 28500, 'Kab_Probolinggo' => 31000, 'Kota_Probolinggo' => 30500]
            ],
            [
                'id' => 'Telur Ayam Ras', 'name' => 'Telur Ayam Ras', 'price' => 25000, 'hap' => 27000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '3.8%', 'risk' => 48, 'stok' => '58 Ton', 'need' => '60 Ton', 'days' => 29,
                'prices_sekarkijang' => ['Situbondo' => 25000, 'Jember' => 25500, 'Banyuwangi' => 26000, 'Bondowoso' => 24800, 'Lumajang' => 25200, 'Kab_Probolinggo' => 25800, 'Kota_Probolinggo' => 25600]
            ],
            [
                'id' => 'Daging Ayam Ras', 'name' => 'Daging Ayam Ras', 'price' => 36000, 'hap' => 36750, 'unit' => 'kg', 'change' => -8.3, 'status' => 'Aman', 'cv' => '5.4%', 'risk' => 35, 'stok' => '75 Ton', 'need' => '65 Ton', 'days' => 34,
                'prices_sekarkijang' => ['Situbondo' => 36000, 'Jember' => 37500, 'Banyuwangi' => 35000, 'Bondowoso' => 35500, 'Lumajang' => 36000, 'Kab_Probolinggo' => 37000, 'Kota_Probolinggo' => 36800]
            ],
            [
                'id' => 'Beras Premium (TH 55)', 'name' => 'Beras Premium (TH 55)', 'price' => 15000, 'hap' => 14900, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.2%', 'risk' => 31, 'stok' => '850 Ton', 'need' => '600 Ton', 'days' => 42,
                'prices_sekarkijang' => ['Situbondo' => 15000, 'Jember' => 15200, 'Banyuwangi' => 14900, 'Bondowoso' => 14800, 'Lumajang' => 14950, 'Kab_Probolinggo' => 15100, 'Kota_Probolinggo' => 15000]
            ],
            [
                'id' => 'Minyak Goreng Curah', 'name' => 'Minyak Goreng Curah', 'price' => 16500, 'hap' => 15700, 'unit' => 'L', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.5%', 'risk' => 28, 'stok' => '62.000 L', 'need' => '38.000 L', 'days' => 48,
                'prices_sekarkijang' => ['Situbondo' => 16500, 'Jember' => 16800, 'Banyuwangi' => 16200, 'Bondowoso' => 16300, 'Lumajang' => 16400, 'Kab_Probolinggo' => 16600, 'Kota_Probolinggo' => 16500]
            ],
            [
                'id' => 'Gula Kristal Putih', 'name' => 'Gula Kristal Putih', 'price' => 17000, 'hap' => 17500, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '2.0%', 'risk' => 22, 'stok' => '120 Ton', 'need' => '90 Ton', 'days' => 40,
                'prices_sekarkijang' => ['Situbondo' => 17000, 'Jember' => 17200, 'Banyuwangi' => 16900, 'Bondowoso' => 16800, 'Lumajang' => 17000, 'Kab_Probolinggo' => 17100, 'Kota_Probolinggo' => 17050]
            ],
            [
                'id' => 'Beras Medium (Bulog)', 'name' => 'Beras Medium (Bulog)', 'price' => 12500, 'hap' => 12500, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '2.1%', 'risk' => 18, 'stok' => '1.250 Ton', 'need' => '820 Ton', 'days' => 45,
                'prices_sekarkijang' => ['Situbondo' => 12500, 'Jember' => 12500, 'Banyuwangi' => 12500, 'Bondowoso' => 12500, 'Lumajang' => 12500, 'Kab_Probolinggo' => 12500, 'Kota_Probolinggo' => 12500]
            ],
            [
                'id' => 'Bawang Putih Sinco/Honan', 'name' => 'Bawang Putih Sinco/Honan', 'price' => 34000, 'hap' => 32000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '4.2%', 'risk' => 28, 'stok' => '45 Ton', 'need' => '35 Ton', 'days' => 38,
                'prices_sekarkijang' => ['Situbondo' => 34000, 'Jember' => 35000, 'Banyuwangi' => 33500, 'Bondowoso' => 33800, 'Lumajang' => 34200, 'Kab_Probolinggo' => 34500, 'Kota_Probolinggo' => 34300]
            ],
            [
                'id' => 'Daging Sapi Paha Belakang (Super)', 'name' => 'Daging Sapi Paha Belakang', 'price' => 120000, 'hap' => 130000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.8%', 'risk' => 16, 'stok' => '25 Ton', 'need' => '18 Ton', 'days' => 41,
                'prices_sekarkijang' => ['Situbondo' => 120000, 'Jember' => 125000, 'Banyuwangi' => 118000, 'Bondowoso' => 115000, 'Lumajang' => 118000, 'Kab_Probolinggo' => 122000, 'Kota_Probolinggo' => 121000]
            ],
        ];

        // Cari komoditas yang dipilih untuk grafik perbandingan Sekarkijang
        $activeComparisonCommodity = $commoditiesMaster[0];
        if ($selectedCommodity !== 'all') {
            foreach ($commoditiesMaster as $cItem) {
                if (str_contains(strtolower($cItem['name']), strtolower($selectedCommodity))) {
                    $activeComparisonCommodity = $cItem;
                    break;
                }
            }
        }

        // Master 12 Pasar Terpantau Situbondo
        $situbondoMarketsList = [
            ['name' => 'Pasar Panji (Mimbaan)', 'district' => 'Panji', 'status' => 'Intervensi', 'lat' => -7.7025, 'lng' => 113.9922],
            ['name' => 'Pasar Besuki', 'district' => 'Besuki', 'status' => 'Intervensi', 'lat' => -7.7348, 'lng' => 113.6931],
            ['name' => 'Pasar Jangkar', 'district' => 'Jangkar', 'status' => 'Intervensi', 'lat' => -7.7120, 'lng' => 114.1500],
            ['name' => 'Pasar Panarukan', 'district' => 'Panarukan', 'status' => 'Waspada', 'lat' => -7.6950, 'lng' => 113.9350],
            ['name' => 'Pasar Asembagus', 'district' => 'Asembagus', 'status' => 'Waspada', 'lat' => -7.7600, 'lng' => 114.2200],
            ['name' => 'Pasar Bungatan', 'district' => 'Bungatan', 'status' => 'Waspada', 'lat' => -7.7180, 'lng' => 113.8200],
            ['name' => 'Pasar Mangaran', 'district' => 'Mangaran', 'status' => 'Waspada', 'lat' => -7.6500, 'lng' => 114.0200],
            ['name' => 'Pasar Mlandingan', 'district' => 'Mlandingan', 'status' => 'Waspada', 'lat' => -7.7080, 'lng' => 113.8700],
            ['name' => 'Pasar Kendit', 'district' => 'Kendit', 'status' => 'Stabil', 'lat' => -7.7100, 'lng' => 113.9000],
            ['name' => 'Pasar Banyuglugur', 'district' => 'Banyuglugur', 'status' => 'Stabil', 'lat' => -7.7200, 'lng' => 113.6300],
            ['name' => 'Pasar Suboh', 'district' => 'Suboh', 'status' => 'Stabil', 'lat' => -7.7100, 'lng' => 113.7500],
            ['name' => 'Pasar Sumbermalang', 'district' => 'Sumbermalang', 'status' => 'Stabil', 'lat' => -7.8000, 'lng' => 113.7300],
        ];

        // Ambil daftar unik komoditas dari database MySQL untuk dropdown filter
        $dbCommodityOptions = \Illuminate\Support\Facades\DB::table('harga_pasar')
            ->where('nama_pasar', 'LIKE', '%Panji%')
            ->distinct()
            ->pluck('nama_barang');
    @endphp

    <!-- SIDEBAR NAVIGATION -->
    <aside class="w-64 sidebar-bg text-slate-300 flex-shrink-0 min-h-screen border-r border-slate-800 flex flex-col justify-between fixed left-0 top-0 bottom-0 z-50">
        <div>
            <!-- Sidebar Header / Logo -->
            <div class="p-4 border-b border-slate-800/80 flex items-center space-x-3">
                <div class="bg-gradient-to-tr from-blue-600 to-indigo-500 text-white p-2 rounded-xl font-extrabold text-lg shadow-lg">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <h1 class="font-black text-white text-base leading-none tracking-wide">SIGAP</h1>
                    <p class="text-[10px] text-blue-400 font-semibold tracking-wider uppercase mt-1">SEKARKIJANG</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1 text-xs font-semibold">
                <a href="?tab=beranda" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'beranda' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-house text-sm w-5"></i>
                    <span>Beranda</span>
                </a>

                <!-- MENU DROPDOWN MONITORING HARGA (SIKEPO & SP2KP) -->
                <div x-data="{ open: {{ $currentTab === 'monitoring_harga' ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'monitoring_harga' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-store text-sm w-5"></i>
                            <span>Monitoring Harga</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] opacity-60 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <!-- Sub-Menu Options -->
                    <div x-show="open" x-collapse class="pl-8 pr-2 py-1 space-y-1 mt-1">
                        <a href="?tab=monitoring_harga&source=sikeppo" class="flex items-center justify-between px-3 py-2 rounded-lg text-[11px] transition-all {{ ($currentTab === 'monitoring_harga' && $dataSource === 'sikeppo') ? 'bg-blue-500/20 text-blue-400 font-bold border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-building-store text-[10px]"></i> SIKEPO (Pasar Panji)
                            </span>
                            @if($dataSource === 'sikeppo')
                                <i class="fa-solid fa-circle text-[6px] text-blue-400"></i>
                            @endif
                        </a>
                        <a href="?tab=monitoring_harga&source=sp2kp" class="flex items-center justify-between px-3 py-2 rounded-lg text-[11px] transition-all {{ ($currentTab === 'monitoring_harga' && $dataSource === 'sp2kp') ? 'bg-amber-500/20 text-amber-400 font-bold border border-amber-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-globe text-[10px]"></i> SP2KP (Kemendag)
                            </span>
                            @if($dataSource === 'sp2kp')
                                <i class="fa-solid fa-circle text-[6px] text-amber-400"></i>
                            @endif
                        </a>
                    </div>
                </div>

                <a href="?tab=monitoring_pasar" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'monitoring_pasar' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-map-location-dot text-sm w-5"></i>
                        <span>Monitoring Pasar</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] opacity-60"></i>
                </a>

                <a href="?tab=sekarkijang" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'sekarkijang' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-diagram-project text-sm w-5"></i>
                    <span>Dashboard Sekarkijang</span>
                </a>

                <a href="?tab=ews" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'ews' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-shield-halved text-sm w-5 text-amber-400"></i>
                        <span>Early Warning System</span>
                    </div>
                    <span class="bg-red-500/20 text-red-400 text-[9px] px-1.5 py-0.5 rounded font-bold">3 Alert</span>
                </a>

                <a href="?tab=stok" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'stok' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-boxes-stacked text-sm w-5"></i>
                    <span>Stok dan Pasokan</span>
                </a>

                <a href="?tab=intervensi" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'intervensi' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-hand-holding-hand text-sm w-5"></i>
                    <span>Intervensi TPID</span>
                </a>

                <a href="?tab=analisis" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'analisis' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-chart-pie text-sm w-5"></i>
                        <span>Analisis &amp; Prediksi</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] opacity-60"></i>
                </a>

                <a href="?tab=laporan" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'laporan' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-file-invoice text-sm w-5"></i>
                    <span>Laporan</span>
                </a>

                <div class="pt-2 border-t border-slate-800/80 my-2"></div>

                <a href="{{ Route::has('kelola-data') ? route('kelola-data') : '#' }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all text-amber-400 hover:bg-slate-800/60">
                    <i class="fa-solid fa-database text-sm w-5"></i>
                    <span>Master Data &amp; Excel</span>
                </a>

                <a href="?tab=pengaturan" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all {{ $currentTab === 'pengaturan' ? 'bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <i class="fa-solid fa-gear text-sm w-5"></i>
                    <span>Pengaturan Akun</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-3 border-t border-slate-800/80 bg-slate-950/40">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="flex items-center gap-1.5 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> MySQL Active
                </span>
                <button onclick="toggleTheme()" class="text-amber-400 hover:text-amber-300">
                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 ml-64 min-h-screen flex flex-col">

        <!-- Top Navigation Header + GLOBAL FILTER FORM + ADMIN TPID PROFILE DROPDOWN -->
        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40 px-6 py-3.5 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    @if($currentTab === 'pengaturan')
                        <i class="fa-solid fa-user-gear text-blue-500"></i> Pengaturan Akun &amp; Profil Pengguna TPID
                    @elseif($currentTab === 'laporan')
                        <i class="fa-solid fa-file-invoice text-blue-500"></i> Pusat Laporan Eksekutif &amp; Export Data SP2KP
                    @elseif($currentTab === 'analisis')
                        <i class="fa-solid fa-chart-pie text-blue-500"></i> Analisis &amp; Prediksi Harga AI/Machine Learning
                    @elseif($currentTab === 'intervensi')
                        <i class="fa-solid fa-hand-holding-hand text-blue-500"></i> Program Intervensi TPID &amp; Gerakan Pangan Murah
                    @elseif($currentTab === 'stok')
                        <i class="fa-solid fa-boxes-stacked text-blue-500"></i> Monitoring Stok &amp; Pasokan 20 Pangan Pokok
                    @elseif($currentTab === 'sekarkijang')
                        <i class="fa-solid fa-diagram-project text-blue-500"></i> Dashboard Sekarkijang &amp; Indeks IPH Regional
                    @elseif($currentTab === 'ews')
                        <i class="fa-solid fa-shield-halved text-amber-500"></i> Early Warning System (EWS) Inflasi
                    @elseif($currentTab === 'monitoring_harga')
                        <i class="fa-solid fa-store text-blue-500"></i> Monitoring Harga (Sumber: {{ strtoupper($dataSource) }})
                    @elseif($currentTab === 'monitoring_pasar')
                        <i class="fa-solid fa-map-location-dot text-indigo-500"></i> Monitoring Persebaran Pasar Rakyat
                    @else
                        <i class="fa-solid fa-house text-blue-500"></i> Executive Overview SIGAP SEKARKIJANG
                    @endif
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">TPID Kabupaten Situbondo &bull; Data Real Pasar Panji: <span class="font-bold text-amber-600 dark:text-amber-400">{{ $startDate }} s/d {{ $endDate }}</span></p>
            </div>

            <!-- RIGHT CONTAINER: FILTER FORM & ADMIN PROFILE DROPDOWN -->
            <div class="flex flex-wrap items-center gap-3">
                
                <!-- GLOBAL FILTER BAR -->
                <form method="GET" action="" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <input type="hidden" name="source" value="{{ $dataSource }}">
                    
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <label for="region" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Pasar:</label>
                        <select id="region" name="region" class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                            <option value="Panji" selected class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white">Pasar Rakyat Panji</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Tanggal:</span>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                        <span class="text-[10px] text-slate-400">s/d</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                    </div>

                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Komoditas:</span>
                        <select name="commodity" class="bg-transparent text-xs font-bold text-amber-600 dark:text-amber-400 focus:outline-none cursor-pointer max-w-[170px]">
                            <option value="all" {{ $selectedCommodity === 'all' ? 'selected' : '' }}>Semua Komoditas</option>
                            @foreach($dbCommodityOptions as $commName)
                                <option value="{{ $commName }}" class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white" {{ $selectedCommodity === $commName ? 'selected' : '' }}>
                                    {{ $commName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-md transition">
                        <i class="fa-solid fa-filter mr-1"></i> Terapkan
                    </button>
                </form>

                <!-- ELEMEN SEPARATOR -->
                <div class="hidden sm:block h-6 w-px bg-slate-300 dark:bg-slate-700"></div>

                <!-- ADMIN TPID PROFILE DROPDOWN -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 bg-blue-900/40 hover:bg-blue-900/60 dark:bg-slate-800 dark:hover:bg-slate-700 border border-blue-800/60 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-white font-bold text-xs transition shadow-sm focus:outline-none">
                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-black text-[11px] shadow">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white">Admin TPID</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 text-xs font-semibold" 
                         style="display: none;">
                        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700/60">
                            <p class="text-slate-900 dark:text-white font-extrabold truncate">Admin TPID Situbondo</p>
                            <p class="text-[10px] text-slate-400 truncate">admin.tpid@situbondokab.go.id</p>
                        </div>
                        <a href="?tab=pengaturan" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition">
                            <i class="fa-solid fa-user-gear text-blue-500 w-4"></i> Pengaturan Akun
                        </a>
                        <div class="my-1 border-t border-slate-100 dark:border-slate-700/60"></div>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition">
                            <i class="fa-solid fa-right-from-bracket w-4"></i> Keluar (Logout)
                        </a>
                    </div>
                </div>

            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="p-6 space-y-6 flex-1">

            <!-- TAB 1: BERANDA -->
            @if ($currentTab === 'beranda')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">IPH Pasar Panji</p>
                            <p class="text-sm font-bold text-red-500">
                                +1.18%
                                <span class="text-[10px] font-normal text-slate-400">(Okt 2026)</span>
                            </p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Naik</p>
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">8 Komoditas</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i class="fa-solid fa-arrow-trend-down text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Turun</p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">6 Komoditas</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-layer-group text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komoditas Terpantau</p>
                            <p class="text-base font-bold text-blue-600 dark:text-blue-400">41 Komoditas</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-emerald-500"></i> Grafik Pergerakan Harga Harian Pasar Rakyat Panji (Data Real MySQL)
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Filter Terpasang: {{ $startDate }} s/d {{ $endDate }}</p>
                        </div>
                        <a href="?tab=monitoring_harga" class="text-xs font-bold text-blue-500 hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="relative h-[420px]">
                        <canvas id="priceChart"></canvas>
                    </div>
                </div>

            <!-- TAB 2: MONITORING HARGA (SIKEPO & SP2KP) -->
            @elseif ($currentTab === 'monitoring_harga')
                <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-2.5 gap-2">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-store text-blue-500"></i>
                                Monitoring Harga Komoditas
                                <span class="px-2 py-0.5 rounded-lg text-xs font-black {{ $dataSource === 'sp2kp' ? 'bg-amber-500/20 text-amber-500 border border-amber-500/30' : 'bg-blue-500/20 text-blue-500 border border-blue-500/30' }}">
                                    Sumber: {{ $dataSource === 'sp2kp' ? 'SP2KP (Kemendag Dummy)' : 'SIKEPO (Pasar Panji Real)' }}
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $dataSource === 'sp2kp' ? 'Data integrasi simulasi SP2KP Kementerian Perdagangan diselaraskan dengan komoditas SIKEPO' : 'Data terintegrasi langsung dari tabel MySQL harga_pasar Kabupaten Situbondo' }}
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

                    <div class="overflow-x-auto text-[11px] max-h-[380px] overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                    <th class="pb-2">No</th>
                                    <th class="pb-2">Nama Komoditas</th>
                                    <th class="pb-2 text-right">Harga Rata-rata ({{ strtoupper($dataSource) }})</th>
                                    <th class="pb-2 text-right text-amber-500 dark:text-amber-400">HAP (Harga Acuan)</th>
                                    <th class="pb-2 text-center">Satuan</th>
                                    <th class="pb-2 text-center">Perubahan</th>
                                    <th class="pb-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-semibold">
                                @php $rowNo = 1; @endphp
                                @foreach($commoditiesMaster as $c)
                                    @if($selectedCommodity === 'all' || str_contains(strtolower($c['name']), strtolower($selectedCommodity)))
                                        @php
                                            $finalPrice = $c['price'];
                                            if ($dataSource === 'sp2kp') {
                                                $finalPrice = round($c['price'] * 1.02);
                                            }
                                            $isOverHap = $finalPrice > $c['hap'];
                                        @endphp
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                            <td class="py-2 text-slate-400">{{ $rowNo++ }}</td>
                                            <td class="py-2 text-slate-800 dark:text-slate-200 font-bold">{{ $c['name'] }}</td>
                                            <td class="py-2 text-right font-extrabold text-slate-900 dark:text-white">Rp {{ number_format($finalPrice, 0, ',', '.') }}</td>
                                            <td class="py-2 text-right font-bold {{ $isOverHap ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                Rp {{ number_format($c['hap'], 0, ',', '.') }}
                                            </td>
                                            <td class="py-2 text-center text-slate-500 dark:text-slate-400">/{{ $c['unit'] }}</td>
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
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c['status'] === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($c['status'] === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                                    {{ $c['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GRAFIK TREN HARGA LEBAR PENUH -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                    <div class="xl:col-span-12 bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-2.5">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-blue-500"></i> Tren Grafik Pergerakan Harga Komoditas ({{ strtoupper($dataSource) }})
                            </h3>
                            <span class="text-[10px] bg-slate-100 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded-lg font-semibold">{{ strtoupper($dataSource) }} Live Data</span>
                        </div>
                        <div class="relative h-[360px]">
                            <canvas id="chartHargaTren"></canvas>
                        </div>
                    </div>
                </div>

            <!-- TAB 3: MONITORING PASAR -->
            @elseif ($currentTab === 'monitoring_pasar')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-store text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pasar Terpantau</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">12 Pasar Rakyat</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Intervensi</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">3 Pasar (Panji, Besuki, Jangkar)</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-eye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Waspada</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">5 Pasar</p>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i class="fa-solid fa-circle-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Stabil</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400">4 Pasar</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-map-location-dot text-indigo-500"></i> Peta Geografis 12 Pasar Rakyat (Situbondo)
                            </h3>
                        </div>
                        <div id="map" class="shadow-inner border border-slate-200 dark:border-slate-700"></div>
                    </div>

                    <div class="lg:col-span-4 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-list-check text-blue-500"></i> Daftar 12 Pasar Utama
                                </h3>
                                <span class="text-xs text-slate-400">Total: {{ count($situbondoMarketsList) }}</span>
                            </div>

                            <div class="mt-3 space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                                @foreach($situbondoMarketsList as $mObj)
                                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700/60 flex items-center justify-between hover:border-blue-500/50 transition cursor-pointer">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $mObj['name'] }}</p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"><i class="fa-solid fa-location-dot text-red-400 mr-1"></i> Kec. {{ $mObj['district'] }}</p>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $mObj['status'] === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($mObj['status'] === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                            {{ $mObj['status'] }}
                                        </span>
                                    </div>
                                @endforeach
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
            @elseif ($currentTab === 'sekarkijang')
                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-bar text-amber-500"></i>
                                Indeks Perkembangan Harga (IPH) Antar 7 Wilayah Sekarkijang
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Indikator Resmi Perubahan Harga Komoditas Pangan Pokok Sub-Provinsi Jatim</p>
                        </div>
                        <span class="text-xs bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 px-3 py-1 rounded-full font-bold">
                            Oktober 2026
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                        @foreach($regionsList as $rKey => $rMeta)
                            <div class="p-3 rounded-xl border transition-all flex flex-col justify-between {{ $curRegion === $rKey ? 'bg-blue-500/10 border-blue-500/50 shadow-md ring-2 ring-blue-500/30' : 'bg-slate-100 dark:bg-slate-900/60 border-slate-200 dark:border-slate-700/50' }}">
                                <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 truncate">{{ $rMeta['name'] }}</div>
                                <div class="mt-2 flex items-baseline justify-between">
                                    <span class="text-base font-extrabold {{ $rMeta['iph'] > 0 ? 'text-red-600 dark:text-red-400' : ($rMeta['iph'] < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400') }}">
                                        {{ ($rMeta['iph'] > 0 ? '+' : '') . $rMeta['iph'] }}%
                                    </span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded {{ $rMeta['iph'] > 0 ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500' }}">
                                        {{ $rMeta['status'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- GRAFIK IPH MOM ANTAR 7 WILAYAH -->
                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3 border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-blue-500"></i>
                                Perubahan Pergerakan IPH (MoM) Antar 7 Kabupaten/Kota Sekarkijang
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tren Fluktuasi Persentase IPH Bulanan di Karesidenan Besuki &amp; Lumajang</p>
                        </div>
                    </div>

                    <div class="relative h-[340px]">
                        <canvas id="interRegionChart"></canvas>
                    </div>
                </div>

                <!-- GRAFIK PERGERAKAN HARGA HARIAN SEKARKIJANG (LINE CHART LENGKAP SEPERTI PASAR PANJI) -->
                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-line text-emerald-500"></i>
                                Grafik Pergerakan Harga Harian Antar 7 Kabupaten/Kota (Sekarkijang)
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visualisasi Tren Fluktuasi Harga Harian Komoditas Pangan Pokok Sub-Provinsi Jawa Timur</p>
                        </div>

                        <!-- FILTER KHUSUS KOMODITAS & TANGGAL PADA GRAFIK PERGERAKAN SEKARKIJANG -->
                        <form method="GET" action="" class="flex flex-wrap items-center gap-2 bg-slate-50 dark:bg-slate-900 p-2 rounded-xl border border-slate-200 dark:border-slate-700/80">
                            <input type="hidden" name="tab" value="sekarkijang">
                            <input type="hidden" name="source" value="{{ $dataSource }}">

                            <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                <span>Komoditas:</span>
                                <select name="commodity" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 text-xs font-bold text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                                    @foreach($commoditiesMaster as $cOpt)
                                        <option value="{{ $cOpt['name'] }}" {{ ($activeComparisonCommodity['name'] === $cOpt['name']) ? 'selected' : '' }}>
                                            {{ $cOpt['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2">
                                <span>Tanggal:</span>
                                <input type="date" name="start_date" value="{{ $startDate }}" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                                <span>s/d</span>
                                <input type="date" name="end_date" value="{{ $endDate }}" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 focus:outline-none cursor-pointer">
                            </div>
                        </form>
                    </div>

                    <!-- CONTAINER GRAFIK PERGERAKAN HARGA HARIAN (LINE CHART) -->
                    <div class="relative h-[400px]">
                        <canvas id="sekarkijangDailyLineChart"></canvas>
                    </div>

                    <!-- RINGKASAN DISPARITAS HARGA -->
                    @php
                        $compPrices = $activeComparisonCommodity['prices_sekarkijang'];
                        $minCity = array_search(min($compPrices), $compPrices);
                        $maxCity = array_search(max($compPrices), $compPrices);
                        $minP = min($compPrices);
                        $maxP = max($compPrices);
                        $gapP = $maxP - $minP;
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs font-semibold">
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex justify-between items-center">
                            <span class="text-slate-600 dark:text-slate-300">Harga Terendah (Surplus):</span>
                            <span class="font-black text-emerald-600 dark:text-emerald-400">{{ str_replace('_', ' ', $minCity) }} (Rp {{ number_format($minP, 0, ',', '.') }})</span>
                        </div>
                        <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl flex justify-between items-center">
                            <span class="text-slate-600 dark:text-slate-300">Harga Tertinggi (Defisit):</span>
                            <span class="font-black text-red-600 dark:text-red-400">{{ str_replace('_', ' ', $maxCity) }} (Rp {{ number_format($maxP, 0, ',', '.') }})</span>
                        </div>
                        <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl flex justify-between items-center">
                            <span class="text-slate-600 dark:text-slate-300">Selisih Disparitas:</span>
                            <span class="font-black text-blue-600 dark:text-blue-400">Rp {{ number_format($gapP, 0, ',', '.') }} / {{ $activeComparisonCommodity['unit'] }}</span>
                        </div>
                    </div>
                </div>

            <!-- TAB 5: EARLY WARNING SYSTEM (EWS) -->
            @elseif ($currentTab === 'ews')
                <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-amber-500"></i> Early Warning System (EWS) 20 Komoditas Strategis
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Bobot Risk Score: Harga (40%), Volatilitas (25%), Stok (20%), Disparitas (15%)</p>
                        </div>
                        <span class="text-xs bg-red-500/10 text-red-500 border border-red-500/20 px-3 py-1 rounded-full font-bold">
                            Risk Score TPID: 82 (Waspada)
                        </span>
                    </div>

                    <div class="overflow-x-auto text-[11px] max-h-[450px] overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                    <th class="pb-2">Komoditas</th>
                                    <th class="pb-2 text-right">Harga Harian</th>
                                    <th class="pb-2 text-center">Volatilitas (CV)</th>
                                    <th class="pb-2 text-center">Risk Score (0-100)</th>
                                    <th class="pb-2 text-center">Status Peringatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-semibold">
                                @foreach($commoditiesMaster as $c)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                        <td class="py-2.5 text-slate-800 dark:text-slate-200 font-bold">{{ $c['name'] }}</td>
                                        <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">Rp {{ number_format($c['price'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 text-center text-slate-500 dark:text-slate-400">{{ $c['cv'] }}</td>
                                        <td class="py-2.5 text-center font-black {{ $c['risk'] >= 70 ? 'text-red-500' : ($c['risk'] >= 45 ? 'text-amber-500' : 'text-emerald-500') }}">{{ $c['risk'] }}</td>
                                        <td class="py-2.5 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c['status'] === 'Intervensi' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : ($c['status'] === 'Waspada' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                                {{ $c['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- TAB 6: STOK DAN PASOKAN -->
            @elseif ($currentTab === 'stok')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-wheat-awn text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stok Beras SPHP Bulog</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">1.250 Ton</p>
                            <span class="text-[10px] text-emerald-500 font-bold"><i class="fa-solid fa-check mr-1"></i>Aman 45 Hari</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-bottle-droplet text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stok Minyak Goreng</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">62.000 Liter</p>
                            <span class="text-[10px] text-emerald-500 font-bold"><i class="fa-solid fa-check mr-1"></i>Aman 48 Hari</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-pepper-hot text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pasokan Cabai Rawit</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">12,4 Ton / Mgg</p>
                            <span class="text-[10px] text-red-500 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Defisit 15%</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 rounded-xl">
                            <i class="fa-solid fa-truck-ramp-box text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelancaran Distribusi</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400">92% Lancar</p>
                            <span class="text-[10px] text-slate-400">Sentra Banyuwangi &amp; Jember</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-12 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-scale-balanced text-blue-500"></i> Neraca Ketersediaan Pangan Pokok (Kab. Situbondo)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sumber Data: Dinas Pertanian dan Ketahanan Pangan (DKPP) Situbondo</p>
                            </div>
                            <span class="text-xs bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 px-2.5 py-1 rounded-lg font-bold">
                                Real-Time Sync DKPP
                            </span>
                        </div>

                        <div class="overflow-x-auto text-[11px] max-h-[400px] overflow-y-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                    <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                        <th class="pb-2">Komoditas</th>
                                        <th class="pb-2 text-right">Stok Gudang</th>
                                        <th class="pb-2 text-right">Kebutuhan/Bln</th>
                                        <th class="pb-2 text-center">Ketahanan (Days of Stock)</th>
                                        <th class="pb-2 text-center">Status Neraca</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                                    @foreach($commoditiesMaster as $c)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                            <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $c['name'] }}</td>
                                            <td class="py-2.5 text-right font-semibold">{{ $c['stok'] }}</td>
                                            <td class="py-2.5 text-right font-semibold">{{ $c['need'] }}</td>
                                            <td class="py-2.5 text-center font-extrabold {{ $c['days'] < 20 ? 'text-red-500' : ($c['days'] < 30 ? 'text-amber-500' : 'text-emerald-500') }}">{{ $c['days'] }} Hari</td>
                                            <td class="py-2.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full font-bold {{ $c['days'] < 20 ? 'bg-red-500/10 text-red-600' : ($c['days'] < 30 ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600') }}">
                                                    {{ $c['days'] < 20 ? 'Defisit Pasokan' : ($c['days'] < 30 ? 'Waspada' : 'Surplus / Aman') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- TAB 7: INTERVENSI TPID -->
            @elseif ($currentTab === 'intervensi')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-hand-holding-hand text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Program Intervensi Aktif</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">12 Program</p>
                            <span class="text-[10px] text-emerald-500 font-bold">+3 dari bulan lalu</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i class="fa-solid fa-truck-moving text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Realisasi SPHP Bulog</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400">15,2 Ton</p>
                            <span class="text-[10px] text-slate-400">+25% dari bulan lalu</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-handshake-angle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kerja Sama Antar Daerah</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">3 KAD Aktif</p>
                            <span class="text-[10px] text-slate-400">Banyuwangi, Jember, Probolinggo</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <i class="fa-solid fa-circle-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Efektivitas Intervensi</p>
                            <p class="text-base font-black text-indigo-600 dark:text-indigo-400">84% (Efektif)</p>
                            <span class="text-[10px] text-emerald-500 font-bold">+6% dibanding awal bulan</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-bullhorn text-blue-500"></i> Monitoring Program Intervensi TPID Situbondo
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Strategi Pengendalian Inflasi Daerah (4K: Ketersediaan, Kelancaran, Keterjangkauan, Komunikasi)</p>
                            </div>
                            <span class="text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-lg font-bold">
                                TPID Kab. Situbondo
                            </span>
                        </div>

                        <div class="overflow-x-auto text-[11px]">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700/60 uppercase text-[10px]">
                                        <th class="pb-2">Program Intervensi</th>
                                        <th class="pb-2">Komoditas Target</th>
                                        <th class="pb-2">Lokasi / Sektor</th>
                                        <th class="pb-2 text-center">Pelaksana</th>
                                        <th class="pb-2 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                                    <tr>
                                        <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">Operasi Pasar Hortikultura</td>
                                        <td class="py-2.5 font-semibold text-red-500">Cabe Rawit &amp; Bawang Merah</td>
                                        <td class="py-2.5 text-slate-600 dark:text-slate-300">Pasar Panji (Mimbaan) &amp; Besuki</td>
                                        <td class="py-2.5 text-center font-bold">Diskoperindag / Bulog</td>
                                        <td class="py-2.5 text-center"><span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full font-bold">Berjalan</span></td>
                                    </tr>
                                    <tr>
                                        <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">Gerakan Pangan Murah (GPM) Keliling</td>
                                        <td class="py-2.5 font-semibold text-blue-500">Beras SPHP, Minyak, Telur</td>
                                        <td class="py-2.5 text-slate-600 dark:text-slate-300">Besuki &amp; Jangkar</td>
                                        <td class="py-2.5 text-center font-bold">DKPP Situbondo</td>
                                        <td class="py-2.5 text-center"><span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full font-bold">Berjalan</span></td>
                                    </tr>
                                    <tr>
                                        <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">Kerja Sama Antar Daerah (KAD) Pasokan</td>
                                        <td class="py-2.5 font-semibold text-amber-500">Cabe Rawit &amp; Sayuran</td>
                                        <td class="py-2.5 text-slate-600 dark:text-slate-300">Kab. Banyuwangi &amp; Jember</td>
                                        <td class="py-2.5 text-center font-bold">Bagian Perekonomian</td>
                                        <td class="py-2.5 text-center"><span class="bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full font-bold">MoU Aktif</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="lg:col-span-4 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-line-down text-emerald-500"></i> Dampak Intervensi Cabe Rawit
                                </h3>
                                <span class="text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded font-bold">Turun 3,1%</span>
                            </div>

                            <div class="mt-3 space-y-3 text-xs">
                                <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700/60 space-y-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-800 dark:text-white">Awal Oktober (Puncak)</span>
                                        <span class="font-extrabold text-red-500">Rp 64.000/kg</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-1 border-t border-slate-200 dark:border-slate-700">
                                        <span class="font-bold text-slate-800 dark:text-white">Akhir Oktober (Real Panji)</span>
                                        <span class="font-extrabold text-emerald-500">Rp 62.000/kg</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">"Intervensi TPID di Pasar Panji berhasil menekan fluktuasi harga cabe rawit secara terukur."</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                            <a href="{{ Route::has('kelola-data') ? route('kelola-data') : '#' }}" class="w-full block text-center bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 rounded-xl transition shadow-md">
                                <i class="fa-solid fa-plus-circle mr-1"></i> Tambah Jadwal Intervensi
                            </a>
                        </div>
                    </div>
                </div>

            <!-- TAB 8: ANALISIS & PREDIKSI -->
            @elseif ($currentTab === 'analisis')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <i class="fa-solid fa-robot text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Model Forecasting</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">Random Forest / KNN</p>
                            <span class="text-[10px] text-emerald-500 font-bold"><i class="fa-solid fa-check mr-1"></i>Akurasi Model 94,2%</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 rounded-xl">
                            <i class="fa-solid fa-chart-line-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proyeksi Puncak Harga</p>
                            <p class="text-base font-black text-red-600 dark:text-red-400">Minggu III Des 2026</p>
                            <span class="text-[10px] text-slate-400">Pemicu: HBKN (Nataru)</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="fa-solid fa-percent text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perkiraan Inflasi M-o-M</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400">+0,34%</p>
                            <span class="text-[10px] text-slate-400">Bulan Desember 2026</span>
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center space-x-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl">
                            <i class="fa-solid fa-bullseye text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Faktor Pengaruh Utama</p>
                            <p class="text-base font-black text-blue-600 dark:text-blue-400">Cuaca &amp; Demand HBKN</p>
                            <span class="text-[10px] text-slate-400">Korelasi 82%</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-200 dark:border-slate-700 pb-3 gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-area text-blue-500"></i> Proyeksi Prediksi Harga Pasar Panji (AI Machine Learning)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Membandingkan Historis Harga Real MySQL dengan Proyeksi AI Masa Mendatang</p>
                            </div>
                            
                            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1 text-xs">
                                <span class="text-slate-400">Komoditas:</span>
                                <span class="font-bold text-amber-500">Cabe Rawit Merah</span>
                            </div>
                        </div>

                        <div class="relative h-[300px]">
                            <canvas id="chartPrediksiAnalisis"></canvas>
                        </div>
                    </div>

                    <div class="lg:col-span-4 space-y-4">
                        <div class="bg-gradient-to-br from-indigo-900/80 to-slate-900 p-4 rounded-2xl border border-indigo-500/40 shadow-xl space-y-2">
                            <h3 class="text-xs font-extrabold text-amber-400 flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> AI Predictive Insight
                            </h3>
                            <p class="text-[11px] text-slate-200 leading-relaxed italic">
                                "Berdasarkan data harian Pasar Panji bulan Oktober 2026 di MySQL, cabe rawit merah diproyeksikan mengalami kenaikan bertahap mendekati akhir tahun akibat faktor perubahan musim."
                            </p>
                        </div>
                    </div>
                </div>

            <!-- TAB 9: LAPORAN -->
            @elseif ($currentTab === 'laporan')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl">
                                <i class="fa-solid fa-file-pdf text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Laporan Eksekutif IPH</p>
                                <p class="text-[10px] text-slate-400">PDF &bull; Oktober 2026</p>
                            </div>
                        </div>
                        <button class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl transition">
                            <i class="fa-solid fa-download text-xs"></i>
                        </button>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-xl">
                                <i class="fa-solid fa-file-excel text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Rekapitulasi Pasar Panji</p>
                                <p class="text-[10px] text-slate-400">XLSX &bull; Data Harian MySQL</p>
                            </div>
                        </div>
                        <button class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl transition">
                            <i class="fa-solid fa-download text-xs"></i>
                        </button>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-blue-500/10 text-blue-500 border border-blue-500/20 rounded-xl">
                                <i class="fa-solid fa-file-word text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Nota Dinas TPID</p>
                                <p class="text-[10px] text-slate-400">DOCX &bull; Rekomendasi Kebijakan</p>
                            </div>
                        </div>
                        <button class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl transition">
                            <i class="fa-solid fa-download text-xs"></i>
                        </button>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-md flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-amber-500/10 text-amber-500 border border-amber-500/20 rounded-xl">
                                <i class="fa-solid fa-file-csv text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Dataset Raw SP2KP</p>
                                <p class="text-[10px] text-slate-400">CSV &bull; Integration Warehouse</p>
                            </div>
                        </div>
                        <button class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl transition">
                            <i class="fa-solid fa-download text-xs"></i>
                        </button>
                    </div>
                </div>

            <!-- TAB 10: PENGATURAN AKUN -->
            @elseif ($currentTab === 'pengaturan')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- KIRI: INFORMASI PROFIL -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white/90 dark:bg-slate-800/90 p-6 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl text-center space-y-4">
                            <div class="relative w-24 h-24 mx-auto">
                                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center font-black text-3xl shadow-xl border-4 border-white dark:border-slate-800">
                                    <i class="fa-solid fa-user-shield"></i>
                                </div>
                                <button class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-500 text-white p-2 rounded-full shadow-lg transition text-xs">
                                    <i class="fa-solid fa-camera"></i>
                                </button>
                            </div>
                            
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Admin TPID Situbondo</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">admin.tpid@situbondokab.go.id</p>
                                <span class="inline-block mt-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[10px] font-bold px-3 py-0.5 rounded-full">
                                    Super Administrator TPID
                                </span>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 text-left text-xs space-y-2 text-slate-600 dark:text-slate-300">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Instansi:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Bagian Perekonomian Setda</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">NIP:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">19850412 201001 1 008</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Terakhir Login:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Hari ini, 08:15 WIB</span>
                                </div>
                            </div>
                        </div>

                        <!-- KARTU PREFERENSI NOTIFIKASI -->
                        <div class="bg-white/90 dark:bg-slate-800/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-3 text-xs">
                            <h4 class="font-extrabold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-700/60 pb-2 flex items-center gap-2">
                                <i class="fa-solid fa-bell text-amber-500"></i> Notifikasi Sektor Inflasi
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Alert Email EWS Kenaikan Harga</span>
                                    <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Laporan Mingguan IPH Otomatis</span>
                                    <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: FORM EDIT PROFIL -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="bg-white/90 dark:bg-slate-800/90 p-6 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xl space-y-4">
                            <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-user-pen text-blue-500"></i> Perbarui Data Profil Pengguna
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Ubah informasi personal dan instansi akun penanggung jawab TPID</p>
                            </div>

                            <form action="#" method="POST" class="space-y-4 text-xs font-semibold">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Admin</label>
                                        <input type="text" value="Admin TPID Situbondo" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-slate-700 dark:text-slate-300 mb-1">NIP / ID Pegawai</label>
                                        <input type="text" value="19850412 201001 1 008" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="button" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 py-4 text-center text-xs text-slate-500">
            &copy; 2026 SIGAP SEKARKIJANG &bull; Tim Pengendalian Inflasi Daerah (TPID) Kabupaten Situbondo
        </footer>

    </div>

    <!-- JAVASCRIPT GRAFIK CHART.JS INTEGRASI MYSQL -->
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                icon.className = 'fa-solid fa-sun';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                icon.className = 'fa-solid fa-moon';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');

            // Data riil dari MySQL hasil query Blade PHP
            const dbLabels = @json($dbChartLabels);
            const dbDatasets = @json($dbChartDatasets);

            const commonChartConfig = {
                type: 'line',
                data: {
                    labels: dbLabels,
                    datasets: dbDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 10, font: { size: 10 }, color: isDark ? '#cbd5e1' : '#334155' }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: isDark ? '#94a3b8' : '#475569', font: { size: 10 } } },
                        y: { 
                            grid: { color: isDark ? '#334155' : '#e2e8f0' }, 
                            ticks: { 
                                callback: function(val) { return 'Rp ' + val.toLocaleString('id-ID'); }, 
                                color: isDark ? '#94a3b8' : '#475569', 
                                font: { size: 10 } 
                            } 
                        }
                    }
                }
            };

            // Render Chart Utama Beranda
            const ctxPriceChart = document.getElementById('priceChart');
            if (ctxPriceChart) {
                new Chart(ctxPriceChart.getContext('2d'), commonChartConfig);
            }

            // Render Chart Monitoring Harga
            const ctxHargaTren = document.getElementById('chartHargaTren');
            if (ctxHargaTren) {
                new Chart(ctxHargaTren.getContext('2d'), commonChartConfig);
            }

            // Render Chart Sekarkijang (IPH MoM)
            const ctxInterRegion = document.getElementById('interRegionChart');
            if (ctxInterRegion) {
                const interRegionDatasets = [
                    { 
                        label: 'IPH Situbondo (MoM)', 
                        data: [1.5, 0.2, -0.4, 0.6, 0.1, -0.5, 1.4, 0.4, 1.8, 0.2, -0.4], 
                        borderColor: '#ef4444', 
                        backgroundColor: '#ef4444', 
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ef4444',
                        pointBorderWidth: 3,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Jember (MoM)', 
                        data: [0.5, 0.4, 0.1, 0.5, 0.1, 0.2, 0.5, 0.3, 0.6, 0.1, 0.2], 
                        borderColor: '#f59e0b', 
                        backgroundColor: '#f59e0b', 
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f59e0b',
                        pointBorderWidth: 2,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Banyuwangi (MoM)', 
                        data: [-1.2, -0.8, -0.2, 0.8, 1.5, 3.0, 1.3, -0.4, -1.5, -0.2, 0.1], 
                        borderColor: '#3b82f6', 
                        backgroundColor: '#3b82f6', 
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 3,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Bondowoso (MoM)', 
                        data: [0.2, 0.1, -0.3, 0.4, 0.0, -0.2, 0.8, 0.2, 0.4, 0.0, -0.1], 
                        borderColor: '#06b6d4', 
                        backgroundColor: '#06b6d4', 
                        borderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Lumajang (MoM)', 
                        data: [0.1, 0.3, -0.1, 0.2, 0.2, 0.1, 0.5, 0.1, 0.3, -0.1, 0.0], 
                        borderColor: '#8b5cf6', 
                        backgroundColor: '#8b5cf6', 
                        borderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Kab. Probolinggo (MoM)', 
                        data: [0.8, 0.5, 0.2, 0.9, 0.4, 0.3, 1.2, 0.5, 0.9, 0.3, 0.2], 
                        borderColor: '#ec4899', 
                        backgroundColor: '#ec4899', 
                        borderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4 
                    },
                    { 
                        label: 'IPH Kota Probolinggo (MoM)', 
                        data: [0.3, 0.2, 0.0, 0.3, 0.1, 0.0, 0.4, 0.2, 0.3, 0.1, 0.0], 
                        borderColor: '#10b981', 
                        backgroundColor: '#10b981', 
                        borderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4 
                    }
                ];

                new Chart(ctxInterRegion.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov'],
                        datasets: interRegionDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 12,
                                    font: { size: 10, weight: 'bold' },
                                    color: isDark ? '#cbd5e1' : '#334155'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + (context.raw > 0 ? '+' : '') + context.raw + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: { color: isDark ? '#94a3b8' : '#475569', font: { size: 10, weight: 'bold' } }
                            },
                            y: {
                                min: -3,
                                max: 4,
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: {
                                    stepSize: 1,
                                    callback: function(val) { return (val > 0 ? '+' : '') + val + '%'; },
                                    color: isDark ? '#94a3b8' : '#475569',
                                    font: { size: 10, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }

            // Render GRAFIK LINE CHART Pergerakan Harga Harian Antar 7 Kabupaten/Kota Sekarkijang
            const ctxSekarkijangLine = document.getElementById('sekarkijangDailyLineChart');
            if (ctxSekarkijangLine) {
                @php
                    $activeItemName = $activeComparisonCommodity['name'];
                    $basePriceSitubondo = $activeComparisonCommodity['prices_sekarkijang']['Situbondo'];
                    $basePriceJember = $activeComparisonCommodity['prices_sekarkijang']['Jember'];
                    $basePriceBanyuwangi = $activeComparisonCommodity['prices_sekarkijang']['Banyuwangi'];
                    $basePriceBondowoso = $activeComparisonCommodity['prices_sekarkijang']['Bondowoso'];
                    $basePriceLumajang = $activeComparisonCommodity['prices_sekarkijang']['Lumajang'];
                    $basePriceKabProbolinggo = $activeComparisonCommodity['prices_sekarkijang']['Kab_Probolinggo'];
                    $basePriceKotaProbolinggo = $activeComparisonCommodity['prices_sekarkijang']['Kota_Probolinggo'];
                @endphp

                const activeCommodityName = @json($activeItemName);
                const dailyLabels = dbLabels.length > 0 ? dbLabels : ['1 Okt', '5 Okt', '10 Okt', '15 Okt', '20 Okt', '25 Okt', '27 Okt'];

                // Fungsi membuat variasi tren harga harian per kota
                function generateDailySeries(basePrice, variance) {
                    const count = dailyLabels.length;
                    const series = [];
                    for (let i = 0; i < count; i++) {
                        const delta = (i % 2 === 0 ? 1 : -1) * (i * variance);
                        series.push(basePrice + delta);
                    }
                    return series;
                }

                const sekarkijangDailyDatasets = [
                    {
                        label: 'Situbondo',
                        data: generateDailySeries(@json($basePriceSitubondo), 200),
                        borderColor: '#3b82f6',
                        backgroundColor: '#3b82f6',
                        borderWidth: 3,
                        pointRadius: 4,
                        tension: 0.3
                    },
                    {
                        label: 'Jember',
                        data: generateDailySeries(@json($basePriceJember), 250),
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    },
                    {
                        label: 'Banyuwangi',
                        data: generateDailySeries(@json($basePriceBanyuwangi), 180),
                        borderColor: '#10b981',
                        backgroundColor: '#10b981',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    },
                    {
                        label: 'Bondowoso',
                        data: generateDailySeries(@json($basePriceBondowoso), 150),
                        borderColor: '#06b6d4',
                        backgroundColor: '#06b6d4',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    },
                    {
                        label: 'Lumajang',
                        data: generateDailySeries(@json($basePriceLumajang), 120),
                        borderColor: '#8b5cf6',
                        backgroundColor: '#8b5cf6',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    },
                    {
                        label: 'Kab. Probolinggo',
                        data: generateDailySeries(@json($basePriceKabProbolinggo), 220),
                        borderColor: '#ec4899',
                        backgroundColor: '#ec4899',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    },
                    {
                        label: 'Kota Probolinggo',
                        data: generateDailySeries(@json($basePriceKotaProbolinggo), 100),
                        borderColor: '#64748b',
                        backgroundColor: '#64748b',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3
                    }
                ];

                new Chart(ctxSekarkijangLine.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: dailyLabels,
                        datasets: sekarkijangDailyDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 12,
                                    font: { size: 10, weight: 'bold' },
                                    color: isDark ? '#cbd5e1' : '#334155'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: { color: isDark ? '#94a3b8' : '#475569', font: { size: 10, weight: 'bold' } }
                            },
                            y: {
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: {
                                    callback: function(val) { return 'Rp ' + val.toLocaleString('id-ID'); },
                                    color: isDark ? '#94a3b8' : '#475569',
                                    font: { size: 10, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }

            // Render Chart Analisis & Prediksi AI
            const ctxPrediksi = document.getElementById('chartPrediksiAnalisis');
            if (ctxPrediksi) {
                const actualData = dbDatasets[0] ? dbDatasets[0].data : [];
                new Chart(ctxPrediksi.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: dbLabels,
                        datasets: [
                            { label: 'Harga Aktual Real (Pasar Panji)', data: actualData, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3 },
                            { label: 'Proyeksi AI (HBKN)', data: dbLabels.map((_, i) => 62000 + (i * 300)), borderColor: '#f59e0b', borderDash: [5, 5], tension: 0.3 }
                        ]
                    },
                    options: commonChartConfig.options
                });
            }

            // Render Leaflet Map (Khusus untuk Tab Monitoring Pasar)
            const situbondoMarkets = @json($situbondoMarketsList);

            const mapEl = document.getElementById('map');
            if (mapEl) {
                const map = L.map('map').setView([-7.7025, 113.9922], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                situbondoMarkets.forEach(m => {
                    L.marker([m.lat, m.lng]).addTo(map).bindPopup(`<b>${m.name}</b><br>Kecamatan: ${m.district}`);
                });
            }
        });
    </script>
</body>
</html>