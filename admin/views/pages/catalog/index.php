<?php
use Admin\core\Auth;
$ec = $editCat ?: ['id' => 0, 'name' => '', 'slug' => '', 'description' => '', 'tags' => '', 'icon_svg' => '', 'parent_id' => null];
$em = $editModel ?: ['id' => 0, 'name' => '', 'slug' => '', 'logo_svg' => ''];
?>

<div class="grid g2" style="align-items:start">
    <!-- دسته‌بندی‌ها -->
    <div>
        <form class="card mb" method="POST" action="<?= admin_url('catalog/saveCategory') ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <input type="hidden" name="id" value="<?= (int) $ec['id'] ?>">
            <div class="card-head"><h3><?= $ec['id'] ? 'ویرایش دسته' : 'دسته‌بندی جدید' ?></h3>
                <?php if ($ec['id']): ?><a class="btn btn-sm" href="<?= admin_url('catalog') ?>">جدید</a><?php endif; ?></div>
            <div class="card-body">
                <div class="grid g2">
                    <div class="field"><label class="fl">نام دسته *</label><input type="text" name="name" value="<?= e($ec['name']) ?>" required></div>
                    <div class="field"><label class="fl">اسلاگ</label><input type="text" name="slug" class="mono" value="<?= e($ec['slug']) ?>" placeholder="خودکار"></div>
                </div>
                <div class="field"><label class="fl">دسته والد</label>
                    <select name="parent_id">
                        <option value="">— دسته اصلی —</option>
                        <?php foreach ($categories as $cc): if ((int) $cc['id'] === (int) $ec['id']) continue; ?>
                            <option value="<?= (int) $cc['id'] ?>" <?= (int) $ec['parent_id'] === (int) $cc['id'] ? 'selected' : '' ?>><?= e($cc['name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field"><label class="fl">توضیحات</label><textarea name="description" rows="2"><?= e($ec['description']) ?></textarea></div>
                <div class="field"><label class="fl">برچسب‌ها (با کاما)</label><input type="text" name="tags" value="<?= e($ec['tags']) ?>"></div>
                <div class="field"><label class="fl">آیکون SVG</label><textarea name="icon_svg" rows="2" style="font-family:ui-monospace,monospace;font-size:11px"><?= e($ec['icon_svg']) ?></textarea></div>
                <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره دسته</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>دسته‌بندی‌ها (<?= count($categories) ?>)</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>نام</th><th>والد</th><th>محصولات</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!$categories): ?><tr><td colspan="4" class="empty">دسته‌ای ثبت نشده.</td></tr><?php endif; ?>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td><?= $c['parent_id'] ? '<span class="hint">↳ </span>' : '' ?><b><?= e($c['name']) ?></b>
                                    <div class="hint mono"><?= e($c['slug']) ?></div></td>
                                <td class="hint"><?= e($c['parent_name'] ?? '—') ?></td>
                                <td><a href="<?= admin_url('products', ['category' => $c['id']]) ?>"><?= (int) $c['products_count'] ?></a></td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('catalog', ['cat' => $c['id']]) ?>">ویرایش</a>
                                        <a class="btn btn-sm btn-danger" href="<?= admin_url('catalog/deleteCategory/' . $c['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- مدل‌های خودرو -->
    <div>
        <form class="card mb" method="POST" action="<?= admin_url('catalog/saveModel') ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <input type="hidden" name="id" value="<?= (int) $em['id'] ?>">
            <div class="card-head"><h3><?= $em['id'] ? 'ویرایش مدل خودرو' : 'مدل خودرو جدید' ?></h3>
                <?php if ($em['id']): ?><a class="btn btn-sm" href="<?= admin_url('catalog') ?>">جدید</a><?php endif; ?></div>
            <div class="card-body">
                <div class="grid g2">
                    <div class="field"><label class="fl">نام مدل *</label><input type="text" name="name" value="<?= e($em['name']) ?>" placeholder="پرادو" required></div>
                    <div class="field"><label class="fl">اسلاگ</label><input type="text" name="slug" class="mono" value="<?= e($em['slug']) ?>" placeholder="prado"></div>
                </div>
                <div class="field"><label class="fl">لوگو SVG</label><textarea name="logo_svg" rows="3" style="font-family:ui-monospace,monospace;font-size:11px"><?= e($em['logo_svg']) ?></textarea></div>
                <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره مدل</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>مدل‌های خودرو (<?= count($carModels) ?>)</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>نام</th><th>اسلاگ</th><th>محصولات</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!$carModels): ?><tr><td colspan="4" class="empty">مدلی ثبت نشده.</td></tr><?php endif; ?>
                        <?php foreach ($carModels as $m): ?>
                            <tr>
                                <td><b><?= e($m['name']) ?></b></td>
                                <td class="mono hint"><?= e($m['slug']) ?></td>
                                <td><a href="<?= admin_url('products', ['car_model' => $m['slug']]) ?>"><?= (int) $m['products_count'] ?></a></td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('catalog', ['model' => $m['id']]) ?>">ویرایش</a>
                                        <a class="btn btn-sm btn-danger" href="<?= admin_url('catalog/deleteModel/' . $m['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
