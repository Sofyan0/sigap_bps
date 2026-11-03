<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Komoditas - SIGAP SEKARKIJANG</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen">

    <header class="bg-slate-900/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-3.5">
                <a href="{{ route('dashboard') }}" class="bg-amber-500 text-slate-950 p-2.5 rounded-2xl font-extrabold text-xl tracking-wider shadow-lg shadow-amber-500/20">
                    SIGAP
                </a>
                <div>
                    <h1 class="font-extrabold text-lg leading-tight tracking-wide text-white">Kelola Data SIGAP SEKARKIJANG</h1>
                    <p class="text-[11px] text-slate-400 font-medium">Manajemen Data Database MySQL & Import CSV SP2KP BPS (38 Kolom)</p>
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-house"></i> Kembali ke Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @if(session('success'))
            <div class="bg-emerald-600 text-white p-4 rounded-2xl shadow-xl flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                    <span class="text-xs font-bold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white font-bold">&times;</button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Side Panel Upload -->
            <div class="space-y-6">
                <div class="bg-slate-800/90 p-6 rounded-2xl border border-slate-700/80 shadow-xl space-y-4">
                    <div class="border-b border-slate-700 pb-3">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-file-csv text-emerald-400"></i> Import CSV SP2KP
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Unggah berkas CSV 38 kolom.</p>
                    </div>

                    <form method="POST" action="{{ route('kelola-data.upload-excel') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="excel_file" class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Berkas CSV:</label>
                            <input type="file" id="excel_file" name="excel_file" accept=".csv, .txt" required class="block w-full text-xs text-slate-400 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 cursor-pointer focus:outline-none">
                        </div>

                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-upload"></i> Upload & Import
                        </button>
                    </form>

                    <hr class="border-slate-700">

                    <form method="POST" action="{{ route('kelola-data.reset') }}" onsubmit="return confirm('Kosongkan tabel database?')">
                        @csrf
                        <button type="submit" class="w-full bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 text-xs font-bold py-2 rounded-xl transition">
                            <i class="fa-solid fa-trash-can mr-1"></i> Kosongkan Tabel
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabel Data 38 Kolom -->
            <div class="lg:col-span-3 bg-slate-800/90 rounded-2xl border border-slate-700/80 shadow-xl overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-700/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-database text-amber-400"></i> Tabel Data Komoditas (38 Kolom SP2KP)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Total Data: <span class="font-bold text-emerald-400">{{ count($commodities) }} Baris</span></p>
                    </div>

                    <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-md transition flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fa-solid fa-plus"></i> Tambah Data Manual
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-900/90 text-slate-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-700">
                                <th class="py-3 px-4 border-r border-slate-700 text-center">Aksi</th>
                                <th class="py-3 px-3">Tahun</th>
                                <th class="py-3 px-3">Id Dok</th>
                                <th class="py-3 px-3">Kuesioner</th>
                                <th class="py-3 px-3">Prov</th>
                                <th class="py-3 px-3">Kab</th>
                                <th class="py-3 px-3">Kec</th>
                                <th class="py-3 px-3">Nama Kec</th>
                                <th class="py-3 px-3">Kode Kom</th>
                                <th class="py-3 px-3">Nama Kom</th>
                                <th class="py-3 px-3">Status Kom</th>
                                <th class="py-3 px-3">Kode Kualitas</th>
                                <th class="py-3 px-3">Nama Kualitas</th>
                                <th class="py-3 px-3">Satuan</th>
                                <th class="py-3 px-3">Status Kualitas</th>
                                <th class="py-3 px-3 text-right">Jan Prev</th>
                                <th class="py-3 px-3 text-right">Jan</th>
                                <th class="py-3 px-3 text-right">Feb Prev</th>
                                <th class="py-3 px-3 text-right">Feb</th>
                                <th class="py-3 px-3 text-right">Mar Prev</th>
                                <th class="py-3 px-3 text-right">Mar</th>
                                <th class="py-3 px-3 text-right">Apr Prev</th>
                                <th class="py-3 px-3 text-right">Apr</th>
                                <th class="py-3 px-3 text-right">Mei Prev</th>
                                <th class="py-3 px-3 text-right">Mei</th>
                                <th class="py-3 px-3 text-right">Jun Prev</th>
                                <th class="py-3 px-3 text-right">Jun</th>
                                <th class="py-3 px-3 text-right">Jul Prev</th>
                                <th class="py-3 px-3 text-right">Jul</th>
                                <th class="py-3 px-3 text-right">Agu Prev</th>
                                <th class="py-3 px-3 text-right">Agu</th>
                                <th class="py-3 px-3 text-right">Sep Prev</th>
                                <th class="py-3 px-3 text-right">Sep</th>
                                <th class="py-3 px-3 text-right">Okt Prev</th>
                                <th class="py-3 px-3 text-right">Okt</th>
                                <th class="py-3 px-3 text-right">Nov Prev</th>
                                <th class="py-3 px-3 text-right">Nov</th>
                                <th class="py-3 px-3 text-right">Des Prev</th>
                                <th class="py-3 px-3 text-right">Des</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50 text-xs">
                            @forelse ($commodities as $c)
                                <tr class="hover:bg-slate-700/40 transition">
                                    <td class="py-2.5 px-4 border-r border-slate-700 text-center whitespace-nowrap space-x-1">
                                        <!-- Tombol Edit -->
                                        <button onclick="openEditModal({{ json_encode($c) }})" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[11px] px-2.5 py-1 rounded-lg font-bold transition">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form method="POST" action="{{ route('kelola-data.destroy', $c->id) }}" class="inline" onsubmit="return confirm('Hapus baris data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 text-[11px] px-2.5 py-1 rounded-lg font-bold transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-slate-400">{{ $c->tahun }}</td>
                                    <td class="py-2.5 px-3 text-slate-300">{{ $c->id_dok ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-300">{{ $c->kuesioner ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->prov }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->kab }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->kec ?? '-' }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-200">{{ $c->nama_kec ?? '-' }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-amber-400">{{ $c->kode_kom }}</td>
                                    <td class="py-2.5 px-3 font-bold text-white">{{ $c->nama_kom }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->status_kom ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->kode_kualitas ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-300">{{ $c->nama_kualitas ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->satuan }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $c->status_kualitas ?? '-' }}</td>

                                    <!-- Data Bulanan Prev & Current -->
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->januari_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->januari, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->februari_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->februari, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->maret_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->maret, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->april_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->april, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->mei_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->mei, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->juni_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->juni, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->juli_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->juli, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->agustus_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->agustus, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->september_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->september, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->oktober_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-400">{{ number_format($c->oktober, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->november_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->november, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-400">{{ number_format($c->desember_prev, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-200">{{ number_format($c->desember, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="39" class="py-12 text-center text-slate-500">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                        Belum ada data komoditas di database. Silakan unggah berkas CSV.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

    <!-- Modal Form Tambah Data Manual -->
    <div id="modalAddData" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4 overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 w-full max-w-2xl rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-plus text-blue-400"></i> Tambah Komoditas Baru
                </h3>
                <button onclick="closeModal('modalAddData')" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('kelola-data.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Tahun:</label>
                        <input type="number" name="tahun" value="{{ date('Y') }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">ID Dokumen:</label>
                        <input type="text" name="id_dok" placeholder="4" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Kuesioner:</label>
                        <input type="text" name="kuesioner" placeholder="HD-2" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Provinsi:</label>
                        <input type="text" name="prov" value="35" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Kabupaten:</label>
                        <input type="text" name="kab" value="12" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Kecamatan (Kode):</label>
                        <input type="text" name="kec" placeholder="3512110" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Nama Kecamatan:</label>
                        <input type="text" name="nama_kec" placeholder="MANGARAN" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Kode Komoditas:</label>
                        <input type="text" name="kode_kom" placeholder="ZA701" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-amber-400">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Nama Komoditas:</label>
                        <input type="text" name="nama_kom" placeholder="Upah Penyemprotan/OPT" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Nama Kualitas:</label>
                        <input type="text" name="nama_kualitas" placeholder="Lokal / Standard" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Satuan:</label>
                        <input type="text" name="satuan" value="kg" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Harga Oktober (Rp):</label>
                        <input type="number" name="oktober" value="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-emerald-400">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-700">
                    <button type="button" onclick="closeModal('modalAddData')" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl text-xs font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Edit Data Lengkap -->
    <div id="modalEditData" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4 overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 w-full max-w-3xl rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-400"></i> Edit Data Komoditas
                </h3>
                <button onclick="closeModal('modalEditData')" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
            </div>

            <form id="formEditData" method="POST" class="space-y-4">
                @csrf
                
                <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Metadata Informasi</p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Tahun:</label>
                        <input type="number" id="edit_tahun" name="tahun" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Nama Kecamatan:</label>
                        <input type="text" id="edit_nama_kec" name="nama_kec" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Kode Komoditas:</label>
                        <input type="text" id="edit_kode_kom" name="kode_kom" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-amber-400">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Nama Komoditas:</label>
                        <input type="text" id="edit_nama_kom" name="nama_kom" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Kuesioner:</label>
                        <input type="text" id="edit_kuesioner" name="kuesioner" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Nama Kualitas:</label>
                        <input type="text" id="edit_nama_kualitas" name="nama_kualitas" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-400">Satuan:</label>
                        <input type="text" id="edit_satuan" name="satuan" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider pt-2 border-t border-slate-700/60">Data Harga Bulanan (Rp)</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-[11px] font-semibold text-slate-400">Januari Prev:</label>
                        <input type="number" id="edit_januari_prev" name="januari_prev" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-400">Januari:</label>
                        <input type="number" id="edit_januari" name="januari" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-400">Oktober Prev:</label>
                        <input type="number" id="edit_oktober_prev" name="oktober_prev" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-400">Oktober:</label>
                        <input type="number" id="edit_oktober" name="oktober" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs font-bold text-emerald-400">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-700">
                    <button type="button" onclick="closeModal('modalEditData')" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl text-xs font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl text-xs font-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalAddData').classList.remove('hidden');
        }

        function openEditModal(item) {
            document.getElementById('formEditData').action = '/kelola-data/update/' + item.id;
            
            document.getElementById('edit_tahun').value = item.tahun || '';
            document.getElementById('edit_nama_kec').value = item.nama_kec || '';
            document.getElementById('edit_kode_kom').value = item.kode_kom || '';
            document.getElementById('edit_nama_kom').value = item.nama_kom || '';
            document.getElementById('edit_kuesioner').value = item.kuesioner || '';
            document.getElementById('edit_nama_kualitas').value = item.nama_kualitas || '';
            document.getElementById('edit_satuan').value = item.satuan || '';
            
            document.getElementById('edit_januari_prev').value = item.januari_prev || 0;
            document.getElementById('edit_januari').value = item.januari || 0;
            document.getElementById('edit_oktober_prev').value = item.oktober_prev || 0;
            document.getElementById('edit_oktober').value = item.oktober || 0;

            document.getElementById('modalEditData').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>