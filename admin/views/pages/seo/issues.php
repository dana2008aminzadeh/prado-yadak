<?php use Admin\core\Auth; ?>

<div class="card mb">
    <div class="card-body">
        <div class="flex gap wrap">
            <?php foreach ($types as $key => [$lbl, $h]): ?>
                <a class="btn btn-sm <?= $type === $key ? 'btn-primary' : '' ?>"
                   href="<?= admin_url('seo/issues', ['type' => $key]) ?>"><?= e($lbl) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($hint): ?>
            <div class="hint mt"><?= e($hint) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><?= e($label) ?> — <?= money($pg['total']) ?> مورد</h3>
        <a class="btn btn-sm" href="<?= admin_url('seo/export', ['type' => $type]) ?>">
            <i data-lucide="download" style="width:13px"></i> خروجی CSV
        </a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>نام قطعه</th><th>کد فنی</th><th>عکس</th>
                    <th>طول توضیحات</th><th>امتیاز سئو</th><th>موجودی</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="mono"><?= (int) $r['id'] ?></td>
                        <td style="max-width:300px"><?= e(excerpt($r['name'], 60)) ?></td>
                        <td class="mono"><?= $r['oem_code'] ? e($r['oem_code']) : '<span class="badge b-red">ندارد</span>' ?></td>
                        <td><?= (int) $r['images'] > 0 ? fa($r['images']) : '<span class="badge b-red">۰</span>' ?></td>
                        <td>
                            <span class="badge <?= (int) $r['desc_len'] >= 300 ? 'b-green' : ((int) $r['desc_len'] >= 100 ? 'b-amber' : 'b-red') ?>">
                                <?= fa((int) $r['desc_len']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= (int) $r['seo_score'] >= 80 ? 'b-green' : ((int) $r['seo_score'] >= 50 ? 'b-amber' : 'b-red') ?>">
                                <?= fa((int) $r['seo_score']) ?>
                            </span>
                        </td>
                        <td><?= (int) $r['in_stock'] === 1 ? '<span class="badge b-green">موجود</span>' : '<span class="badge b-gray">ناموجود</span>' ?></td>
                        <td class="flex gap">
                            <a class="btn btn-sm btn-primary" href="<?= admin_url('products/edit/' . (int) $r['id']) ?>">اصلاح</a>
                            <a class="btn btn-sm" target="_blank" rel="noopener" href="/product/<?= e($r['slug']) ?>">مشاهده</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="empty">هیچ موردی در این دسته وجود ندارد. 👌</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
