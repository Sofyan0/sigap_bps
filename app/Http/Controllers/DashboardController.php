<?php

namespace App\Http\Controllers;

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
        $ewsMarketOptions = in_array($activeTab, ['ews', 'stok', 'intervensi', 'analisis', 'laporan'], true)
            && \Illuminate\Support\Facades\Schema::hasTable('monitoring_sp2kp_prices')
            ? DB::table('monitoring_sp2kp_prices')
                ->whereNotNull('market_name')
                ->where('market_name', '<>', '')
                ->distinct()
                ->orderBy('market_name')
                ->pluck('market_name')
            : collect();
        if ($ewsMarketOptions->isEmpty()) {
            $ewsMarketOptions = collect(['Situbondo']);
        }
        $requestedRegion = $request->input('region');
        $selectedRegion = in_array($activeTab, ['ews', 'stok', 'intervensi', 'analisis', 'laporan'], true)
            ? ($ewsMarketOptions->contains($requestedRegion) ? $requestedRegion : $ewsMarketOptions->first())
            : $request->input('region', 'Situbondo');
        $savedSekarkijangFilters = $request->session()->get('sekarkijang_filters', []);
        if ($activeTab === 'sekarkijang'
            && ! $request->hasAny(['sekarkijang_commodity', 'start_date', 'end_date'])
            && is_array($savedSekarkijangFilters)
            && isset(
                $savedSekarkijangFilters['commodity'],
                $savedSekarkijangFilters['start_date'],
                $savedSekarkijangFilters['end_date']
            )) {
            $request->merge([
                'sekarkijang_commodity' => $savedSekarkijangFilters['commodity'],
                'start_date' => $savedSekarkijangFilters['start_date'],
                'end_date' => $savedSekarkijangFilters['end_date'],
                'date_filter_applied' => 1,
            ]);
        }
        $dashboardStartDate = $request->input('start_date', $today->copy()->startOfMonth()->toDateString());
        $dashboardEndDate = $request->input('end_date', $today->toDateString());
        $chartStartDate = $dashboardStartDate;
        $chartEndDate = $dashboardEndDate;
        $defaultStartDate = $today->copy()->startOfMonth()->toDateString();
        $hasExplicitDateFilter = $request->boolean('date_filter_applied')
            || ($request->hasAny(['start_date', 'end_date'])
                && ($dashboardStartDate !== $defaultStartDate || $dashboardEndDate !== $today->toDateString()));
        $selectedPriceCommodity = $request->input('commodity', 'all');
        $priceComparisons = $activeTab === 'beranda'
            ? $this->buildPriceComparisons($dashboardStartDate, $dashboardEndDate, $selectedPriceCommodity)
            : collect();
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
                $dashboardStartDate = \Carbon\Carbon::parse($latestUploadedDate)->startOfMonth()->toDateString();
                $chartStartDate = $dashboardStartDate;
            }
        }

        $sp2kpPriceData = in_array($activeTab, ['ews', 'stok', 'intervensi', 'analisis', 'laporan'], true)
            ? $this->buildSp2kpPriceAssessment(
                $dashboardStartDate,
                $dashboardEndDate,
                $selectedRegion,
                $selectedPriceCommodity
            )
            : ['assessment' => null, 'commodity_options' => collect()];
        $ewsAssessment = $activeTab === 'ews' ? $sp2kpPriceData['assessment'] : null;
        $ewsCommodityOptions = $activeTab === 'ews' ? $sp2kpPriceData['commodity_options'] : collect();
        $stockAssessment = $activeTab === 'stok' ? $sp2kpPriceData['assessment'] : null;
        $stockCommodityOptions = $activeTab === 'stok' ? $sp2kpPriceData['commodity_options'] : collect();
        $interventionAssessment = $activeTab === 'intervensi' ? $sp2kpPriceData['assessment'] : null;
        $interventionCommodityOptions = $activeTab === 'intervensi' ? $sp2kpPriceData['commodity_options'] : collect();
        $analysisCommodityOptions = $activeTab === 'analisis' ? $sp2kpPriceData['commodity_options'] : collect();
        $reportAssessment = $activeTab === 'laporan' ? $sp2kpPriceData['assessment'] : null;
        $reportMetrics = ($reportAssessment?->metrics ?? collect())
            ->filter(fn ($metric) => $metric->alert_status !== 'Aman')
            ->values();
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
        $account = $activeTab === 'pengaturan' ? auth()->user() : null;
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
            $analysisMetrics = $sp2kpPriceData['assessment']?->metrics ?? collect();
            $forecastCommodityNames = $analysisRun?->forecasts
                ->pluck('commodity_name')
                ->unique()
                ->values() ?? collect();
            $analysisCommodityAliases = [
                'Cabe Rawit Merah' => 'Cabai Rawit Merah',
                'Cabe Merah Besar' => 'Cabai Merah Besar',
                'Bawang Merah (Super)' => 'Bawang Merah',
                'Beras Premium (TH 55)' => 'Beras Premium',
                'Beras Medium (Bulog)' => 'Beras Medium',
                'Minyak Goreng Curah' => 'Minyak Goreng Sawit Curah',
                'Gula Kristal Putih' => 'Gula Pasir Curah',
                'Bawang Putih Sinco/Honan' => 'Bawang Putih Honan',
                'Daging Sapi Paha Belakang (Super)' => 'Daging Sapi Paha Belakang',
            ];
            $resolveAnalysisCommodity = fn ($name) => $analysisCommodityOptions->contains($name)
                ? $name
                : ($analysisCommodityAliases[$name] ?? $name);
            $requestedAnalysisCommodity = $selectedPriceCommodity !== 'all'
                ? $selectedPriceCommodity
                : $request->input('analysis_commodity');
            $forecastCommodity = $forecastCommodityNames->first(
                fn ($name) => $analysisCommodityOptions->contains($resolveAnalysisCommodity($name))
            );
            $analysisCommodity = $requestedAnalysisCommodity !== null
                && $analysisCommodityOptions->contains($resolveAnalysisCommodity($requestedAnalysisCommodity))
                ? $resolveAnalysisCommodity($requestedAnalysisCommodity)
                : ($forecastCommodity !== null
                    ? $resolveAnalysisCommodity($forecastCommodity)
                    : ($analysisMetrics->first()?->commodity->name
                        ?? $analysisCommodityOptions->first()
                        ?? 'Belum tersedia'));
            $forecastCommodityCandidates = collect([$analysisCommodity])
                ->merge(array_keys(array_filter(
                    $analysisCommodityAliases,
                    fn ($sp2kpName) => $sp2kpName === $analysisCommodity
                )))
                ->all();
            $analysisForecasts = $analysisRun?->forecasts
                ->filter(fn ($forecast) => in_array($forecast->commodity_name, $forecastCommodityCandidates, true))
                ->sortBy('forecast_date')
                ->values() ?? collect();

            $analysisPriceMetric = $analysisMetrics->first(
                fn ($metric) => $metric->commodity->name === $analysisCommodity
            );
            $analysisHistory = $analysisPriceMetric?->price_history ?? collect();
            $actualPriceByDate = $analysisHistory->mapWithKeys(
                fn ($record) => [(string) $record->date => (float) $record->price]
            );
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
        $sekarkijangSitubondoSource = 'SP2KP';
        if ($activeTab === 'sekarkijang') {
            $latestRegionalCommodity = null;
            $sekarkijangCommodityOptions = DB::table('harga_pasar')
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->where('tanggal_dibuat', '<=', $today->toDateString())
                ->distinct()
                ->orderBy('nama_barang')
                ->pluck('nama_barang');
            if (\Illuminate\Support\Facades\Schema::hasTable('monitoring_sp2kp_prices')) {
                $sekarkijangCommodityOptions = $sekarkijangCommodityOptions->merge(
                    DB::table('monitoring_sp2kp_prices')
                        ->where('price_date', '<=', $today->toDateString())
                        ->distinct()
                        ->pluck('commodity_name')
                );
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('sekarkijang_region_prices')) {
                $latestRegionalCommodity = DB::table('sekarkijang_region_prices')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->value('commodity_name');
                $sekarkijangCommodityOptions = $sekarkijangCommodityOptions->merge(
                    DB::table('sekarkijang_region_prices')
                        ->distinct()
                        ->pluck('commodity_name')
                );
            }
            $sekarkijangCommodityOptions = $sekarkijangCommodityOptions
                ->filter(fn ($name) => is_string($name) && $name !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $rankedCommodities = DB::table('harga_pasar')
                ->select('nama_barang', 'tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                ->where('nama_pasar', 'LIKE', '%Panji%')
                ->whereBetween('tanggal_dibuat', [$chartStartDate, $chartEndDate])
                ->groupBy('nama_barang', 'tanggal_dibuat')
                ->orderBy('tanggal_dibuat')
                ->get()
                ->groupBy('nama_barang')
                ->map(fn ($records) => (float) $records->last()->average_price)
                ->sortDesc();
            $defaultComparisonCommodity = $latestRegionalCommodity
                ?? $rankedCommodities->keys()->first();
            $defaultComparisonCommodity ??= $sekarkijangCommodityOptions[0] ?? null;
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
            if (! $hasExplicitDateFilter
                && $sekarkijangSelectedCommodity !== null
                && $sekarkijangSelectedCommodity === $latestRegionalCommodity) {
                $regionalDateRange = DB::table('sekarkijang_region_prices')
                    ->where('commodity_name', $sekarkijangSelectedCommodity)
                    ->selectRaw('MIN(price_date) as first_date, MAX(price_date) as last_date')
                    ->first();
                if ($regionalDateRange?->first_date !== null) {
                    $sekarkijangComparisonStartDate = $regionalDateRange->first_date;
                    $sekarkijangComparisonEndDate = $regionalDateRange->last_date;
                }
            }

            if ($sekarkijangSelectedCommodity !== null) {
                $regionColors = [
                    '3512' => '#3b82f6',
                    '3511' => '#c0504d',
                    '3510' => '#9bbb59',
                    '3509' => '#8064a2',
                    '3513' => '#4bacc6',
                    '3574' => '#f79646',
                    '3508' => '#95b3d7',
                ];
                $localPrices = collect();
                if (\Illuminate\Support\Facades\Schema::hasTable('monitoring_sp2kp_prices')) {
                    $localPrices = DB::table('monitoring_sp2kp_prices')
                        ->select('price_date', DB::raw('AVG(price) as average_price'))
                        ->where('commodity_name', $sekarkijangSelectedCommodity)
                        ->whereBetween('price_date', [
                            $sekarkijangComparisonStartDate,
                            $sekarkijangComparisonEndDate,
                        ])
                        ->groupBy('price_date')
                        ->get()
                        ->mapWithKeys(fn ($row) => [(string) $row->price_date => (float) $row->average_price]);
                }
                if ($localPrices->isEmpty()) {
                    $sekarkijangSitubondoSource = 'Pasar Panji';
                    $localPrices = DB::table('harga_pasar')
                        ->select('tanggal_dibuat', DB::raw('AVG(harga) as average_price'))
                        ->where('nama_pasar', 'LIKE', '%Panji%')
                        ->where('nama_barang', $sekarkijangSelectedCommodity)
                        ->whereBetween('tanggal_dibuat', [
                            $sekarkijangComparisonStartDate,
                            $sekarkijangComparisonEndDate,
                        ])
                        ->groupBy('tanggal_dibuat')
                        ->get()
                        ->mapWithKeys(fn ($row) => [(string) $row->tanggal_dibuat => (float) $row->average_price]);
                }

                $uploadedPricesByRegion = collect();
                if (\Illuminate\Support\Facades\Schema::hasTable('sekarkijang_region_prices')) {
                    $uploadedPricesByRegion = DB::table('sekarkijang_region_prices')
                        ->select('region_code', 'price_date', DB::raw('AVG(price) as average_price'))
                        ->where('commodity_name', $sekarkijangSelectedCommodity)
                        ->whereBetween('price_date', [
                            $sekarkijangComparisonStartDate,
                            $sekarkijangComparisonEndDate,
                        ])
                        ->groupBy('region_code', 'price_date')
                        ->get()
                        ->groupBy('region_code')
                        ->map(fn ($rows) => $rows->mapWithKeys(
                            fn ($row) => [(string) $row->price_date => (float) $row->average_price]
                        ));
                }

                $comparisonDateKeys = $localPrices->keys()
                    ->merge($uploadedPricesByRegion->flatMap(fn ($prices) => $prices->keys()))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                foreach ($comparisonDateKeys as $dateKey) {
                    $sekarkijangComparisonLabels[] = \Carbon\Carbon::parse($dateKey)->translatedFormat('j M');
                }

                if ($comparisonDateKeys !== []
                    && ($localPrices->isNotEmpty() || $uploadedPricesByRegion->isNotEmpty())) {
                    foreach ($iphWeeklySummary as $region) {
                        $regionCode = $region['code'];
                        if ($regionCode === '35') {
                            continue;
                        }

                        $regionPrices = $regionCode === '3512'
                            ? $localPrices
                            : $uploadedPricesByRegion->get($regionCode, collect());
                        $sekarkijangComparisonDatasets[] = [
                            'label' => $region['name'].($regionCode === '3512' ? ' ('.$sekarkijangSitubondoSource.')' : ''),
                            'data' => array_map(
                                fn ($dateKey) => $regionPrices->get($dateKey),
                                $comparisonDateKeys
                            ),
                            'borderColor' => $regionColors[$regionCode],
                            'backgroundColor' => $regionColors[$regionCode],
                            'borderWidth' => 2,
                            'pointRadius' => 2,
                            'pointHoverRadius' => 4,
                            'fill' => false,
                            'tension' => 0.2,
                        ];
                    }
                }
            }
            if ($sekarkijangSelectedCommodity !== null) {
                $request->session()->put('sekarkijang_filters', [
                    'commodity' => $sekarkijangSelectedCommodity,
                    'start_date' => $sekarkijangComparisonStartDate,
                    'end_date' => $sekarkijangComparisonEndDate,
                ]);
            }
        }

        $priceSourceChart = $activeTab === 'beranda'
            ? $this->buildPriceSourceChart($dashboardStartDate, $dashboardEndDate, $selectedPriceCommodity)
            : ['labels' => [], 'datasets' => []];

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
            'analysisCommodityOptions',
            'dashboardSettings', 'account',
            'berandaSummary', 'priceComparisons', 'priceSourceChart',
            'situbondoMarketsList', 'situbondoVerifiedMarkets', 'marketStatusCounts',
            'sekarkijangCommodityOptions', 'sekarkijangSelectedCommodity',
            'sekarkijangComparisonStartDate', 'sekarkijangComparisonEndDate',
            'sekarkijangComparisonLabels', 'sekarkijangComparisonDatasets',
            'sekarkijangSitubondoSource',
            'ewsMarketOptions', 'ewsCommodityOptions', 'stockAssessment', 'stockCommodityOptions',
            'reportAssessment', 'reportMetrics',
            'interventionAssessment', 'interventionCommodityOptions'
        ));
    }

    private function buildPriceComparisons(string $startDate, string $endDate, string $selectedCommodity): \Illuminate\Support\Collection
    {
        $sourceRows = [
            'sikepo' => $this->getPriceComparisonRows('sikepo', $startDate, $endDate),
            'sp2kp' => $this->getPriceComparisonRows('sp2kp', $startDate, $endDate),
        ];
        $selectedCommodityKey = $selectedCommodity === 'all'
            ? null
            : $this->normalizeComparisonCommodity($selectedCommodity);
        $pricesBySource = ['sikepo' => [], 'sp2kp' => []];

        foreach ($sourceRows as $source => $rows) {
            foreach ($rows as $row) {
                $commodityKey = $this->normalizeComparisonCommodity($row->commodity_name);
                if ($commodityKey === '' || ($selectedCommodityKey !== null && $commodityKey !== $selectedCommodityKey)) {
                    continue;
                }

                $date = (string) $row->price_date;
                if ($date < $startDate || $date > $endDate || (float) $row->price <= 0) {
                    continue;
                }

                $pricesBySource[$source][$commodityKey][$date]['prices'][] = (float) $row->price;
                $pricesBySource[$source][$commodityKey][$date]['name'] = $row->commodity_name;
                $pricesBySource[$source][$commodityKey][$date]['unit'] = $row->unit ?? '';
            }
        }

        $comparisons = collect();
        $allCommodities = array_unique(array_merge(
            array_keys($pricesBySource['sikepo']),
            array_keys($pricesBySource['sp2kp'])
        ));

        foreach ($allCommodities as $commodityKey) {
            $sikepoDates = array_keys($pricesBySource['sikepo'][$commodityKey] ?? []);
            $sp2kpDates = array_keys($pricesBySource['sp2kp'][$commodityKey] ?? []);
            $commonDates = array_intersect(
                $sikepoDates,
                $sp2kpDates
            );

            if ($commonDates !== []) {
                rsort($commonDates);
                $sikepoDate = $sp2kpDate = $commonDates[0];
            } else {
                rsort($sikepoDates);
                rsort($sp2kpDates);
                $sikepoDate = $sikepoDates[0] ?? null;
                $sp2kpDate = $sp2kpDates[0] ?? null;
            }

            $sikepo = $sikepoDate === null ? null : $pricesBySource['sikepo'][$commodityKey][$sikepoDate];
            $sp2kp = $sp2kpDate === null ? null : $pricesBySource['sp2kp'][$commodityKey][$sp2kpDate];
            $sikepoPrice = $sikepo === null ? null : array_sum($sikepo['prices']) / count($sikepo['prices']);
            $sp2kpPrice = $sp2kp === null ? null : array_sum($sp2kp['prices']) / count($sp2kp['prices']);
            $sikepoUnit = trim($sikepo['unit'] ?? '');
            $sp2kpUnit = trim($sp2kp['unit'] ?? '');
            $unitsDiffer = $sikepo !== null && $sp2kp !== null && $sikepoUnit !== ''
                && $sp2kpUnit !== ''
                && \Illuminate\Support\Str::lower($sikepoUnit) !== \Illuminate\Support\Str::lower($sp2kpUnit);
            $differenceReason = $sikepo === null || $sp2kp === null
                ? 'Data tidak tersedia'
                : ($sikepoDate !== $sp2kpDate
                    ? 'Tanggal berbeda'
                    : ($unitsDiffer ? 'Satuan berbeda' : null));

            $comparisons->push([
                'commodity' => $sp2kp['name'] ?? $sikepo['name'],
                'sikepo_date' => $sikepoDate,
                'sp2kp_date' => $sp2kpDate,
                'sikepo_unit' => $sikepoUnit,
                'sp2kp_unit' => $sp2kpUnit,
                'sikepo_price' => $sikepoPrice,
                'sp2kp_price' => $sp2kpPrice,
                'difference' => $differenceReason === null ? $sp2kpPrice - $sikepoPrice : null,
                'difference_reason' => $differenceReason,
            ]);
        }

        return $comparisons->sortBy('commodity', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function getPriceComparisonRows(string $source, string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        $table = $source === 'sp2kp' ? 'monitoring_sp2kp_prices' : 'monitoring_sikepo_prices';
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return collect();
        }

        $query = DB::table($table)
            ->select('commodity_name', 'unit', 'price_date', 'price')
            ->whereBetween('price_date', [$startDate, $endDate])
            ->where('price', '>', 0);

        if ($source === 'sikepo') {
            $query->where('market_name', 'LIKE', '%Panji%');
        } else {
            $query->where(function ($marketQuery) {
                $marketQuery->where('market_name', 'LIKE', '%Situbondo%')
                    ->orWhere('market_name', 'LIKE', '%Panji%');
            });
        }

        return $query->orderBy('price_date')->get();
    }

    private function normalizeComparisonCommodity(string $commodity): string
    {
        $normalized = \Illuminate\Support\Str::lower(trim(preg_replace('/\s+/u', ' ', $commodity) ?? $commodity));

        return preg_replace('/^cabe(?=\s|$)/u', 'cabai', $normalized) ?? $normalized;
    }

    private function buildPriceSourceChart(string $startDate, string $endDate, string $selectedCommodity): array
    {
        $selectedCommodityKey = $selectedCommodity === 'all'
            ? null
            : $this->normalizeComparisonCommodity($selectedCommodity);
        $sourcePrices = ['sikepo' => [], 'sp2kp' => []];
        $commodityNames = [];
        $chartDates = [];

        foreach ([
            'sikepo' => $this->getPriceComparisonRows('sikepo', $startDate, $endDate),
            'sp2kp' => $this->getPriceComparisonRows('sp2kp', $startDate, $endDate),
        ] as $source => $rows) {
            foreach ($rows as $row) {
                $commodityKey = $this->normalizeComparisonCommodity($row->commodity_name);
                if ($commodityKey === '' || ($selectedCommodityKey !== null && $commodityKey !== $selectedCommodityKey)) {
                    continue;
                }

                $date = (string) $row->price_date;
                $sourcePrices[$source][$commodityKey][$date]['total'] =
                    ($sourcePrices[$source][$commodityKey][$date]['total'] ?? 0) + (float) $row->price;
                $sourcePrices[$source][$commodityKey][$date]['count'] =
                    ($sourcePrices[$source][$commodityKey][$date]['count'] ?? 0) + 1;
                $commodityNames[$commodityKey][$source] = $row->commodity_name;
                $chartDates[$date] = $date;
            }
        }

        $chartDates = array_values($chartDates);
        sort($chartDates);
        $commodityKeys = array_unique(array_merge(
            array_keys($sourcePrices['sikepo']),
            array_keys($sourcePrices['sp2kp'])
        ));
        sort($commodityKeys, SORT_NATURAL | SORT_FLAG_CASE);

        $palette = ['#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#059669', '#a855f7', '#b91c1c', '#64748b', '#f43f5e', '#0284c7'];
        $datasets = [];
        foreach ($commodityKeys as $index => $commodityKey) {
            $displayName = $commodityNames[$commodityKey]['sp2kp']
                ?? $commodityNames[$commodityKey]['sikepo'];
            $color = $palette[$index % count($palette)];

            foreach (['sikepo', 'sp2kp'] as $source) {
                $dailyPrices = $sourcePrices[$source][$commodityKey] ?? [];
                if ($dailyPrices === []) {
                    continue;
                }

                $data = array_map(function ($date) use ($dailyPrices) {
                    if (! isset($dailyPrices[$date])) {
                        return null;
                    }

                    return $dailyPrices[$date]['total'] / $dailyPrices[$date]['count'];
                }, $chartDates);

                $datasets[] = [
                    'label' => $displayName.' ('.strtoupper($source).')',
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'borderDash' => $source === 'sp2kp' ? [6, 4] : [],
                    'borderWidth' => 2,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 4,
                    'fill' => false,
                    'spanGaps' => false,
                    'tension' => 0.25,
                ];
            }
        }

        return [
            'labels' => array_map(
                fn ($date) => \Carbon\Carbon::parse($date)->translatedFormat('j M'),
                $chartDates
            ),
            'datasets' => $datasets,
        ];
    }

    private function buildSp2kpPriceAssessment(
        string $startDate,
        string $endDate,
        string $marketName,
        string $selectedCommodity
    ): array {
        $priceRows = collect();
        $commodityOptions = collect();
        $hasUploadedPrices = \Illuminate\Support\Facades\Schema::hasTable('monitoring_sp2kp_prices')
            && DB::table('monitoring_sp2kp_prices')->exists();

        if ($hasUploadedPrices) {
            $commodityOptions = DB::table('monitoring_sp2kp_prices')
                ->where('market_name', $marketName)
                ->whereNotNull('commodity_name')
                ->where('commodity_name', '<>', '')
                ->distinct()
                ->orderBy('commodity_name')
                ->pluck('commodity_name');
            $priceRows = DB::table('monitoring_sp2kp_prices')
                ->select('commodity_name', 'unit', 'price_date', 'price')
                ->where('market_name', $marketName)
                ->whereBetween('price_date', [$startDate, $endDate])
                ->where('price', '>', 0)
                ->orderBy('price_date')
                ->get();
        } elseif (is_file(database_path('data/sp2kp-situbondo-2026-11.csv'))) {
            $file = fopen(database_path('data/sp2kp-situbondo-2026-11.csv'), 'r');
            if ($file === false) {
                throw new \RuntimeException('Data harga SP2KP Situbondo tidak dapat dibaca.');
            }

            $headers = fgetcsv($file, 0, ',', '"', '');
            if (! is_array($headers) || count($headers) < 3) {
                fclose($file);
                throw new \RuntimeException('Format data harga SP2KP Situbondo tidak valid.');
            }

            $dates = array_slice($headers, 2);
            while (($row = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $commodityName = trim($row[0] ?? '');
                if ($commodityName === '') {
                    continue;
                }

                foreach ($dates as $index => $date) {
                    $price = $row[$index + 2] ?? null;
                    if ($price === null || ! is_numeric($price) || (float) $price <= 0) {
                        continue;
                    }

                    $priceRows->push((object) [
                        'commodity_name' => $commodityName,
                        'unit' => trim($row[1] ?? ''),
                        'price_date' => $date,
                        'price' => (float) $price,
                    ]);
                }
            }
            fclose($file);
            $marketName = 'Situbondo';
        }

        if ($commodityOptions->isEmpty()) {
            $commodityOptions = $priceRows->pluck('commodity_name')
                ->filter(fn ($name) => is_string($name) && $name !== '')
                ->unique()
                ->sort()
                ->values();
        }
        $filteredRows = $priceRows->filter(
            fn ($row) => $row->price_date >= $startDate
                && $row->price_date <= $endDate
                && ($selectedCommodity === 'all' || $row->commodity_name === $selectedCommodity)
        );

        $metrics = $filteredRows->groupBy('commodity_name')
            ->map(function ($rows, $name) {
                $dailyPrices = $rows->groupBy('price_date')
                    ->map(fn ($dailyRows) => (float) $dailyRows->avg('price'))
                    ->sortKeys();
                $prices = $dailyPrices->values()->all();
                $latestPrice = (float) end($prices);
                $firstPrice = (float) reset($prices);
                $inflationRate = count($prices) > 1
                    ? (($latestPrice - $firstPrice) / $firstPrice) * 100
                    : null;
                $meanPrice = array_sum($prices) / count($prices);
                $variance = array_sum(array_map(
                    fn ($price) => ($price - $meanPrice) ** 2,
                    $prices
                )) / count($prices);
                $volatility = $meanPrice > 0
                    ? (sqrt($variance) / $meanPrice) * 100
                    : 0;
                $inflationRisk = $inflationRate === null
                    ? 0
                    : min(100, max(0, $inflationRate * 5));
                $volatilityRisk = min(100, $volatility * 5);
                $riskScore = (int) round(($inflationRisk * 0.7) + ($volatilityRisk * 0.3));

                return (object) [
                    'commodity' => (object) ['name' => $name],
                    'price' => $latestPrice,
                    'first_price' => $firstPrice,
                    'first_date' => $dailyPrices->keys()->first(),
                    'latest_date' => $dailyPrices->keys()->last(),
                    'price_history' => $dailyPrices
                        ->map(fn ($price, $date) => (object) ['date' => $date, 'price' => $price])
                        ->values(),
                    'unit' => $rows->first()->unit,
                    'inflation_rate' => $inflationRate === null ? null : round($inflationRate, 2),
                    'volatility_cv' => round($volatility, 2),
                    'risk_score' => $riskScore,
                    'alert_status' => $this->ewsStatus($riskScore),
                ];
            })
            ->sortByDesc('risk_score')
            ->values();

        if ($metrics->isEmpty()) {
            return ['assessment' => null, 'commodity_options' => $commodityOptions];
        }

        $overallRiskScore = (int) round($metrics->avg('risk_score'));
        $latestObservedDate = $filteredRows->max('price_date');

        return [
            'assessment' => (object) [
                'observed_on' => $latestObservedDate,
                'market_name' => $marketName,
                'overall_risk_score' => $overallRiskScore,
                'overall_status' => $this->ewsStatus($overallRiskScore),
                'metrics' => $metrics,
            ],
            'commodity_options' => $commodityOptions,
        ];
    }

    private function ewsStatus(int $riskScore): string
    {
        return $riskScore >= 70
            ? 'Intervensi'
            : ($riskScore >= 45 ? 'Waspada' : 'Aman');
    }
}