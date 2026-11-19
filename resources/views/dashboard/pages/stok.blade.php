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
