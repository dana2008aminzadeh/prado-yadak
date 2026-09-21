<?php
/** @var array $pg */
if (empty($pg) || $pg['pages'] <= 1) return;
$qs = $_GET;
$cur = $pg['page'];
$make = function (int $p) use ($qs) {
    $qs['page'] = $p;
    return '?' . http_build_query($qs);
};
$start = max(1, $cur - 2);
$end = min($pg['pages'], $cur + 2);
?>
<div class="pagination">
    <?php if ($cur > 1): ?>
        <a href="<?= e($make(1)) ?>">اول</a>
        <a href="<?= e($make($cur - 1)) ?>">‹</a>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $cur): ?>
            <span class="cur"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($make($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($cur < $pg['pages']): ?>
        <a href="<?= e($make($cur + 1)) ?>">›</a>
        <a href="<?= e($make($pg['pages'])) ?>">آخر</a>
    <?php endif; ?>
</div>
