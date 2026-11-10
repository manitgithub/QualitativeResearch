<?php

/**
 * ยอดเครดิตคงเหลือและประวัติล่าสุด
 *
 * @var yii\web\View $this
 * @var int $balance
 * @var app\models\CreditLedger[] $ledger
 */

use app\components\ai\Pricing;
use app\models\AiJob;
use app\models\CreditLedger;
use yii\helpers\Html;

$kinds = CreditLedger::kindLabels();
?>
<div class="card-body border-bottom d-flex align-items-baseline gap-2">
    <span class="fs-3 fw-semibold"><?= Yii::$app->formatter->asInteger($balance) ?></span>
    <span class="text-muted">เครดิต</span>
    <span class="small text-muted ms-auto">1 เครดิต = <?= Yii::$app->formatter->asDecimal(Pricing::bahtPerCredit(), 2) ?> บาท</span>
</div>
<ul class="list-group list-group-flush small">
    <?php foreach ($ledger as $l): ?>
        <li class="list-group-item d-flex justify-content-between gap-2">
            <span>
                <?= Yii::$app->formatter->asDatetime($l->created_at, 'short') ?> ·
                <?= $kinds[$l->kind] ?? Html::encode($l->kind) ?>
                <?php if ($l->job): ?>
                    <span class="text-muted">(<?= AiJob::featureLabels()[$l->job->feature] ?? Html::encode($l->job->feature) ?>, <?= (int)$l->job->item_count ?> คำตอบ)</span>
                <?php endif ?>
                <?php if ($l->kind === CreditLedger::KIND_GRANT && $l->note): ?>
                    <span class="text-muted">— <?= Html::encode($l->note) ?></span>
                <?php endif ?>
            </span>
            <span class="text-nowrap">
                <b class="<?= $l->delta > 0 ? 'text-success' : 'text-danger' ?>"><?= $l->delta > 0 ? '+' : '' ?><?= $l->delta ?></b>
                <span class="text-muted">→ <?= $l->balance_after ?></span>
            </span>
        </li>
    <?php endforeach ?>
    <?php if (!$ledger): ?><li class="list-group-item text-muted">ยังไม่มีรายการ</li><?php endif ?>
</ul>
