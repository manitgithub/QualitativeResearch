<?php

/**
 * ตารางประเด็นสังเคราะห์ Input / Process / Output ของคำถาม 1 ข้อ
 * @var app\models\Interpretation[] $themes
 */

use app\models\Interpretation;
use yii\helpers\Html;

$cols = Interpretation::ipoOptions();
$by = [];
foreach ($themes as $t) {
    $by[$t->ipo ?: ''][] = $t->text;
}
$other = $by[''] ?? [];
?>
<table class="book-table" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%">
    <tr><?php foreach ($cols as $label): ?><th style="width:33%"><?= $label ?></th><?php endforeach ?></tr>
    <tr>
        <?php foreach (array_keys($cols) as $k): ?>
            <td style="vertical-align:top">
                <?php foreach ($by[$k] ?? [] as $i => $text): ?>
                    <div><?= $i + 1 ?>. <?= Html::encode($text) ?></div>
                <?php endforeach ?>
            </td>
        <?php endforeach ?>
    </tr>
    <?php if ($other): ?>
        <tr><td colspan="3"><b>ไม่ระบุหมวด:</b> <?= Html::encode(implode(' · ', $other)) ?></td></tr>
    <?php endif ?>
</table>
