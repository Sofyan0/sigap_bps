<?php

namespace App\Http\Controllers;

use App\Models\EwsAssessment;
use App\Models\AnalysisRun;
use App\Models\DashboardSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now();
        $activeTab = $request->input('tab', 'beranda');
        $dashboardStartDate = $request->input('start_date', $today->copy()->startOfMonth()->toDateString());
        $dashboardEndDate = $request->input('end_date', $today->toDateString());
        $chartStartDate = $dashboardStartDate;
        $chartEndDate = $dashboardEndDate;
        $defaultStartDate = $today->copy()->startOfMonth()->toDateString();
        $hasExplicitDateFilter = $request->boolean('date_filter_applied')
            || ($request->hasAny(['start_date', 'end_date'])
                && ($dashboardStartDate !== $defaultStartDate || $dashboardEndDate !== $today->toDateString()));
        $selectedPriceCommodity = $request->input('commodity', 'all');
        $priceDateQuery = DB::table('harga_pasar')
            ->where('nama_pasar', 'LIKE', '%Panji%');
        if ($selectedPriceCommodity !== 'all') {
            $priceDateQuery->where('nama_barang', $selectedPriceCommodity);
        }

        if (!$hasExplicitDateFilter && !(clone $priceDateQuery)
            ->whereBetween('tanggal_dibuat', [$chartStartDate, $chartEndDate])
            ->exists()) {
            $latestPriceDate = (clone $priceDateQuery)
                ->where('tanggal_dibuat', '<=', $today->toDateString())
                ->max('tanggal_dibuat');

            if ($latestPriceDate !== null) {
                $chartEndDate = \Carbon\Carbon::parse($latestPriceDate)->toDateString();
                $chartStartDate = \Carbon\Carbon::parse($latestPriceDate)->startOfMonth()->toDateString();
            }
        }

        if (! $hasExplicitDateFilter
            && $activeTab === 'monitoring_harga'
            && in_array($request->input('source', 'sikeppo'), ['sikeppo', 'sp2kp'], true)) {
            $monitoringTable = $request->input('source', 'sikeppo') === 'sp2kp'
                ? 'monitoring_sp2kp_prices'
                : 'monitoring_sikepo_prices';
            $uploadedPriceQuery = \Illuminate\Support\Facades\Schema::hasTable($monitoringTable)
                ? DB::table($monitoringTable)
                : null;
            if ($selectedPriceCommodity !== 'all') {
                $uploadedPriceQuery?->where('commodity_name', $selectedPriceCommodity);
            }

            $latestUploadedDate = $uploadedPriceQuery?->max('price_date');
            if ($latestUploadedDate !== null) {
                $dashboardEndDate = \Carbon\Carbon::parse($latestUploadedDate)->toDateString();
                $dashboardStartDate = \Carbon\Carbon::parse($latestUploadedDate)->startOfMonth()->toDateString();
                $chartStartDate = $dashboardStartDate;
                $chartEndDate = $dashboardEndDate;
            }
        }

        $ewsAssessment = $activeTab === 'ews'
            ? EwsAssessment::with(['metrics' => function ($query) {
                $query->whereHas('commodity', function ($commodityQuery) {
                    $commodityQuery->where('is_active', true);
                })->with('commodity');
            }])
                ->orderByDesc('observed_on')
                ->orderByDesc('id')
                ->first()
            : null;
        $analysisRun = $activeTab === 'analisis'
            ? AnalysisRun::with('forecasts')
                ->orderByDesc('generated_at')
                ->orderByDesc('id')
                ->first()
            : null;
        $dashboardSettings = $activeTab === 'pengaturan'
            ? DashboardSetting::firstOrCreate(
                ['id' => 1],
                [
                    'admin_name' => 'Admin TPID Situbondo',
                    'email' => 'admin.tpid@situbondokab.go.id',
                    'institution' => 'Bagian Perekonomian Setda',
                    'employee_id' => '19850412 201001 1 008',
                ]
            )
            : null;
        $analysisForecasts = collect();
        $analysisHistory = collect();
        $analysisChartLabels = [];
        $analysisActualPrices = [];
        $analysisForecastPrices = [];
        $analysisCommodity = 'Cabe Rawit Merah';
        $situbondoMarketsList = collect();
        $situbondoVerifiedMarkets = collect();
        $marketStatusCounts = [
            'total' => 0,
            'Intervensi' => 0,
            'Waspada' => 0,
            'Stabil' => 0,
        ];
        $berandaSummary = [
            'average_change' => null,
            'commodities_up' => 0,
            'commodities_down' => 0,
            'commodities_monitored' => 0,
        ];

        if ($activeTab === 'analisis') {
            $forecastCommodityNames = $analysisRun?->forecasts
                ->pluck('commodity_name')
                ->unique()
                ->values() ?? collect();
            $analysisCommodity = $request->input(
                'analysis_commodity',
                $forecastCommodityNames->first() ?? 'Cabe Rawit Merah'
            );
            $analysisForecasts = $analysisRun?->forecasts
                ->where('commodity_name', $analysisCommodity)
                ->sortBy('forecast_date')
                ->values() ?? collect();

            $analysisHistory = DB::table('harga_pasar')
                ->select('tanggal_dibuat', DB::raw('AVG(harga) as harga'))
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->where('nama_barang', $analysisCommodity)
                ->whereBetween('tanggal_dibuat', [$dashboardStartDate, $dashboardEndDate])
                ->groupBy('tanggal_dibuat')
                ->orderBy('tanggal_dibuat')
                ->get();

            $actualPriceByDate = $analysisHistory->mapWithKeys(function ($record) {
                return [(string) $record->tanggal_dibuat => (float) $record->harga];
            });
            $forecastPriceByDate = $analysisForecasts->mapWithKeys(function ($forecast) {
                return [$forecast->forecast_date->toDateString() => (float) $forecast->predicted_price];
            });
            $chartDates = $actualPriceByDate->keys()
                ->merge($forecastPriceByDate->keys())
                ->unique()
                ->sort()
                ->values();
            $lastActualDate = $actualPriceByDate->keys()->last();

            foreach ($chartDates as $chartDate) {
                $analysisChartLabels[] = \Carbon\Carbon::parse($chartDate)->translatedFormat('j M');
                $analysisActualPrices[] = $actualPriceByDate->get($chartDate);
                $analysisForecastPrices[] = $forecastPriceByDate->get(
                    $chartDate,
                    $chartDate === $lastActualDate && $analysisForecasts->isNotEmpty()
                        ? $actualPriceByDate->get($chartDate)
                        : null
                );
            }
        }

        if ($activeTab === 'beranda') {
            $dailyCommodityPrices = DB::table('harga_pasar')
                ->select('nama_barang', 'tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->whereBetween('tanggal_dibuat', [$dashboardStartDate, $dashboardEndDate])
                ->groupBy('nama_barang', 'tanggal_dibuat')
                ->orderBy('tanggal_dibuat')
                ->get()
                ->groupBy('nama_barang');

            $percentageChanges = [];
            foreach ($dailyCommodityPrices as $dailyPrices) {
                if ($dailyPrices->count() < 2) {
                    continue;
                }

                $firstPrice = (float) $dailyPrices->first()->average_price;
                $lastPrice = (float) $dailyPrices->last()->average_price;
                if ($firstPrice <= 0) {
                    continue;
                }

                $percentageChange = (($lastPrice - $firstPrice) / $firstPrice) * 100;
                $percentageChanges[] = $percentageChange;

                if ($percentageChange > 0) {
                    $berandaSummary['commodities_up']++;
                } elseif ($percentageChange < 0) {
                    $berandaSummary['commodities_down']++;
                }
            }

            $berandaSummary['commodities_monitored'] = $dailyCommodityPrices->count();
            if ($percentageChanges !== []) {
                $berandaSummary['average_change'] = array_sum($percentageChanges) / count($percentageChanges);
            }
        }

        if ($activeTab === 'monitoring_pasar') {
            $situbondoMarketsList = DB::table('markets')
                ->where('region', 'Situbondo')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($market) {
                    return [
                        'name' => $market->name,
                        'district' => $market->district,
                        'status' => $market->monitoring_status,
                        'lat' => (float) $market->latitude,
                        'lng' => (float) $market->longitude,
                        'location_verified' => (bool) $market->location_verified,
                        'address' => $market->location_address,
                        'source_url' => $market->location_source_url,
                    ];
                });
            $situbondoVerifiedMarkets = $situbondoMarketsList
                ->where('location_verified', true)
                ->values();

            $marketStatusCounts['total'] = $situbondoMarketsList->count();
            foreach ($situbondoMarketsList as $market) {
                if (array_key_exists($market['status'], $marketStatusCounts)) {
                    $marketStatusCounts[$market['status']]++;
                }
            }
        }

        // Filter Parameters
        $periodType = $request->input('period_type', 'daily');
        $date = $request->input('date', date('Y-m-d'));
        $month = $request->input('month', date('Y-m'));
        $year = $request->input('year', date('Y'));
        
        $selectedRegion = $request->input('region', 'Situbondo');
        $selectedMarket = $request->input('market', 'all');

        // Disparitas Commodity Multi-Select Filter (Default: BWG-01)
        $selectedCommodity = $request->input('commodity_comparison', ['BWG-01']);
        if (!is_array($selectedCommodity)) {
            $selectedCommodity = [$selectedCommodity];
        }

        $trendStartDate = $request->input('trend_start_date', date('Y-m-d', strtotime('-20 days', strtotime($date))));
        $trendEndDate = $request->input('trend_end_date', $date);

        // Trend Commodity Multi-Select Filter (Default: all)
        $trendCommodity = $request->input('trend_commodity', ['all']);
        if (!is_array($trendCommodity)) {
            $trendCommodity = [$trendCommodity];
        }

        // Formatted Date Subtitle
        if ($periodType === 'monthly') {
            $formattedDate = date('F Y', strtotime($month . '-01'));
        } elseif ($periodType === 'yearly') {
            $formattedDate = 'Tahun ' . $year;
        } else {
            $formattedDate = date('d F Y', strtotime($date));
        }

        $iphWeekColumns = [
            'M4 September 2026',
            'M1 Oktober 2026',
            'M2 Oktober 2026',
            'M3 Oktober 2026',
            'M4 Oktober 2026',
            'M5 Oktober 2026',
        ];
        $iphWeeklySummary = [
            [
                'code' => '3512',
                'name' => 'Kabupaten Situbondo',
                'values' => [1.13, 0.80, 0.65, 0.77, 0.62, 0.27],
                'type' => 'Aktual',
                'source' => 'Data Aktual (Laporan 360 Kab/Kota Non-IHK)',
            ],
            [
                'code' => '3511',
                'name' => 'Kabupaten Bondowoso',
                'values' => [1.27, 0.54, 0.69, 0.84, 0.83, 0.65],
                'type' => 'Aktual',
                'source' => 'Data Aktual (Laporan 360 Kab/Kota Non-IHK)',
            ],
            [
                'code' => '3510',
                'name' => 'Kabupaten Banyuwangi',
                'values' => [1.17, 0.59, 0.63, 0.72, 0.66, 0.50],
                'type' => 'Proksi',
                'source' => 'Rata-rata 4 Kab. Tapal Kuda (Wilayah IHK BPS)',
            ],
            [
                'code' => '3509',
                'name' => 'Kabupaten Jember',
                'values' => [1.17, 0.59, 0.63, 0.72, 0.66, 0.50],
                'type' => 'Proksi',
                'source' => 'Rata-rata 4 Kab. Tapal Kuda (Wilayah IHK BPS)',
            ],
            [
                'code' => '3513',
                'name' => 'Kabupaten Probolinggo',
                'values' => [0.68, 0.36, 0.41, 0.48, 0.53, 0.55],
                'type' => 'Aktual',
                'source' => 'Data Aktual (Laporan 360 Kab/Kota Non-IHK)',
            ],
            [
                'code' => '3574',
                'name' => 'Kota Probolinggo',
                'values' => [0.68, 0.36, 0.41, 0.48, 0.53, 0.55],
                'type' => 'Proksi',
                'source' => 'Mengacu Kab. Probolinggo (Wilayah IHK BPS)',
            ],
            [
                'code' => '3508',
                'name' => 'Kabupaten Lumajang',
                'values' => [1.60, 0.67, 0.77, 0.80, 0.65, 0.54],
                'type' => 'Aktual',
                'source' => 'Data Aktual (Laporan 360 Kab/Kota Non-IHK)',
            ],
            [
                'code' => '35',
                'name' => 'Provinsi Jawa Timur',
                'values' => [2.01, 0.93, 1.08, 1.04, 0.98, 0.78],
                'type' => 'Pembanding',
                'source' => 'Data Aktual Sheet Provinsi (Agregat Jawa Timur)',
            ],
        ];
        foreach ($iphWeeklySummary as &$iphRow) {
            $iphValues = array_values(array_filter($iphRow['values'], 'is_numeric'));
            $iphRow['average'] = round(array_sum($iphValues) / count($iphValues), 2);
            $iphRow['highest'] = max($iphValues);
            $iphRow['lowest'] = min($iphValues);
        }
        unset($iphRow);

        // Master Market Data
        $allMarkets = [
            ['id' => 'mimbaan', 'name' => 'Pasar Rakyat Mimbaan', 'district' => 'Panji', 'region' => 'Situbondo', 'lat' => -7.7025, 'lng' => 113.9922],
            ['id' => 'ardirejo', 'name' => 'Pasar Rakyat Ardirejo', 'district' => 'Panji', 'region' => 'Situbondo', 'lat' => -7.7082, 'lng' => 113.9985],
            ['id' => 'besuki_baru', 'name' => 'Pasar Rakyat Besuki Baru', 'district' => 'Besuki', 'region' => 'Situbondo', 'lat' => -7.7348, 'lng' => 113.6931],
            ['id' => 'tanjung_jember', 'name' => 'Pasar Tanjung', 'district' => 'Kaliwates', 'region' => 'Jember', 'lat' => -8.1721, 'lng' => 113.6981],
            ['id' => 'banyuwangi_induk', 'name' => 'Pasar Blambangan', 'district' => 'Banyuwangi', 'region' => 'Banyuwangi', 'lat' => -8.2112, 'lng' => 114.3682],
            ['id' => 'bondowoso_induk', 'name' => 'Pasar Induk Bondowoso', 'district' => 'Bondowoso', 'region' => 'Bondowoso', 'lat' => -7.9134, 'lng' => 113.8211],
            ['id' => 'lumajang_baru', 'name' => 'Pasar Baru Lumajang', 'district' => 'Lumajang', 'region' => 'Lumajang', 'lat' => -8.1331, 'lng' => 113.2241],
        ];

        // Filter Markets
        $filteredMarkets = collect($allMarkets)->where('region', $selectedRegion)->values()->all();
        $marketsLocation = $selectedMarket === 'all' ? $filteredMarkets : collect($allMarkets)->where('id', $selectedMarket)->values()->all();
        
        $dateSeed = crc32($periodType . $date . $month . $year);
        $offset = ($dateSeed % 7) * 150;

        // Komoditas Data
        $defaultCommodities = [
            'BRS-01' => ['code' => 'BRS-01', 'name' => 'Beras Medium', 'unit' => 'kg', 'base_m1' => 12400, 'base_m2' => 12500, 'color' => '#3b82f6'],
            'BRS-02' => ['code' => 'BRS-02', 'name' => 'Beras Premium', 'unit' => 'kg', 'base_m1' => 14800, 'base_m2' => 15200, 'color' => '#1d4ed8'],
            'BWG-01' => ['code' => 'BWG-01', 'name' => 'Bawang Merah', 'unit' => 'kg', 'base_m1' => 37800 + $offset, 'base_m2' => 38100 + $offset, 'color' => '#8b5cf6'],
            'BWG-02' => ['code' => 'BWG-02', 'name' => 'Bawang Putih Honan', 'unit' => 'kg', 'base_m1' => 35200 + $offset, 'base_m2' => 35900 + $offset, 'color' => '#a855f7'],
            'CBI-01' => ['code' => 'CBI-01', 'name' => 'Cabai Rawit Merah', 'unit' => 'kg', 'base_m1' => 42000 + $offset, 'base_m2' => 45000 + $offset, 'color' => '#ef4444'],
            'CBI-02' => ['code' => 'CBI-02', 'name' => 'Cabai Merah Keriting', 'unit' => 'kg', 'base_m1' => 38500, 'base_m2' => 37000, 'color' => '#dc2626'],
            'TLR-01' => ['code' => 'TLR-01', 'name' => 'Telur Ayam Ras', 'unit' => 'kg', 'base_m1' => 25500 + ($offset/2), 'base_m2' => 27300 + ($offset/2), 'color' => '#f59e0b'],
            'DGN-01' => ['code' => 'DGN-01', 'name' => 'Daging Ayam Broiler', 'unit' => 'kg', 'base_m1' => 32600 - $offset, 'base_m2' => 32400 - $offset, 'color' => '#f97316'],
            'MYK-01' => ['code' => 'MYK-01', 'name' => 'Minyakita (HET)', 'unit' => 'L', 'base_m1' => 15700, 'base_m2' => 16000, 'color' => '#eab308'],
            'GLA-01' => ['code' => 'GLA-01', 'name' => 'Gula Pasir Curah', 'unit' => 'kg', 'base_m1' => 17000, 'base_m2' => 17500, 'color' => '#14b8a6'],
        ];

        $sessionData = $request->session()->get('custom_commodities', $defaultCommodities);

        $commodities = [];
        $totalUp = 0; $totalDown = 0; $totalStable = 0;

        foreach ($sessionData as $item) {
            $m1 = (float)$item['base_m1'];
            $m2 = (float)$item['base_m2'];
            $diff = $m2 - $m1;
            $pct = $m1 > 0 ? round(($m2 / $m1) * 100, 2) : 100;
            
            $item['m1'] = $m1; $item['m2'] = $m2; $item['diff'] = $diff; $item['pct'] = $pct;
            $item['status'] = $diff > 0 ? 'Naik' : ($diff < 0 ? 'Turun' : 'Stabil');

            if ($diff > 0) $totalUp++;
            elseif ($diff < 0) $totalDown++;
            else $totalStable++;

            $commodities[] = $item;
        }

        // Date Period Range
        $startDateObj = new \DateTime($trendStartDate);
        $endDateObj = new \DateTime($trendEndDate);
        if ($startDateObj > $endDateObj) {
            $trendStartDate = $trendEndDate;
            $startDateObj = new \DateTime($trendStartDate);
        }

        $interval = new \DateInterval('P1D');
        $dateRangeObj = new \DatePeriod($startDateObj, $interval, $endDateObj->modify('+1 day'));
        
        $trendDateLabels = [];
        foreach ($dateRangeObj as $dt) {
            $trendDateLabels[] = $dt->format('d M');
        }

        $chartDatasets = [];
        $daysCount = count($trendDateLabels);

        foreach ($commodities as $c) {
            if (!in_array('all', $trendCommodity) && !in_array($c['code'], $trendCommodity)) continue;

            $dataPoints = [];
            $baseVal = $c['m1'];
            for ($i = 0; $i < $daysCount; $i++) {
                $stepOffset = ($i * 120) - rand(50, 150);
                $dataPoints[] = max(1000, $baseVal + $stepOffset);
            }

            $chartDatasets[] = [
                'label' => $c['name'],
                'data' => $dataPoints,
                'borderColor' => $c['color'],
                'backgroundColor' => $c['color'],
                'borderWidth' => 2,
                'tension' => 0.35,
                'pointRadius' => 3
            ];
        }
        $chartData = ['labels' => $trendDateLabels, 'datasets' => $chartDatasets];

        $sekarkijangCommodityOptions = [];
        $sekarkijangSelectedCommodity = null;
        $sekarkijangComparisonStartDate = $chartStartDate;
        $sekarkijangComparisonEndDate = $chartEndDate;
        $sekarkijangComparisonLabels = [];
        $sekarkijangComparisonDatasets = [];
        if ($activeTab === 'sekarkijang') {
            $sekarkijangPrices = DB::table('harga_pasar')
                ->select('nama_barang', 'tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->whereBetween('tanggal_dibuat', [$chartStartDate, $chartEndDate])
                ->groupBy('nama_barang', 'tanggal_dibuat')
                ->orderBy('tanggal_dibuat')
                ->get();
            $pricesByCommodity = $sekarkijangPrices->groupBy('nama_barang');
            $rankedCommodities = $pricesByCommodity
                ->map(function ($records, $name) {
                    $latestPrice = $records->last();

                    return [
                        'name' => $name,
                        'latest_date' => (string) $latestPrice->tanggal_dibuat,
                        'latest_price' => round((float) $latestPrice->average_price * 1.02),
                    ];
                })
                ->sortByDesc('latest_price')
                ->values();
            $sekarkijangCommodityOptions = DB::table('harga_pasar')
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->where('tanggal_dibuat', '<=', $today->toDateString())
                ->distinct()
                ->orderBy('nama_barang')
                ->pluck('nama_barang')
                ->all();

            $defaultComparisonCommodity = $rankedCommodities->first();
            $defaultComparisonCommodity = $defaultComparisonCommodity['name']
                ?? ($sekarkijangCommodityOptions[0] ?? null);
            $requestedComparisonCommodity = $request->input('sekarkijang_commodity');
            $sekarkijangSelectedCommodity = in_array($requestedComparisonCommodity, $sekarkijangCommodityOptions, true)
                ? $requestedComparisonCommodity
                : $defaultComparisonCommodity;
            $sekarkijangComparisonStartDate = $hasExplicitDateFilter
                ? $dashboardStartDate
                : $chartStartDate;
            $sekarkijangComparisonEndDate = $hasExplicitDateFilter
                ? $dashboardEndDate
                : $today->toDateString();

            if ($sekarkijangSelectedCommodity !== null) {
                $comparisonPrices = DB::table('harga_pasar')
                    ->select('tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                    ->where('nama_pasar', 'LIKE', '%Panji%')
                    ->where('nama_barang', $sekarkijangSelectedCommodity)
                    ->whereBetween('tanggal_dibuat', [
                        $sekarkijangComparisonStartDate,
                        $sekarkijangComparisonEndDate,
                    ])
                    ->groupBy('tanggal_dibuat')
                    ->orderBy('tanggal_dibuat')
                    ->get()
                    ->keyBy('tanggal_dibuat');
                $baselinePrice = DB::table('harga_pasar')
                    ->select('tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                    ->where('nama_pasar', 'LIKE', '%Panji%')
                    ->where('nama_barang', $sekarkijangSelectedCommodity)
                    ->where('tanggal_dibuat', '<=', $sekarkijangComparisonStartDate)
                    ->groupBy('tanggal_dibuat')
                    ->orderByDesc('tanggal_dibuat')
                    ->first();
                $latestLocalPrice = $baselinePrice === null
                    ? null
                    : (float) $baselinePrice->average_price;

                $regionFactors = [
                    '3511' => 0.97,
                    '3510' => 1.05,
                    '3509' => 1.02,
                    '3513' => 0.95,
                    '3574' => 0.99,
                    '3508' => 1.08,
                ];
                $regionColors = [
                    '3512' => '#3b82f6',
                    '3511' => '#c0504d',
                    '3510' => '#9bbb59',
                    '3509' => '#8064a2',
                    '3513' => '#4bacc6',
                    '3574' => '#f79646',
                    '3508' => '#95b3d7',
                ];
                $regionSeries = [];
                foreach ($iphWeeklySummary as $region) {
                    if ($region['code'] !== '35') {
                        $regionSeries[$region['code']] = [];
                    }
                }

                $comparisonStart = \Carbon\Carbon::parse($sekarkijangComparisonStartDate);
                $comparisonEnd = \Carbon\Carbon::parse($sekarkijangComparisonEndDate);
                if ($comparisonStart->lte($comparisonEnd)) {
                    $comparisonDates = new \DatePeriod(
                        $comparisonStart,
                        new \DateInterval('P1D'),
                        $comparisonEnd->copy()->addDay()
                    );

                    foreach ($comparisonDates as $comparisonDate) {
                        $dateKey = $comparisonDate->format('Y-m-d');
                        $actualPrice = $comparisonPrices->get($dateKey);
                        if ($actualPrice !== null) {
                            $latestLocalPrice = (float) $actualPrice->average_price;
                        }
                        $sekarkijangComparisonLabels[] = \Carbon\Carbon::parse($dateKey)->translatedFormat('j M');

                        foreach (array_keys($regionSeries) as $regionCode) {
                            if ((string) $regionCode === '3512') {
                                $regionSeries[$regionCode][] = $actualPrice === null
                                    ? null
                                    : round((float) $actualPrice->average_price * 1.02);
                                continue;
                            }

                            if ($latestLocalPrice === null) {
                                $regionSeries[$regionCode][] = null;
                                continue;
                            }

                            $dailyVariation = ((crc32($regionCode . '|' . $dateKey) % 31) - 15) / 1000;
                            $regionSeries[$regionCode][] = round(
                                $latestLocalPrice * 1.02 * $regionFactors[$regionCode] * (1 + $dailyVariation)
                            );
                        }
                    }
                }

                if ($sekarkijangComparisonLabels !== []) {
                    foreach ($iphWeeklySummary as $region) {
                        $regionCode = $region['code'];
                        if ($regionCode === '35') {
                            continue;
                        }

                        $isSimulated = $regionCode !== '3512';
                        $sekarkijangComparisonDatasets[] = [
                            'label' => $region['name'] . ($isSimulated ? '' : ' (Pasar Panji +2%)'),
                            'data' => $regionSeries[$regionCode],
                            'borderColor' => $regionColors[$regionCode],
                            'backgroundColor' => $regionColors[$regionCode],
                            'borderDash' => $isSimulated ? [6, 4] : [],
                            'borderWidth' => 2,
                            'pointRadius' => 2,
                            'pointHoverRadius' => 4,
                            'fill' => false,
                            'tension' => 0.2,
                        ];
                    }
                }
            }
        }

        return view('dashboard', compact(
            'activeTab', 'commodities', 'date', 'month', 'year', 'periodType', 'formattedDate',
            'dashboardStartDate', 'dashboardEndDate', 'chartStartDate', 'chartEndDate',
            'selectedRegion', 'selectedMarket', 'selectedCommodity',
            'allMarkets', 'filteredMarkets', 'chartData',
            'iphWeekColumns', 'iphWeeklySummary',
            'marketsLocation', 'totalUp', 'totalDown', 'totalStable',
            'trendStartDate', 'trendEndDate', 'trendCommodity', 'ewsAssessment',
            'analysisRun', 'analysisForecasts', 'analysisCommodity', 'analysisHistory',
            'analysisChartLabels', 'analysisActualPrices', 'analysisForecastPrices',
            'dashboardSettings',
            'berandaSummary', 'situbondoMarketsList', 'situbondoVerifiedMarkets', 'marketStatusCounts',
            'sekarkijangCommodityOptions', 'sekarkijangSelectedCommodity',
            'sekarkijangComparisonStartDate', 'sekarkijangComparisonEndDate',
            'sekarkijangComparisonLabels', 'sekarkijangComparisonDatasets'
        ));
    }
}