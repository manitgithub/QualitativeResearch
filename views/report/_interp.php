<?php

/**
 * ประเด็น 1 รายการ (ฟอร์มแก้ไขสร้างด้วย JS จาก _interp_templates เมื่อกดแก้ไข เพื่อให้หน้าที่มีข้อมูลมากเบา)
 *
 * @var yii\web\View $this
 * @var app\models\Interpretation $it
 * @var bool $canEdit
 */

use yii\helpers\Html;
?>
<div class="interp-item round-<?= $it->round ?>">
    <div class="d-flex justify-content-between gap-2">
        <div>
            <?= Html::encode($it->text) ?>
            <?php if ($it->isFromAi()): ?><i class="bi bi-stars text-primary small" title="<?= $it->source === 'ai' ? 'ประเด็นจาก AI' : 'ประเด็นจาก AI (นักวิจัยแก้ไขแล้ว)' ?>"></i><?php endif ?>
            <?php if ($it->ipo): ?><span class="badge text-bg-secondary ipo-badge"><?= $it->ipo ?></span><?php endif ?>
        </div>
        <?php if ($canEdit): ?>
            <div class="actions text-nowrap no-print">
                <a href="#" class="text-secondary" title="แก้ไข"
                   data-interp-edit="<?= $it->id ?>" data-text="<?= Html::encode($it->text) ?>" data-ipo="<?= Html::encode($it->ipo) ?>"><i class="bi bi-pencil"></i></a>
                <a href="#" class="text-danger ms-1" title="ลบ" data-interp-delete="<?= $it->id ?>"><i class="bi bi-trash"></i></a>
            </div>
        <?php endif ?>
    </div>
</div>
