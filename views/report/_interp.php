<?php

/**
 * ประเด็น 1 รายการ พร้อมฟอร์มแก้ไขแบบ inline
 *
 * @var yii\web\View $this
 * @var app\models\Interpretation $it
 * @var bool $canEdit
 */

use app\models\Interpretation;
use yii\helpers\Html;

$editId = 'edit-i' . $it->id;
?>
<div class="interp-item round-<?= $it->round ?>">
    <div class="d-flex justify-content-between gap-2">
        <div>
            <?= Html::encode($it->text) ?>
            <?php if ($it->ipo): ?><span class="badge text-bg-secondary ipo-badge"><?= $it->ipo ?></span><?php endif ?>
        </div>
        <?php if ($canEdit): ?>
            <div class="actions text-nowrap no-print">
                <a href="#" class="text-secondary" data-toggle-target="#<?= $editId ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
                <?= Html::a('<i class="bi bi-trash"></i>', ['interpretation/delete', 'id' => $it->id], [
                    'class' => 'text-danger ms-1', 'title' => 'ลบ',
                    'data' => ['method' => 'post', 'confirm' => 'ลบประเด็นนี้?'],
                ]) ?>
            </div>
        <?php endif ?>
    </div>
    <?php if ($canEdit): ?>
        <?= Html::beginForm(['interpretation/update', 'id' => $it->id], 'post', ['id' => $editId, 'class' => 'd-none mt-2']) ?>
        <textarea name="Interpretation[text]" class="form-control form-control-sm mb-1" rows="2" data-autosize><?= Html::encode($it->text) ?></textarea>
        <div class="d-flex gap-1">
            <?= Html::dropDownList('Interpretation[ipo]', $it->ipo, Interpretation::ipoOptions(), ['class' => 'form-select form-select-sm', 'prompt' => '— IPO —']) ?>
            <button class="btn btn-sm btn-primary">บันทึก</button>
        </div>
        <?= Html::endForm() ?>
    <?php endif ?>
</div>
