<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Active Tab Menu Parameter (default: beranda)
        $activeTab = $request->input('tab', 'beranda');

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

        // Data 7 Wilayah Sekarkijang beserta IPH (%)
        $regionsData = [
            'Situbondo'        => ['name' => 'Situbondo', 'iph' => 1.18, 'status' => 'Naik'],
            'Jember'           => ['name' => 'Jember', 'iph' => 2.05, 'status' => 'Naik'],
            'Banyuwangi'       => ['name' => 'Banyuwangi', 'iph' => 0.85, 'status' => 'Naik'],
            'Bondowoso'        => ['name' => 'Bondowoso', 'iph' => -0.42, 'status' => 'Turun'],
            'Lumajang'         => ['name' => 'Lumajang', 'iph' => 0.65, 'status' => 'Naik'],
            'Kab. Probolinggo' => ['name' => 'Kab. Probolinggo', 'iph' => -1.10, 'status' => 'Turun'],
            'Kota Probolinggo' => ['name' => 'Kota Probolinggo', 'iph' => 0.30, 'status' => 'Naik'],
        ];

        $regions = array_keys($regionsData);

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

        // Comparison Matrix
        $comparisonDataMatrix = [
            'BWG-01' => ['Situbondo' => 38100 + $offset, 'Jember' => 37400, 'Banyuwangi' => 40000, 'Bondowoso' => 43933, 'Lumajang' => 35633, 'Kab. Probolinggo' => 15000, 'Kota Probolinggo' => 37600],
            'CBI-01' => ['Situbondo' => 45000 + $offset, 'Jember' => 43500, 'Banyuwangi' => 48000, 'Bondowoso' => 46000, 'Lumajang' => 42000, 'Kab. Probolinggo' => 39000, 'Kota Probolinggo' => 44000],
            'TLR-01' => ['Situbondo' => 27333 + $offset, 'Jember' => 26300, 'Banyuwangi' => 28133, 'Bondowoso' => 25600, 'Lumajang' => 27600, 'Kab. Probolinggo' => 25000, 'Kota Probolinggo' => 27200],
            'BRS-01' => ['Situbondo' => 12500, 'Jember' => 12650, 'Banyuwangi' => 12800, 'Bondowoso' => 12400, 'Lumajang' => 12550, 'Kab. Probolinggo' => 12300, 'Kota Probolinggo' => 12600],
        ];

        $regionColors = [
            'Situbondo' => '#3b82f6', 'Jember' => '#8b5cf6', 'Banyuwangi' => '#ef4444',
            'Bondowoso' => '#f59e0b', 'Lumajang' => '#10b981', 'Kab. Probolinggo' => '#06b6d4', 'Kota Probolinggo' => '#ec4899',
        ];

        $disparityDatasets = [];
        $primaryCode = is_array($selectedCommodity) ? ($selectedCommodity[0] ?? 'BWG-01') : $selectedCommodity;
        if ($primaryCode === 'all') $primaryCode = 'BWG-01';

        $cBaseData = $comparisonDataMatrix[$primaryCode] ?? $comparisonDataMatrix['BWG-01'];
        $activeCommodityObj = collect($commodities)->firstWhere('code', $primaryCode);

        foreach ($regions as $rk) {
            $basePrice = $cBaseData[$rk] ?? 30000;
            $linePoints = [];

            for ($i = 0; $i < $daysCount; $i++) {
                $variance = ($i * 90) + rand(-150, 150);
                $linePoints[] = max(1000, $basePrice + $variance);
            }

            $iphVal = $regionsData[$rk]['iph'];
            $iphText = ($iphVal > 0 ? '+' : '') . $iphVal . '%';

            $disparityDatasets[] = [
                'label' => $rk . ' (IPH: ' . $iphText . ')',
                'data' => $linePoints,
                'borderColor' => $regionColors[$rk] ?? '#64748b',
                'backgroundColor' => $regionColors[$rk] ?? '#64748b',
                'borderWidth' => 2,
                'tension' => 0.3,
                'pointRadius' => 3
            ];
        }

        $maxRegionVal = max($cBaseData);
        $minRegionVal = min($cBaseData);
        $maxRegionName = array_search($maxRegionVal, $cBaseData);
        $minRegionName = array_search($minRegionVal, $cBaseData);
        $avgRegionVal = round(array_sum($cBaseData) / count($cBaseData));

        $interRegionChartData = ['labels' => $trendDateLabels, 'datasets' => $disparityDatasets];
        $selectedIph = $regionsData[$selectedRegion] ?? ['iph' => 0.0, 'status' => 'Stabil'];

        return view('dashboard', compact(
            'activeTab', 'commodities', 'date', 'month', 'year', 'periodType', 'formattedDate', 
            'selectedRegion', 'selectedMarket', 'selectedCommodity',
            'regions', 'regionsData', 'selectedIph', 'allMarkets', 'filteredMarkets', 'chartData', 'interRegionChartData', 
            'marketsLocation', 'activeCommodityObj', 'totalUp', 'totalDown', 'totalStable',
            'maxRegionVal', 'minRegionVal', 'maxRegionName', 'minRegionName', 'avgRegionVal',
            'trendStartDate', 'trendEndDate', 'trendCommodity'
        ));
    }
}