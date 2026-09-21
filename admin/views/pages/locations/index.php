<?php use Admin\core\Auth; ?>

<div class="grid g2" style="grid-template-columns:1fr 1.6fr;align-items:start">
    <div>
        <form class="card mb" method="POST" action="<?= admin_url('locations/saveProvince') ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>افزودن استان</h3></div>
            <div class="card-body">
                <div class="grid g2">
                    <div class="field"><label class="fl">نام استان *</label><input type="text" name="name" required></div>
                    <div class="field"><label class="fl">اسلاگ</label><input type="text" name="slug" class="mono" placeholder="خودکار"></div>
                </div>
                <label class="flex items-center gap" style="margin-bottom:12px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" checked style="width:auto"><span style="font-size:13px;font-weight:700">فعال</span></label>
                <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره استان</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>استان‌ها (<?= count($provinces) ?>)</h3></div>
            <div class="table-wrap" style="max-height:520px;overflow-y:auto">
                <table>
                    <thead><tr><th>نام</th><th>شهرها</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($provinces as $p): ?>
                            <tr>
                                <td><b><?= e($p['name']) ?></b></td>
                                <td><a href="<?= admin_url('locations', ['province' => $p['id']]) ?>"><?= (int) $p['cities_count'] ?></a></td>
                                <td><span class="badge <?= $p['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $p['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('locations/toggleProvince/' . $p['id']) ?>">تغییر</a>
                                        <a class="btn btn-sm btn-danger" href="<?= admin_url('locations/deleteProvince/' . $p['id']) ?>" onclick="return confirmDelete('استان و همه شهرهایش حذف شود؟')">حذف</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <form class="card mb" method="POST" action="<?= admin_url('locations/saveCity') ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>افزودن شهر</h3></div>
            <div class="card-body">
                <div class="grid g3">
                    <div class="field"><label class="fl">استان *</label>
                        <select name="province_id" required>
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= $provinceId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">نام شهر *</label><input type="text" name="name" required></div>
                    <div class="field"><label class="fl">اسلاگ</label><input type="text" name="slug" class="mono" placeholder="خودکار"></div>
                </div>
                <label class="flex items-center gap" style="margin-bottom:12px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" checked style="width:auto"><span style="font-size:13px;font-weight:700">فعال</span></label>
                <button class="btn btn-primary" type="submit">ذخیره شهر</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head">
                <h3>شهرها (<?= money($pg['total']) ?>)</h3>
                <form method="GET" action="<?= admin_url('locations') ?>" class="flex gap">
                    <input type="hidden" name="province" value="<?= (int) $provinceId ?>">
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجوی شهر" style="width:180px">
                    <button class="btn btn-sm" type="submit">جستجو</button>
                    <a class="btn btn-sm" href="<?= admin_url('locations') ?>">همه</a>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>شهر</th><th>استان</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!$cities): ?><tr><td colspan="4" class="empty">شهری یافت نشد.</td></tr><?php endif; ?>
                        <?php foreach ($cities as $c): ?>
                            <tr>
                                <td><b><?= e($c['name']) ?></b><div class="hint mono"><?= e($c['slug']) ?></div></td>
                                <td class="hint"><?= e($c['province_name'] ?? '—') ?></td>
                                <td><span class="badge <?= $c['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $c['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('locations/toggleCity/' . $c['id']) ?>">تغییر</a>
                                        <a class="btn btn-sm btn-danger" href="<?= admin_url('locations/deleteCity/' . $c['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
        </div>
    </div>
</div>
