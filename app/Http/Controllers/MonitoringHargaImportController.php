<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class MonitoringHargaImportController extends Controller
{
    private const SEKARKIJANG_REGIONS = [
        '3511' => 'Kabupaten Bondowoso',
        '3510' => 'Kabupaten Banyuwangi',
        '3509' => 'Kabupaten Jember',
        '3513' => 'Kabupaten Probolinggo',
        '3574' => 'Kota Probolinggo',
        '3508' => 'Kabupaten Lumajang',
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source' => 'required|in:sikeppo,sp2kp',
            'excel_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        $upload = $request->file('excel_file');
        $rows = $this->parseUpload(
            $upload->getRealPath(),
            $validated['source'],
            strtolower($upload->getClientOriginalExtension())
        );
        if ($rows === []) {
            throw ValidationException::withMessages([
                'excel_file' => 'Tidak ditemukan data harga valid pada berkas yang diunggah.',
            ]);
        }

        DB::transaction(function () use ($validated, $rows) {
            $table = $this->pricesTable($validated['source']);
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->upsert(
                    $chunk,
                    ['market_name', 'commodity_name', 'price_date'],
                    ['unit', 'price', 'updated_at']
                );
            }
        });

        $sourcePrices = DB::table($this->pricesTable($validated['source']));
        $firstDate = Carbon::parse((clone $sourcePrices)->min('price_date'));
        $latestDate = Carbon::parse((clone $sourcePrices)->max('price_date'));

        return redirect()->route('dashboard', [
            'tab' => 'monitoring_harga',
            'source' => $validated['source'],
            'start_date' => $firstDate->toDateString(),
            'end_date' => $latestDate->toDateString(),
            'date_filter_applied' => 1,
        ])->with('success', count($rows).' data harga berhasil diimpor.');
    }

    public function storeRegion(Request $request)
    {
        $validated = $request->validate([
            'region_code' => 'required|in:'.implode(',', array_keys(self::SEKARKIJANG_REGIONS)),
            'excel_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        $upload = $request->file('excel_file');
        $regionName = self::SEKARKIJANG_REGIONS[$validated['region_code']];
        $rows = $this->parseUpload(
            $upload->getRealPath(),
            'sp2kp',
            strtolower($upload->getClientOriginalExtension()),
            $regionName
        );
        if ($rows === []) {
            throw ValidationException::withMessages([
                'excel_file' => 'Tidak ditemukan data harga valid pada berkas yang diunggah.',
            ]);
        }

        $regionRows = array_map(function ($row) use ($validated, $regionName) {
            return $row + [
                'region_code' => $validated['region_code'],
                'region_name' => $regionName,
            ];
        }, $rows);

        DB::transaction(function () use ($regionRows) {
            foreach (array_chunk($regionRows, 500) as $chunk) {
                DB::table('sekarkijang_region_prices')->upsert(
                    $chunk,
                    ['region_code', 'market_name', 'commodity_name', 'price_date'],
                    ['region_name', 'unit', 'price', 'updated_at']
                );
            }
        });

        $dates = array_column($rows, 'price_date');
        sort($dates);

        return redirect()->route('dashboard', [
            'tab' => 'sekarkijang',
            'start_date' => $dates[0],
            'end_date' => end($dates),
            'date_filter_applied' => 1,
            'sekarkijang_commodity' => $rows[0]['commodity_name'],
        ])->with('success', count($rows).' data harga '.$regionName.' berhasil diimpor.');
    }

    private function pricesTable(string $source): string
    {
        return $source === 'sp2kp'
            ? 'monitoring_sp2kp_prices'
            : 'monitoring_sikepo_prices';
    }

    private function parseUpload(string $path, string $source, string $extension, ?string $defaultMarket = null): array
    {
        $sheetRows = in_array($extension, ['csv', 'txt'], true)
            ? $this->readCsvRows($path)
            : $this->readSpreadsheetRows($path, $source);
        $header = $this->findHeaderRow($sheetRows, $source);

        if ($header === null) {
            throw ValidationException::withMessages([
                'excel_file' => $source === 'sp2kp'
                    ? 'Format SP2KP tidak dikenali. Gunakan format laporan Nama Barang, Unit, Harga, dan PERIODE atau format kolom tanggal; sistem mencari data di semua sheet.'
                    : 'Format SIKEPO tidak dikenali. Pastikan ada kolom komoditas, harga, dan tanggal.',
            ]);
        }

        $metadataRows = array_slice($sheetRows, 0, $header['index']);
        $sheetRows = array_slice($sheetRows, $header['index'] + 1);
        $records = [];

        if ($this->hasLongFormatColumns($header['headers'])) {
            $records = $this->parseLongFormat($header['headers'], $sheetRows, $source, $defaultMarket);
        } elseif ($source === 'sp2kp') {
            if ($this->hasSp2kpReportColumns($header['headers'])) {
                $reportDate = $this->findSp2kpReportDate($metadataRows);
                if ($reportDate === null) {
                    throw ValidationException::withMessages([
                        'excel_file' => 'Tanggal pada baris PERIODE laporan SP2KP tidak ditemukan.',
                    ]);
                }

                $records = $this->parseSp2kpReportFormat($header['headers'], $sheetRows, $reportDate, $defaultMarket);
            } else {
                $records = $this->parseWideSp2kpFormat($header['row'], $sheetRows, $defaultMarket);
            }
        } else {
            throw ValidationException::withMessages([
                'excel_file' => 'Format SIKEPO harus memiliki kolom komoditas, harga, dan tanggal.',
            ]);
        }

        return array_values($records);
    }

    private function readSpreadsheetRows(string $path, string $source): array
    {
        try {
            $workbook = IOFactory::load($path);
            $activeSheet = $workbook->getActiveSheet();
            $activeRows = $activeSheet->toArray(null, true, true, false);

            if ($this->findHeaderRow($activeRows, $source) !== null) {
                return $activeRows;
            }

            foreach ($workbook->getWorksheetIterator() as $worksheet) {
                if ($worksheet === $activeSheet) {
                    continue;
                }

                $rows = $worksheet->toArray(null, true, true, false);
                if ($this->findHeaderRow($rows, $source) !== null) {
                    return $rows;
                }
            }

            return $activeRows;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception) {
            throw ValidationException::withMessages([
                'excel_file' => 'Berkas Excel tidak dapat dibaca. Pastikan format dan isi berkas valid.',
            ]);
        }
    }

    private function findHeaderRow(array $rows, string $source): ?array
    {
        foreach (array_slice($rows, 0, 20, true) as $index => $candidateRow) {
            $headers = array_map(fn ($value) => $this->normalizeHeader($value), $candidateRow);
            $hasCommodity = $this->findCommodityHeaderIndex($headers) !== null;
            $hasLongFormatColumns = $this->findHeaderIndex($headers, ['harga', 'price']) !== null
                && $this->findHeaderIndex($headers, ['tanggal_dibuat', 'tanggal_data', 'tanggal', 'date']) !== null;
            $hasWideFormatColumns = $source === 'sp2kp'
                && $hasCommodity
                && collect($candidateRow)->contains(fn ($value) => $this->parseDate($value) !== null);
            $hasReportFormatColumns = $source === 'sp2kp'
                && $hasCommodity
                && $this->findHeaderIndex($headers, ['harga', 'price']) !== null;

            if (($hasCommodity && $hasLongFormatColumns) || $hasWideFormatColumns || $hasReportFormatColumns) {
                return [
                    'index' => $index,
                    'row' => $candidateRow,
                    'headers' => $headers,
                ];
            }
        }

        return null;
    }

    private function hasSp2kpReportColumns(array $headers): bool
    {
        return $this->findCommodityHeaderIndex($headers) !== null
            && $this->findHeaderIndex($headers, ['harga', 'price']) !== null;
    }

    private function findSp2kpReportDate(array $metadataRows): ?string
    {
        $dates = [];
        foreach ($metadataRows as $row) {
            foreach ($row as $value) {
                preg_match_all('/\b(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})\b/', (string) $value, $matches);
                foreach ($matches[0] as $match) {
                    $date = $this->parseDate($match);
                    if ($date !== null) {
                        $dates[] = $date;
                    }
                }
            }
        }

        return $dates === [] ? null : end($dates);
    }

    private function parseSp2kpReportFormat(array $headers, array $rows, string $reportDate, ?string $defaultMarket = null): array
    {
        $nameIndex = $this->findCommodityHeaderIndex($headers);
        $priceIndex = $this->findHeaderIndex($headers, ['harga', 'price']);
        $unitIndex = $this->findHeaderIndex($headers, ['satuan', 'unit']);

        if ($nameIndex === null || $priceIndex === null) {
            throw ValidationException::withMessages([
                'excel_file' => 'Format laporan SP2KP harus memiliki kolom Nama Barang dan Harga.',
            ]);
        }

        $records = [];
        foreach ($rows as $row) {
            $commodity = trim((string) ($row[$nameIndex] ?? ''));
            $price = $this->parsePrice($row[$priceIndex] ?? null);
            if ($commodity === '' || $price === null) {
                continue;
            }

            $unit = $unitIndex === null ? '' : trim((string) ($row[$unitIndex] ?? ''));
            $this->addRecord($records, 'sp2kp', $commodity, $unit, $price, $reportDate, $defaultMarket ?? 'Situbondo');
        }

        return $records;
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'excel_file' => 'Berkas CSV tidak dapat dibaca.',
            ]);
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $commaColumns = count(str_getcsv($firstLine, ',', '"', ''));
            $semicolonColumns = count(str_getcsv($firstLine, ';', '"', ''));
            $delimiter = $semicolonColumns > $commaColumns ? ';' : ',';
            rewind($handle);

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function parseLongFormat(array $headers, array $rows, string $source, ?string $defaultMarket = null): array
    {
        $nameIndex = $this->findCommodityHeaderIndex($headers);
        $priceIndex = $this->findHeaderIndex($headers, ['harga', 'price']);
        $dateIndex = $this->findHeaderIndex($headers, ['tanggal_dibuat', 'tanggal_data', 'tanggal', 'date']);
        $unitIndex = $this->findHeaderIndex($headers, ['satuan', 'unit']);
        $marketIndex = $this->findHeaderIndex($headers, ['nama_pasar', 'pasar', 'market']);

        if ($nameIndex === null || $priceIndex === null || $dateIndex === null) {
            throw ValidationException::withMessages([
                'excel_file' => 'Format data harus memiliki kolom komoditas, harga, dan tanggal.',
            ]);
        }

        $records = [];
        foreach ($rows as $row) {
            $commodity = trim((string) ($row[$nameIndex] ?? ''));
            $price = $this->parsePrice($row[$priceIndex] ?? null);
            $date = $this->parseDate($row[$dateIndex] ?? null);
            if ($commodity === '' || $price === null || $date === null) {
                continue;
            }

            $unit = trim((string) ($unitIndex === null ? '' : ($row[$unitIndex] ?? '')));
            $market = trim((string) ($marketIndex === null ? '' : ($row[$marketIndex] ?? '')));
            $this->addRecord($records, $source, $commodity, $unit, $price, $date, $market, $defaultMarket);
        }

        return $records;
    }

    private function parseWideSp2kpFormat(array $headerRow, array $rows, ?string $defaultMarket = null): array
    {
        $headers = array_map(fn ($value) => $this->normalizeHeader($value), $headerRow);
        $nameIndex = $this->findCommodityHeaderIndex($headers);
        $unitIndex = $this->findHeaderIndex($headers, ['satuan', 'unit']);

        if ($nameIndex === null) {
            throw ValidationException::withMessages([
                'excel_file' => 'Kolom komoditas tidak ditemukan. Gunakan header Komoditas, Nama Komoditas, atau Nama Barang.',
            ]);
        }

        $dateColumns = [];
        foreach ($headerRow as $index => $value) {
            $date = $this->parseDate($value);
            if ($date !== null) {
                $dateColumns[$index] = $date;
            }
        }

        if ($dateColumns === []) {
            throw ValidationException::withMessages([
                'excel_file' => 'Kolom tanggal tidak dikenali. Gunakan format YYYY-MM-DD atau DD/MM/YYYY pada judul kolom harga.',
            ]);
        }

        $records = [];
        foreach ($rows as $row) {
            $commodity = trim((string) ($row[$nameIndex] ?? ''));
            if ($commodity === '') {
                continue;
            }

            foreach ($dateColumns as $index => $date) {
                $price = $this->parsePrice($row[$index] ?? null);
                if ($price === null) {
                    continue;
                }

                $unit = $unitIndex === null ? '' : trim((string) ($row[$unitIndex] ?? ''));
                $this->addRecord($records, 'sp2kp', $commodity, $unit, $price, $date, $defaultMarket ?? 'Situbondo');
            }
        }

        return $records;
    }

    private function addRecord(
        array &$records,
        string $source,
        string $commodity,
        string $unit,
        float $price,
        string $date,
        string $market,
        ?string $defaultMarket = null
    ): void
    {
        $market = $market !== '' ? $market : ($defaultMarket ?? ($source === 'sikeppo' ? 'Pasar Rakyat Panji' : 'Situbondo'));
        $key = implode('|', [$market, $commodity, $date]);
        $records[$key] = [
            'market_name' => $market,
            'commodity_name' => $commodity,
            'unit' => $unit,
            'price' => $price,
            'price_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function hasLongFormatColumns(array $headers): bool
    {
        return $this->findHeaderIndex($headers, ['harga', 'price']) !== null
            && $this->findHeaderIndex($headers, ['tanggal_dibuat', 'tanggal_data', 'tanggal', 'date']) !== null;
    }

    private function findCommodityHeaderIndex(array $headers): ?int
    {
        return $this->findHeaderIndex($headers, [
            'komoditas',
            'komoditi',
            'nama_komoditas',
            'nama_komoditi',
            'nama_barang',
            'commodity',
            'item',
            'variant',
        ]);
    }

    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        foreach ($headers as $index => $header) {
            if (in_array($header, $aliases, true)) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeHeader($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        return trim(preg_replace('/[^a-z0-9]+/', '_', $value), '_');
    }

    private function parseDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value >= 20000 && (float) $value <= 100000) {
            return SpreadsheetDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $value = trim((string) $value);
        foreach ([
            '!Y-m-d',
            '!Y/m/d',
            '!d/m/Y',
            '!d-m-Y',
            '!d.m.Y',
            '!d/m/y',
            '!d-m-y',
            '!d.m.y',
            '!m/d/Y',
            '!m-d-Y',
            '!j-M-Y',
            '!d-M-Y',
            '!j M Y',
            '!d M Y',
        ] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false && $date->format(substr($format, 1)) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function parsePrice($value): ?float
    {
        $rawValue = trim((string) $value);
        $isGroupedThousands = is_string($value)
            && preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $rawValue);
        if (is_numeric($value) && ! $isGroupedThousands) {
            $price = (float) $value;

            return $price > 0 ? $price : null;
        }

        $price = preg_replace('/[^\d.,-]/', '', $rawValue);
        if ($price === '') {
            return null;
        }

        if (str_contains($price, ',') && str_contains($price, '.')) {
            if (strrpos($price, ',') > strrpos($price, '.')) {
                $price = str_replace('.', '', $price);
                $price = str_replace(',', '.', $price);
            } else {
                $price = str_replace(',', '', $price);
            }
        } elseif (str_contains($price, ',')) {
            $price = preg_match('/^-?\d{1,3}(?:,\d{3})+$/', $price)
                ? str_replace(',', '', $price)
                : str_replace(',', '.', $price);
        } elseif (preg_match('/\.\d{3}$/', $price)) {
            $price = str_replace('.', '', $price);
        }

        $price = is_numeric($price) ? (float) $price : 0;

        return $price > 0 ? $price : null;
    }
}
