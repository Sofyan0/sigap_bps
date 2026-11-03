<tbody class="divide-y divide-slate-100 text-sm">
    <?php if (count($commodities) > 0): ?>
        <?php foreach ($commodities as $commodity): ?>
            <?php $record = $latestPrices->get($commodity->id); ?>
            <tr class="hover:bg-slate-50 transition">
                <td class="py-3 px-4 font-mono text-slate-500"><?php echo e($commodity->code); ?></td>
                <td class="py-3 px-4 font-medium text-slate-900"><?php echo e($commodity->name); ?></td>
                <td class="py-3 px-4 text-slate-600"><?php echo e($commodity->category); ?></td>
                <td class="py-3 px-4 text-slate-600"><?php echo e($commodity->unit); ?></td>
                <td class="py-3 px-4 font-semibold text-right text-slate-900">
                    <?php if ($record): ?>
                        Rp <?php echo e(number_format($record->price, 0, ',', '.')); ?>

                    <?php else: ?>
                        <span class="text-slate-400 italic">Belum Ada Data</span>
                    <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-center">
                    <?php if ($record): ?>
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full <?php echo e($record->source === 'central' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'); ?>">
                            <?php echo e(ucfirst($record->source)); ?>

                        </span>
                    <?php else: ?>
                        <span class="text-slate-400">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="py-8 text-center text-slate-500">
                Belum ada data komoditas yang terdaftar.
            </td>
        </tr>
    <?php endif; ?>
</tbody>