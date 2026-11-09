<?php

/**
 * ฟอร์มกลางสำหรับเพิ่ม/แก้/ลบประเด็น — มีชุดเดียวต่อหน้า JS (web/js/app.js) จะคัดลอกไปวางในเซลล์ที่กด
 *
 * @var yii\web\View $this
 */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<template id="interp-form-tpl">
    <?= Html::beginForm(['/interpretation/create'], 'post', ['class' => 'mt-1 no-print interp-form']) ?>
    <input type="hidden" name="Interpretation[response_id]">
    <input type="hidden" name="Interpretation[round]">
    <input type="hidden" name="returnUrl">
    <textarea name="Interpretation[text]" class="form-control form-control-sm mb-1" rows="2" data-autosize placeholder="ประเด็น (Ctrl+Enter เพื่อบันทึก)"></textarea>
    <div class="d-flex gap-1">
        <?= Html::dropDownList('Interpretation[ipo]', null, Interpretation::ipoOptions(), ['class' => 'form-select form-select-sm', 'prompt' => '— IPO —']) ?>
        <button class="btn btn-sm btn-primary">บันทึก</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-interp-cancel>ยกเลิก</button>
    </div>
    <?= Html::endForm() ?>
</template>
<?= Html::beginForm(['/interpretation/delete', 'id' => 0], 'post', ['id' => 'interp-delete-form', 'class' => 'd-none']) ?>
<input type="hidden" name="returnUrl">
<?= Html::endForm() ?>
<script>
    window.interpUrls = {
        create: <?= json_encode(Url::to(['/interpretation/create'])) ?>,
        update: <?= json_encode(Url::to(['/interpretation/update', 'id' => '__id__'])) ?>,
        remove: <?= json_encode(Url::to(['/interpretation/delete', 'id' => '__id__'])) ?>
    };
</script>
