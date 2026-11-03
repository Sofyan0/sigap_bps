<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use Illuminate\Http\Request;

class KelolaDataController extends Controller
{
    public function index()
    {
        $commodities = Commodity::orderBy('id', 'desc')->get();
        return view('kelola-data', compact('commodities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun' => 'required|numeric',
            'kode_kom' => 'required|string',
            'nama_kom' => 'required|string',
        ]);

        Commodity::create($request->all());

        return redirect()->back()->with('success', 'Data komoditas berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $commodity = Commodity::findOrFail($id);
        $commodity->update($request->all());

        return redirect()->back()->with('success', 'Data komoditas ' . $commodity->nama_kom . ' berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $commodity = Commodity::findOrFail($id);
        $commodity->delete();

        return redirect()->back()->with('success', 'Data komoditas berhasil dihapus!');
    }

    public function reset()
    {
        Commodity::truncate();
        return redirect()->back()->with('success', 'Seluruh data komoditas berhasil dikosongkan.');
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:csv,txt|max:4096'
        ], [
            'excel_file.mimes' => 'Format berkas harus .csv! Jika berkas Anda .xlsx, Save As ke CSV terlebih dahulu.'
        ]);

        $file = $request->file('excel_file');
        $handle = fopen($file->getRealPath(), "r");
        $header = null;
        $count = 0;

        while (($row = fgetcsv($handle, 4096, ",")) !== FALSE) {
            // Skip baris header pertama
            if (!$header) {
                $header = $row;
                continue;
            }

            // Sanitasi tahun
            $rawTahun = preg_replace('/[^0-9]/', '', $row[0] ?? '');
            $tahun = (!empty($rawTahun) && strlen($rawTahun) === 4) ? (int)$rawTahun : (int)date('Y');

            $kodeKom = isset($row[7]) ? trim($row[7]) : '';
            if (empty($kodeKom)) {
                continue;
            }

            Commodity::updateOrCreate(
                [
                    'tahun' => $tahun,
                    'kode_kom' => $kodeKom,
                    'nama_kec' => trim($row[6] ?? ''),
                ],
                [
                    'id_dok' => trim($row[1] ?? ''),
                    'kuesioner' => trim($row[2] ?? ''),
                    'prov' => trim($row[3] ?? '35'),
                    'kab' => trim($row[4] ?? '12'),
                    'kec' => trim($row[5] ?? ''),
                    'nama_kom' => trim($row[8] ?? ''),
                    'status_kom' => trim($row[9] ?? ''),
                    'kode_kualitas' => trim($row[10] ?? ''),
                    'nama_kualitas' => trim($row[11] ?? ''),
                    'satuan' => trim($row[12] ?? 'kg'),
                    'status_kualitas' => trim($row[13] ?? ''),
                    'januari_prev' => (float)preg_replace('/[^0-9.]/', '', $row[14] ?? 0),
                    'januari' => (float)preg_replace('/[^0-9.]/', '', $row[15] ?? 0),
                    'februari_prev' => (float)preg_replace('/[^0-9.]/', '', $row[16] ?? 0),
                    'februari' => (float)preg_replace('/[^0-9.]/', '', $row[17] ?? 0),
                    'maret_prev' => (float)preg_replace('/[^0-9.]/', '', $row[18] ?? 0),
                    'maret' => (float)preg_replace('/[^0-9.]/', '', $row[19] ?? 0),
                    'april_prev' => (float)preg_replace('/[^0-9.]/', '', $row[20] ?? 0),
                    'april' => (float)preg_replace('/[^0-9.]/', '', $row[21] ?? 0),
                    'mei_prev' => (float)preg_replace('/[^0-9.]/', '', $row[22] ?? 0),
                    'mei' => (float)preg_replace('/[^0-9.]/', '', $row[23] ?? 0),
                    'juni_prev' => (float)preg_replace('/[^0-9.]/', '', $row[24] ?? 0),
                    'juni' => (float)preg_replace('/[^0-9.]/', '', $row[25] ?? 0),
                    'juli_prev' => (float)preg_replace('/[^0-9.]/', '', $row[26] ?? 0),
                    'juli' => (float)preg_replace('/[^0-9.]/', '', $row[27] ?? 0),
                    'agustus_prev' => (float)preg_replace('/[^0-9.]/', '', $row[28] ?? 0),
                    'agustus' => (float)preg_replace('/[^0-9.]/', '', $row[29] ?? 0),
                    'september_prev' => (float)preg_replace('/[^0-9.]/', '', $row[30] ?? 0),
                    'september' => (float)preg_replace('/[^0-9.]/', '', $row[31] ?? 0),
                    'oktober_prev' => (float)preg_replace('/[^0-9.]/', '', $row[32] ?? 0),
                    'oktober' => (float)preg_replace('/[^0-9.]/', '', $row[33] ?? 0),
                    'november_prev' => (float)preg_replace('/[^0-9.]/', '', $row[34] ?? 0),
                    'november' => (float)preg_replace('/[^0-9.]/', '', $row[35] ?? 0),
                    'desember_prev' => (float)preg_replace('/[^0-9.]/', '', $row[36] ?? 0),
                    'desember' => (float)preg_replace('/[^0-9.]/', '', $row[37] ?? 0),
                ]
            );
            $count++;
        }
        fclose($handle);

        return redirect()->back()->with('success', $count . ' baris data komoditas berhasil diimpor ke MySQL!');
    }
}