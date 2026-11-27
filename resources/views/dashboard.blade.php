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
        $usingSp2kpData = in_array($currentTab, ['ews', 'stok', 'intervensi', 'analisis', 'laporan'], true);
        $dataSource = $usingSp2kpData ? 'sp2kp' : request()->query('source', 'sikeppo');
        $sourceDisplayName = $dataSource === 'sp2kp' ? 'SP2KP' : 'SIKEPO';
        
        $today = \Carbon\Carbon::today();
        $startDate = $currentTab === 'sekarkijang'
            ? ($sekarkijangComparisonStartDate ?? $dashboardStartDate ?? request()->query('start_date', $today->copy()->startOfMonth()->toDateString()))
            : ($dashboardStartDate ?? request()->query('start_date', $today->copy()->startOfMonth()->toDateString()));
        $endDate = $currentTab === 'sekarkijang'
            ? ($sekarkijangComparisonEndDate ?? $dashboardEndDate ?? request()->query('end_date', $today->toDateString()))
            : ($dashboardEndDate ?? request()->query('end_date', $today->toDateString()));
        $selectedCommodity = request()->query('commodity', 'all');
        $usingSp2kpMonitoringData = $currentTab === 'monitoring_harga' && $dataSource === 'sp2kp';
        $usingSikepoMonitoringData = $currentTab === 'monitoring_harga' && $dataSource === 'sikeppo';
        $usingSpreadsheetMonitoringData = $usingSp2kpMonitoringData || $usingSikepoMonitoringData;
        $sp2kpCommodityData = [];
        $sp2kpChartData = collect();
        $sp2kpCommodityOptions = collect();
        $sikepoCommodityData = [];
        $sikepoChartData = collect();
        $sikepoCommodityOptions = collect();
        $uploadedMonitoringRows = collect();
        $monitoringPricesTable = $dataSource === 'sp2kp'
            ? 'monitoring_sp2kp_prices'
            : 'monitoring_sikepo_prices';
        if ($usingSpreadsheetMonitoringData
            && \Illuminate\Support\Facades\Schema::hasTable($monitoringPricesTable)) {
            $uploadedMonitoringRows = \Illuminate\Support\Facades\DB::table($monitoringPricesTable)
                ->orderBy('price_date')
                ->get();
        }

        if ($usingSp2kpMonitoringData) {
            if ($uploadedMonitoringRows->isNotEmpty()) {
                foreach ($uploadedMonitoringRows as $record) {
                    $commodityName = $record->commodity_name;
                    $priceDate = $record->price_date;
                    $price = (float) $record->price;
                    $sp2kpCommodityData[$commodityName] = [
                        'unit' => $record->unit,
                        'prices' => $sp2kpCommodityData[$commodityName]['prices'] ?? [],
                    ];
                    $sp2kpCommodityData[$commodityName]['prices'][$priceDate] = $price;
                    if ($priceDate >= $startDate
                        && $priceDate <= $endDate
                        && ($selectedCommodity === 'all' || $selectedCommodity === $commodityName)) {
                        $sp2kpChartData->push((object) [
                            'nama_barang' => $commodityName,
                            'tanggal_dibuat' => $priceDate,
                            'harga' => $price,
                        ]);
                    }
                }
            } else {
                $sp2kpCsv = fopen(database_path('data/sp2kp-situbondo-2026-11.csv'), 'r');
                if ($sp2kpCsv === false) {
                    throw new \RuntimeException('Data harga SP2KP Situbondo tidak dapat dibaca.');
                }

                $sp2kpHeader = fgetcsv($sp2kpCsv, 0, ',', '"', '');
                if (! is_array($sp2kpHeader) || count($sp2kpHeader) < 3) {
                    fclose($sp2kpCsv);
                    throw new \RuntimeException('Format data harga SP2KP Situbondo tidak valid.');
                }

                $sp2kpDates = array_slice($sp2kpHeader, 2);
                while (($sp2kpRow = fgetcsv($sp2kpCsv, 0, ',', '"', '')) !== false) {
                    $commodityName = trim($sp2kpRow[0] ?? '');
                    if ($commodityName === '') {
                        continue;
                    }

                    $prices = [];
                    foreach ($sp2kpDates as $dateIndex => $priceDate) {
                        $price = $sp2kpRow[$dateIndex + 2] ?? null;
                        if ($price === null || ! is_numeric($price) || (float) $price <= 0) {
                            continue;
                        }

                        $prices[$priceDate] = (float) $price;
                        if ($priceDate >= $startDate
                            && $priceDate <= $endDate
                            && ($selectedCommodity === 'all' || $selectedCommodity === $commodityName)) {
                            $sp2kpChartData->push((object) [
                                'nama_barang' => $commodityName,
                                'tanggal_dibuat' => $priceDate,
                                'harga' => (float) $price,
                            ]);
                        }
                    }

                    $sp2kpCommodityData[$commodityName] = [
                        'unit' => trim($sp2kpRow[1] ?? ''),
                        'prices' => $prices,
                    ];
                }
                fclose($sp2kpCsv);
            }
            $sp2kpCommodityOptions = collect(array_keys($sp2kpCommodityData))
                ->sort()
                ->values();
        }

        if ($usingSikepoMonitoringData) {
            if ($uploadedMonitoringRows->isNotEmpty()) {
                foreach ($uploadedMonitoringRows as $record) {
                    $commodityName = $record->commodity_name;
                    $priceDate = $record->price_date;
                    $price = (float) $record->price;
                    $sikepoCommodityData[$commodityName] = [
                        'unit' => $record->unit,
                        'prices' => $sikepoCommodityData[$commodityName]['prices'] ?? [],
                    ];
                    $sikepoCommodityData[$commodityName]['prices'][$priceDate] = $price;
                    if ($priceDate >= $startDate
                        && $priceDate <= $endDate
                        && ($selectedCommodity === 'all' || $selectedCommodity === $commodityName)) {
                        $sikepoChartData->push((object) [
                            'nama_barang' => $commodityName,
                            'tanggal_dibuat' => $priceDate,
                            'harga' => $price,
                        ]);
                    }
                }
            } else {
                $sikepoCsv = fopen(database_path('data/sikepo-situbondo-2026-11.csv'), 'r');
                if ($sikepoCsv === false) {
                    throw new \RuntimeException('Data harga SIKEPO Situbondo tidak dapat dibaca.');
                }

                $sikepoHeader = fgetcsv($sikepoCsv, 0, ',', '"', '');
                if (! is_array($sikepoHeader) || count($sikepoHeader) < 5) {
                    fclose($sikepoCsv);
                    throw new \RuntimeException('Format data harga SIKEPO Situbondo tidak valid.');
                }

                while (($sikepoRow = fgetcsv($sikepoCsv, 0, ',', '"', '')) !== false) {
                    $commodityName = trim($sikepoRow[2] ?? '');
                    $priceDate = trim($sikepoRow[4] ?? '');
                    $price = $sikepoRow[3] ?? null;
                    if ($commodityName === '' || $priceDate === '' || ! is_numeric($price) || (float) $price <= 0) {
                        continue;
                    }

                    $sikepoCommodityData[$commodityName]['prices'][$priceDate] = (float) $price;
                    if ($priceDate >= $startDate
                        && $priceDate <= $endDate
                        && ($selectedCommodity === 'all' || $selectedCommodity === $commodityName)) {
                        $sikepoChartData->push((object) [
                            'nama_barang' => $commodityName,
                            'tanggal_dibuat' => $priceDate,
                            'harga' => (float) $price,
                        ]);
                    }
                }
                fclose($sikepoCsv);
            }

            foreach ($sikepoCommodityData as $commodityName => $commodity) {
                $sikepoCommodityData[$commodityName]['unit'] = $commodity['unit'] ?? '';
            }
            $sikepoCommodityOptions = collect(array_keys($sikepoCommodityData))
                ->sort()
                ->values();
        }

        // PENGELOLAAN RENTANG TANGGAL DINAMIS
        try {
            $periodStart = \Carbon\Carbon::parse($startDate);
            $periodEnd = \Carbon\Carbon::parse($endDate);
            if ($periodStart->gt($periodEnd)) {
                $periodStart = $today->copy()->startOfMonth();
                $periodEnd = $today->copy();
            }
        } catch (\Exception $e) {
            $periodStart = $today->copy()->startOfMonth();
            $periodEnd = $today->copy();
        }

        $displayDate = $periodEnd->translatedFormat('d F Y');
        $selectedSekarkijangWeek = '';
        if ($periodStart->format('Y-m') === $periodEnd->format('Y-m')) {
            $startDay = (int) $periodStart->format('j');
            $endDay = (int) $periodEnd->format('j');
            $daysInSelectedMonth = (int) $periodStart->daysInMonth;
            $weekNumber = (int) floor(($startDay - 1) / 7) + 1;
            $weekStart = (($weekNumber - 1) * 7) + 1;
            $weekEnd = min($weekNumber * 7, $daysInSelectedMonth);

            if ($startDay === 1 && $endDay === $daysInSelectedMonth) {
                $selectedSekarkijangWeek = 'all';
            } elseif ($startDay === $weekStart && $endDay === $weekEnd) {
                $selectedSekarkijangWeek = (string) $weekNumber;
            }
        }

        // QUERY DATABASE MYSQL UNTUK SIKEPO / SP2KP
        $query = \Illuminate\Support\Facades\DB::table('harga_pasar')
            ->where('nama_pasar', 'LIKE', '%Panji%')
            ->whereBetween('tanggal_dibuat', [$chartStartDate ?? $startDate, $chartEndDate ?? $endDate]);

        if ($selectedCommodity !== 'all') {
            $query->where('nama_barang', $selectedCommodity);
        }

        $rawDbData = $usingSpreadsheetMonitoringData
            ? ($usingSp2kpMonitoringData ? $sp2kpChartData : $sikepoChartData)
            : ($currentTab === 'monitoring_harga'
                ? $query->orderBy('tanggal_dibuat', 'asc')->get()
                : collect());
        $chartSeedPrices = collect();
        if ($currentTab === 'monitoring_harga' && ! $usingSpreadsheetMonitoringData) {
            $latestPriceDates = \Illuminate\Support\Facades\DB::table('harga_pasar')
                ->select('nama_barang', \Illuminate\Support\Facades\DB::raw('MAX(tanggal_dibuat) as tanggal_dibuat'))
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->where('tanggal_dibuat', '<', $chartStartDate ?? $startDate);

            if ($selectedCommodity !== 'all') {
                $latestPriceDates->where('nama_barang', $selectedCommodity);
            }

            $chartSeedPrices = \Illuminate\Support\Facades\DB::table('harga_pasar as prices')
                ->joinSub($latestPriceDates->groupBy('nama_barang'), 'latest_prices', function ($join) {
                    $join->on('prices.nama_barang', '=', 'latest_prices.nama_barang')
                        ->on('prices.tanggal_dibuat', '=', 'latest_prices.tanggal_dibuat');
                })
                ->where('prices.nama_pasar', 'LIKE', '%Panji%')
                ->select('prices.nama_barang', 'prices.tanggal_dibuat', \Illuminate\Support\Facades\DB::raw('AVG(prices.harga) as harga'))
                ->groupBy('prices.nama_barang', 'prices.tanggal_dibuat')
                ->get();

            $rawDbData = $chartSeedPrices->concat($rawDbData);
        }

        // Dataset grafik per komoditas dari database
        $groupedCommodities = $rawDbData->groupBy('nama_barang');
        $palette = ['#ef4444', '#f59e0b', '#dc2626', '#d97706', '#ea580c', '#10b981', '#06b6d4', '#8b5cf6', '#059669', '#a855f7', '#b91c1c', '#64748b', '#f43f5e', '#0284c7'];
        $colorIdx = 0;

        $dbChartDatasets = [];
        $seedPriceByCommodity = $chartSeedPrices->pluck('harga', 'nama_barang');
        $actualDates = $rawDbData->pluck('tanggal_dibuat')->unique();
        $inRangeDates = $actualDates->filter(function ($date) use ($chartStartDate, $startDate) {
            return $date >= ($chartStartDate ?? $startDate);
        });
        $timelineStart = $inRangeDates->isNotEmpty()
            ? \Carbon\Carbon::parse($inRangeDates->min())->min(\Carbon\Carbon::parse($startDate))
            : \Carbon\Carbon::parse($chartStartDate ?? $startDate);
        $timelineEnd = $actualDates->isNotEmpty()
            ? \Carbon\Carbon::parse($actualDates->max())->max(\Carbon\Carbon::parse($endDate))
            : \Carbon\Carbon::parse($endDate);
        $chartDates = $usingSpreadsheetMonitoringData
            ? $inRangeDates->unique()->sort()->values()
            : collect(\Carbon\CarbonPeriod::create($timelineStart, $timelineEnd))
                ->map(function ($date) {
                    return $date->toDateString();
                });
        $dbChartLabels = $chartDates->map(function ($date) {
            return \Carbon\Carbon::parse($date)->translatedFormat('j M');
        })->values()->toArray();

        foreach ($groupedCommodities as $bName => $records) {
            $priceMap = $records->pluck('harga', 'tanggal_dibuat')->toArray();
            $dataPoints = [];
            $lastKnownPrice = isset($seedPriceByCommodity[$bName])
                ? (float) $seedPriceByCommodity[$bName]
                : null;
            if ($lastKnownPrice !== null && $dataSource === 'sp2kp' && ! $usingSpreadsheetMonitoringData) {
                $lastKnownPrice = round($lastKnownPrice * 1.02);
            }

            foreach ($chartDates as $dt) {
                $val = isset($priceMap[$dt]) ? (float)$priceMap[$dt] : null;
                if ($dataSource === 'sp2kp' && $val !== null && ! $usingSpreadsheetMonitoringData) {
                    $val = round($val * 1.02);
                }

                if ($val !== null) {
                    $lastKnownPrice = $val;
                } elseif (! $usingSpreadsheetMonitoringData) {
                    $val = $lastKnownPrice;
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

        // Master Data Komoditas Strategis Pasar Panji dengan Nilai HAP & Simulasi Perbandingan Harga 7 Kabupaten/Kota Sekarkijang
        $commoditiesMaster = [
            [
                'id' => 'Cabe Rawit Merah', 'name' => 'Cabe Rawit Merah', 'price' => 62000, 'hap' => 57000, 'unit' => 'kg', 'change' => 3.2, 'status' => 'Intervensi', 'cv' => '22.4%', 'risk' => 87, 'stok' => '12.4 Ton', 'need' => '28 Ton', 'days' => 13,
            ],
            [
                'id' => 'Bawang Merah (Super)', 'name' => 'Bawang Merah (Super)', 'price' => 24000, 'hap' => 22000, 'unit' => 'kg', 'change' => 8.3, 'status' => 'Intervensi', 'cv' => '18.1%', 'risk' => 73, 'stok' => '32 Ton', 'need' => '45 Ton', 'days' => 21,
            ],
            [
                'id' => 'Cabe Merah Besar', 'name' => 'Cabe Merah Besar', 'price' => 30000, 'hap' => 25000, 'unit' => 'kg', 'change' => 33.3, 'status' => 'Waspada', 'cv' => '14.8%', 'risk' => 62, 'stok' => '18.5 Ton', 'need' => '22 Ton', 'days' => 25,
            ],
            [
                'id' => 'Telur Ayam Ras', 'name' => 'Telur Ayam Ras', 'price' => 25000, 'hap' => 27000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '3.8%', 'risk' => 48, 'stok' => '58 Ton', 'need' => '60 Ton', 'days' => 29,
            ],
            [
                'id' => 'Daging Ayam Ras', 'name' => 'Daging Ayam Ras', 'price' => 36000, 'hap' => 36750, 'unit' => 'kg', 'change' => -8.3, 'status' => 'Aman', 'cv' => '5.4%', 'risk' => 35, 'stok' => '75 Ton', 'need' => '65 Ton', 'days' => 34,
            ],
            [
                'id' => 'Beras Premium (TH 55)', 'name' => 'Beras Premium (TH 55)', 'price' => 15000, 'hap' => 14900, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.2%', 'risk' => 31, 'stok' => '850 Ton', 'need' => '600 Ton', 'days' => 42,
            ],
            [
                'id' => 'Minyak Goreng Curah', 'name' => 'Minyak Goreng Curah', 'price' => 16500, 'hap' => 15700, 'unit' => 'L', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.5%', 'risk' => 28, 'stok' => '62.000 L', 'need' => '38.000 L', 'days' => 48,
            ],
            [
                'id' => 'Gula Kristal Putih', 'name' => 'Gula Kristal Putih', 'price' => 17000, 'hap' => 17500, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '2.0%', 'risk' => 22, 'stok' => '120 Ton', 'need' => '90 Ton', 'days' => 40,
            ],
            [
                'id' => 'Beras Medium (Bulog)', 'name' => 'Beras Medium (Bulog)', 'price' => 12500, 'hap' => 12500, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '2.1%', 'risk' => 18, 'stok' => '1.250 Ton', 'need' => '820 Ton', 'days' => 45,
            ],
            [
                'id' => 'Bawang Putih Sinco/Honan', 'name' => 'Bawang Putih Sinco/Honan', 'price' => 34000, 'hap' => 32000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '4.2%', 'risk' => 28, 'stok' => '45 Ton', 'need' => '35 Ton', 'days' => 38,
            ],
            [
                'id' => 'Daging Sapi Paha Belakang (Super)', 'name' => 'Daging Sapi Paha Belakang', 'price' => 120000, 'hap' => 130000, 'unit' => 'kg', 'change' => 0.0, 'status' => 'Aman', 'cv' => '1.8%', 'risk' => 16, 'stok' => '25 Ton', 'need' => '18 Ton', 'days' => 41,
            ],
        ];

        if ($usingSpreadsheetMonitoringData) {
            $strategicCommodities = collect($commoditiesMaster)->keyBy('name');
            $hapReferenceNames = [
                'Daging Sapi Paha Belakang (Super)' => 'Daging Sapi Paha Belakang',
                'Beras Medium' => 'Beras Medium (Bulog)',
                'Beras Premium' => 'Beras Premium (TH 55)',
                'Cabai Merah Besar' => 'Cabe Merah Besar',
                'Cabai Rawit Merah' => 'Cabe Rawit Merah',
                'Bawang Merah' => 'Bawang Merah (Super)',
                'Minyak Goreng Sawit Curah' => 'Minyak Goreng Curah',
                'Gula Pasir Curah' => 'Gula Kristal Putih',
                'Bawang Putih Honan' => 'Bawang Putih Sinco/Honan',
                'Daging Sapi Paha Belakang' => 'Daging Sapi Paha Belakang',
            ];
            $sourceCommodityData = $usingSp2kpMonitoringData
                ? $sp2kpCommodityData
                : $sikepoCommodityData;
            $commoditiesMaster = [];

            foreach ($sourceCommodityData as $name => $commodity) {
                $periodPrices = array_filter(
                    $commodity['prices'],
                    fn ($priceDate) => $priceDate >= $startDate && $priceDate <= $endDate,
                    ARRAY_FILTER_USE_KEY
                );
                if ($periodPrices === []) {
                    continue;
                }

                ksort($periodPrices);
                $latestDate = array_key_last($periodPrices);
                $priceValues = array_values($periodPrices);
                $latestPrice = $priceValues[array_key_last($priceValues)];
                $previousPrice = count($priceValues) > 1
                    ? $priceValues[count($priceValues) - 2]
                    : null;
                $strategicName = $hapReferenceNames[$name] ?? $name;
                $strategicCommodity = $strategicCommodities->get($strategicName);

                $commoditiesMaster[] = [
                    'id' => $name,
                    'name' => $name,
                    'price' => $latestPrice,
                    'hap' => $strategicCommodity['hap'] ?? null,
                    'unit' => $commodity['unit'] !== ''
                        ? $commodity['unit']
                        : ($usingSp2kpMonitoringData ? '—' : ($strategicCommodity['unit'] ?? '—')),
                    'change' => $previousPrice === null
                        ? 0
                        : round((($latestPrice - $previousPrice) / $previousPrice) * 100, 1),
                    'status' => $strategicCommodity['status'] ?? 'Acuan HAP tidak tersedia',
                    'date' => $latestDate,
                ];
            }
        }

        // Ambil daftar unik komoditas dari database MySQL untuk dropdown filter
        $dbCommodityOptions = $usingSp2kpData
            ? match ($currentTab) {
                'ews' => $ewsCommodityOptions,
                'stok' => $stockCommodityOptions,
                'intervensi' => $interventionCommodityOptions,
                default => $analysisCommodityOptions,
            }
            : ($usingSp2kpMonitoringData
                ? $sp2kpCommodityOptions
                : ($usingSikepoMonitoringData
                    ? $sikepoCommodityOptions
                    : \Illuminate\Support\Facades\DB::table('harga_pasar')
                        ->where('nama_pasar', 'LIKE', '%Panji%')
                        ->distinct()
                        ->pluck('nama_barang')));
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
    <div class="flex-1 min-w-0 ml-64 min-h-screen flex flex-col">

        <!-- Top Navigation Header + GLOBAL FILTER FORM + ADMIN TPID PROFILE DROPDOWN -->
        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40 px-6 py-3.5 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    @if($currentTab === 'pengaturan')
                        <i class="fa-solid fa-user-gear text-blue-500"></i> Pengaturan Akun &amp; Profil Pengguna TPID
                    @elseif($currentTab === 'laporan')
                        <i class="fa-solid fa-file-invoice text-blue-500"></i> Laporan Harga SP2KP yang Perlu Ditinjau
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
                        <i class="fa-solid fa-store text-blue-500"></i> Monitoring Harga (Sumber: {{ $sourceDisplayName }})
                    @elseif($currentTab === 'monitoring_pasar')
                        <i class="fa-solid fa-map-location-dot text-indigo-500"></i> Monitoring Persebaran Pasar Rakyat
                    @else
                        <i class="fa-solid fa-house text-blue-500"></i> Executive Overview SIGAP SEKARKIJANG
                    @endif
                </h2>
            </div>

            <!-- RIGHT CONTAINER: FILTER FORM & ADMIN PROFILE DROPDOWN -->
            <div class="flex flex-wrap items-center gap-3">
                
                @if(! in_array($currentTab, ['pengaturan', 'laporan', 'monitoring_pasar', 'sekarkijang'], true))
                <!-- GLOBAL FILTER BAR -->
                <form id="dashboardFilterForm" method="GET" action="{{ url('/') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <input type="hidden" name="source" value="{{ $dataSource }}">
                    <input type="hidden" name="date_filter_applied" value="1">
                    
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <label for="region" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $usingSp2kpData ? 'Wilayah SP2KP:' : 'Pasar:' }}</label>
                        <select id="region" name="region" class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                            @if($usingSp2kpData)
                                @foreach($ewsMarketOptions as $marketName)
                                    <option value="{{ $marketName }}" {{ $curRegion === $marketName ? 'selected' : '' }} class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white">{{ $marketName }}</option>
                                @endforeach
                            @else
                                <option value="Panji" selected class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white">Pasar Rakyat Panji</option>
                            @endif
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Tanggal:</span>
                        <input type="date" name="start_date" value="{{ $startDate }}" data-date-filter class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                        <span class="text-[10px] text-slate-400">s/d</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" data-date-filter class="bg-transparent text-xs font-bold text-slate-800 dark:text-white focus:outline-none cursor-pointer">
                    </div>

                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Komoditas:</span>
                        <select id="commodityFilter" name="commodity" class="bg-transparent text-xs font-bold text-amber-600 dark:text-amber-400 focus:outline-none cursor-pointer max-w-[170px]">
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
                @endif

                <!-- ADMIN TPID PROFILE DROPDOWN -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 bg-blue-900/40 hover:bg-blue-900/60 dark:bg-slate-800 dark:hover:bg-slate-700 border border-blue-800/60 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-white font-bold text-xs transition shadow-sm focus:outline-none">
                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-black text-[11px] shadow">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <span class="max-w-[120px] truncate font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</span>
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
                            <p class="text-slate-900 dark:text-white font-extrabold truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="?tab=pengaturan" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition">
                            <i class="fa-solid fa-user-gear text-blue-500 w-4"></i> Pengaturan Akun
                        </a>
                        <div class="my-1 border-t border-slate-100 dark:border-slate-700/60"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                                <i class="fa-solid fa-right-from-bracket w-4"></i> Keluar (Logout)
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="p-6 space-y-6 flex-1">

            <!-- TAB 1: BERANDA -->
            @if ($currentTab === 'beranda')
                @include('dashboard.pages.beranda')
            @elseif ($currentTab === 'monitoring_harga')
                @include('dashboard.pages.monitoring_harga')
            @elseif ($currentTab === 'monitoring_pasar')
                @include('dashboard.pages.monitoring_pasar')
            @elseif ($currentTab === 'sekarkijang')
                @include('dashboard.pages.sekarkijang')
            @elseif ($currentTab === 'ews')
                @include('dashboard.pages.ews')
            @elseif ($currentTab === 'stok')
                @include('dashboard.pages.stok')
            @elseif ($currentTab === 'intervensi')
                @include('dashboard.pages.intervensi')
            @elseif ($currentTab === 'analisis')
                @include('dashboard.pages.analisis')
            @elseif ($currentTab === 'laporan')
                @include('dashboard.pages.laporan')
            @elseif ($currentTab === 'pengaturan')
                @include('dashboard.pages.pengaturan')
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
            const priceSourceChartLabels = @json($priceSourceChart['labels']);
            const priceSourceChartDatasets = @json($priceSourceChart['datasets']);
            const dashboardFilterForm = document.getElementById('dashboardFilterForm');
            const commodityFilter = document.getElementById('commodityFilter');
            const dateFilterMarker = dashboardFilterForm
                ? dashboardFilterForm.querySelector('[name="date_filter_applied"]')
                : null;
            const dateFilterInputs = dashboardFilterForm
                ? Array.from(dashboardFilterForm.querySelectorAll('[data-date-filter]'))
                : [];

            dateFilterInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    input.dataset.changed = 'true';
                });
            });

            if (commodityFilter && dashboardFilterForm) {
                commodityFilter.addEventListener('change', function() {
                    const query = new URLSearchParams(window.location.search);
                    const hasNonDefaultDates = query.has('start_date') && query.has('end_date')
                        && (query.get('start_date') !== @json($today->copy()->startOfMonth()->toDateString())
                            || query.get('end_date') !== @json($today->toDateString()));
                    const hasSubmittedDates = query.has('date_filter_applied') || hasNonDefaultDates;
                    const hasChangedDates = dateFilterInputs.some(function(input) {
                        return input.dataset.changed === 'true';
                    });

                    if (!hasSubmittedDates && !hasChangedDates) {
                        dateFilterInputs.forEach(function(input) {
                            input.disabled = true;
                        });
                        if (dateFilterMarker) {
                            dateFilterMarker.disabled = true;
                        }
                    }

                    dashboardFilterForm.requestSubmit();
                });
            }

            const sekarkijangMonth = document.getElementById('sekarkijangMonth');
            const sekarkijangWeek = document.getElementById('sekarkijangWeek');
            const sekarkijangStartDate = document.getElementById('sekarkijangStartDate');
            const sekarkijangEndDate = document.getElementById('sekarkijangEndDate');

            if (sekarkijangMonth && sekarkijangWeek && sekarkijangStartDate && sekarkijangEndDate) {
                const updateWeekAvailability = function() {
                    const [year, month] = sekarkijangMonth.value.split('-').map(Number);
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const fifthWeek = sekarkijangWeek.querySelector('option[value="5"]');
                    fifthWeek.disabled = daysInMonth < 29;

                    if (fifthWeek.disabled && sekarkijangWeek.value === '5') {
                        sekarkijangWeek.value = 'all';
                    }
                };

                const applySekarkijangDateFilter = function() {
                    if (!sekarkijangMonth.value) return;

                    updateWeekAvailability();

                    const [year, month] = sekarkijangMonth.value.split('-').map(Number);
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const week = sekarkijangWeek.value || 'all';
                    const startDay = week === 'all' ? 1 : (Number(week) - 1) * 7 + 1;
                    const endDay = week === 'all' ? daysInMonth : Math.min(Number(week) * 7, daysInMonth);

                    if (startDay > daysInMonth) return;

                    const monthValue = String(month).padStart(2, '0');
                    sekarkijangStartDate.value = `${year}-${monthValue}-${String(startDay).padStart(2, '0')}`;
                    sekarkijangEndDate.value = `${year}-${monthValue}-${String(endDay).padStart(2, '0')}`;
                    sekarkijangWeek.value = week;
                    sekarkijangStartDate.form.requestSubmit();
                };

                sekarkijangMonth.addEventListener('change', function() {
                    if (!sekarkijangWeek.value) sekarkijangWeek.value = 'all';
                    applySekarkijangDateFilter();
                });
                sekarkijangWeek.addEventListener('change', applySekarkijangDateFilter);
                updateWeekAvailability();
            }

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
                new Chart(ctxPriceChart.getContext('2d'), {
                    ...commonChartConfig,
                    data: {
                        labels: priceSourceChartLabels,
                        datasets: priceSourceChartDatasets
                    }
                });
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

                const interRegionMonthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov'];
                const monthStartFilter = document.getElementById('iphMonthStart');
                const monthEndFilter = document.getElementById('iphMonthEnd');
                const buildWeeklyIphData = function(startMonth, endMonth) {
                    const labels = [];
                    const datasets = interRegionDatasets.map(function(dataset) {
                        return { ...dataset, data: [] };
                    });

                    for (let monthIndex = startMonth; monthIndex <= endMonth; monthIndex++) {
                        const weekCount = Math.ceil(new Date(2026, monthIndex + 1, 0).getDate() / 7);
                        const monthLabel = interRegionMonthLabels[monthIndex];

                        labels.push(...Array.from(
                            { length: weekCount },
                            function(_, weekIndex) {
                                return 'Minggu ' + (weekIndex + 1) + ' ' + monthLabel;
                            }
                        ));

                        datasets.forEach(function(dataset, datasetIndex) {
                            const sourceDataset = interRegionDatasets[datasetIndex];
                            const currentValue = sourceDataset.data[monthIndex];
                            const previousValue = monthIndex === 0
                                ? currentValue
                                : sourceDataset.data[monthIndex - 1];

                            for (let weekIndex = 1; weekIndex <= weekCount; weekIndex++) {
                                const progress = weekIndex / weekCount;
                                datasets[datasetIndex].data.push(
                                    Number((previousValue + (currentValue - previousValue) * progress).toFixed(2))
                                );
                            }
                        });
                    }

                    return { labels: labels, datasets: datasets };
                };
                const initialWeeklyIphData = buildWeeklyIphData(
                    Number(monthStartFilter.value),
                    Number(monthEndFilter.value)
                );
                const interRegionChart = new Chart(ctxInterRegion.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: initialWeeklyIphData.labels,
                        datasets: initialWeeklyIphData.datasets
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

                const applyInterRegionMonthFilter = function(changedFilter) {
                    if (Number(monthStartFilter.value) > Number(monthEndFilter.value)) {
                        if (changedFilter === monthStartFilter) {
                            monthEndFilter.value = monthStartFilter.value;
                        } else {
                            monthStartFilter.value = monthEndFilter.value;
                        }
                    }

                    const start = Number(monthStartFilter.value);
                    const end = Number(monthEndFilter.value);
                    const weeklyIphData = buildWeeklyIphData(start, end);

                    interRegionChart.data.labels = weeklyIphData.labels;
                    interRegionChart.data.datasets = weeklyIphData.datasets;
                    interRegionChart.update();
                };

                monthStartFilter.addEventListener('change', function() {
                    applyInterRegionMonthFilter(monthStartFilter);
                });
                monthEndFilter.addEventListener('change', function() {
                    applyInterRegionMonthFilter(monthEndFilter);
                });
            }

            const ctxIphWeekly = document.getElementById('iphWeeklyChart');
            if (ctxIphWeekly) {
                const iphWeeklyRows = @json($iphWeeklySummary);
                const iphWeekLabels = @json($iphWeekColumns).map(function(label) {
                    return label.replace('September', 'Sep').replace('Oktober', 'Okt');
                });
                const iphSeriesColors = {
                    '3512': '#4472c4',
                    '3511': '#c0504d',
                    '3510': '#9bbb59',
                    '3509': '#8064a2',
                    '3513': '#4bacc6',
                    '3574': '#f79646',
                    '3508': '#95b3d7'
                };
                const iphWeeklyDatasets = iphWeeklyRows
                    .filter(function(row) {
                        return row.type !== 'Pembanding';
                    })
                    .map(function(row) {
                        const color = iphSeriesColors[row.code];
                        return {
                            label: row.name,
                            data: row.values,
                            borderColor: color,
                            backgroundColor: color,
                            pointBackgroundColor: color,
                            pointBorderColor: color,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: 0
                        };
                    });

                new Chart(ctxIphWeekly.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: iphWeekLabels,
                        datasets: iphWeeklyDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: ctxIphWeekly.parentElement.clientWidth < 900 ? 'bottom' : 'right',
                                labels: {
                                    boxWidth: 12,
                                    padding: 14,
                                    color: isDark ? '#cbd5e1' : '#334155',
                                    font: { size: 11 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + Number(context.raw).toLocaleString('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }) + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Periode Minggu',
                                    color: isDark ? '#cbd5e1' : '#334155',
                                    font: { weight: 'bold' }
                                },
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: {
                                    color: isDark ? '#94a3b8' : '#475569',
                                    font: { size: 10 }
                                }
                            },
                            y: {
                                min: 0,
                                max: 1.8,
                                title: {
                                    display: true,
                                    text: 'Perubahan IPH (%)',
                                    color: isDark ? '#cbd5e1' : '#334155',
                                    font: { weight: 'bold' }
                                },
                                grid: { color: isDark ? '#334155' : '#9ca3af' },
                                ticks: {
                                    stepSize: 0.2,
                                    callback: function(value) {
                                        return Number(value).toLocaleString('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    },
                                    color: isDark ? '#94a3b8' : '#475569',
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }

            const ctxSekarkijangRegionComparison = document.getElementById('sekarkijangRegionComparisonChart');
            if (ctxSekarkijangRegionComparison) {
                new Chart(ctxSekarkijangRegionComparison.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($sekarkijangComparisonLabels),
                        datasets: @json($sekarkijangComparisonDatasets)
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
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
                                        return context.dataset.label + ': Rp ' + Number(context.raw).toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: {
                                    color: isDark ? '#94a3b8' : '#475569',
                                    font: { size: 9, weight: 'bold' },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            },
                            y: {
                                grid: { color: isDark ? '#334155' : '#e2e8f0' },
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                                    },
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
                const analysisActualPrices = @json($analysisActualPrices);
                const analysisForecastPrices = @json($analysisForecastPrices);
                const analysisDatasets = [];
                if (analysisActualPrices.some(function(price) { return price !== null; })) {
                    analysisDatasets.push({
                        label: @json('Harga Aktual SP2KP - '.$selectedRegion),
                        data: analysisActualPrices,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3
                    });
                }
                if (analysisForecastPrices.some(function(price) { return price !== null; })) {
                    analysisDatasets.push({
                        label: @json($analysisRun?->model_name ?? 'Proyeksi Model'),
                        data: analysisForecastPrices,
                        borderColor: '#f59e0b',
                        borderDash: [5, 5],
                        tension: 0.3
                    });
                }

                new Chart(ctxPrediksi.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($analysisChartLabels),
                        datasets: analysisDatasets
                    },
                    options: commonChartConfig.options
                });
            }

            // Render Leaflet Map (Khusus untuk Tab Monitoring Pasar)
            const situbondoMarkets = @json($situbondoVerifiedMarkets);

            const mapEl = document.getElementById('map');
            if (mapEl) {
                const map = L.map('map').setView([-7.7025, 113.9922], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                const marketBounds = [];
                situbondoMarkets.forEach(function(market) {
                    if (!market.location_verified || !Number.isFinite(market.lat) || !Number.isFinite(market.lng)) return;

                    const marker = L.marker([market.lat, market.lng]).addTo(map);
                    const popup = document.createElement('div');
                    const name = document.createElement('strong');
                    name.textContent = market.name;
                    const address = document.createElement('div');
                    address.textContent = market.address || `Kecamatan ${market.district}`;
                    popup.append(name, address);

                    if (market.source_url) {
                        const source = document.createElement('a');
                        source.href = market.source_url;
                        source.target = '_blank';
                        source.rel = 'noopener noreferrer';
                        source.textContent = 'Sumber: OpenStreetMap';
                        popup.append(document.createElement('br'), source);
                    }

                    marker.bindPopup(popup);
                    marketBounds.push([market.lat, market.lng]);
                });

                if (marketBounds.length > 0) {
                    map.fitBounds(marketBounds, { padding: [24, 24], maxZoom: 12 });
                }
            }
        });
    </script>
</body>
</html>