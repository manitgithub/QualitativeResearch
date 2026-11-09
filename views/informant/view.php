<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Informant $informant */
/** @var app\models\Response[] $responses */
/** @var int|null $prevId */
/** @var int|null $nextId */
/** @var bool $canEdit */

use app\models\Interpretation;
use app\models\Question;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ผู้ให้ข้อมูล ' . $informant->code;
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = ['label' => 'ผู้ให้ข้อมูล', 'url' => ['index', 'projectId' => $project->id]];
$this->params['breadcrumbs'][] = $informant->code;
$rounds = Interpretation::roundOptions();
?>
<div class="page-header">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="small text-muted mt-1">
            <?php foreach (array_filter([
                $informant->age ? 'อายุ ' . $informant->age . ' ปี' : null,
                $informant->group->name ?? null,
                $informant->collectionMethod->name ?? null,
                $informant->interviewed_on ? Yii::$app->formatter->asDate($informant->interviewed_on, 'long') : null,
            ]) as $i => $t): ?>
                <?= $i ? ' · ' : '' ?><?= Html::encode($t) ?>
            <?php endforeach ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group">
            <a class="btn btn-outline-secondary <?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? Url::to(['view', 'id' => $prevId]) : '#' ?>"><i class="bi bi-chevron-left"></i></a>
            <a class="btn btn-outline-secondary <?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? Url::to(['view', 'id' => $nextId]) : '#' ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
        <?php if ($canEdit): ?>
            <a href="<?= Url::to(['update', 'id' => $informant->id]) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> แก้ไขคำตอบ</a>
            <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $informant->id], [
                'class' => 'btn btn-outline-danger', 'title' => 'ลบ',
                'data' => ['method' => 'post', 'confirm' => 'ลบผู้ให้ข้อมูล ' . $informant->code . ' และคำตอบทั้งหมด?'],
            ]) ?>
        <?php endif ?>
    </div>
</div>

<?php if ($informant->note): ?>
    <div class="alert alert-light border" style="white-space: pre-wrap"><?= Html::encode($informant->note) ?></div>
<?php endif ?>

<?php if (!$responses): ?>
    <div class="card"><div class="card-body text-muted text-center py-4">ยังไม่มีคำตอบ</div></div>
<?php endif ?>

<?php foreach ($responses as $r): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between gap-2">
                <div class="fw-semibold mb-2"><?= Html::encode($r->question->title) ?></div>
                <?php if ($r->question->category === Question::CATEGORY_RESEARCH): ?>
                    <a href="<?= Url::to(['report/question', 'projectId' => $project->id, 'questionId' => $r->question_id, '#' => 'r' . $r->id]) ?>" class="small text-nowrap">วิเคราะห์ →</a>
                <?php endif ?>
            </div>
            <div style="white-space: pre-wrap"><?= Html::encode($r->getDisplayValue()) ?></div>
            <?php if ($r->interpretations): ?>
                <div class="mt-3">
                    <?php foreach ($r->interpretations as $it): ?>
                        <div class="interp-item round-<?= $it->round ?>">
                            <span class="badge text-bg-light"><?= $rounds[$it->round] ?? $it->round ?></span>
                            <?= Html::encode($it->text) ?>
                            <?php if ($it->ipo): ?><span class="badge text-bg-secondary ipo-badge"><?= $it->ipo ?></span><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
<?php endforeach ?>
