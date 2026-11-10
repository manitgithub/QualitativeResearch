<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Concept $model */
/** @var app\models\Concept[] $concepts */
/** @var bool $canEdit */
/** @var app\models\Concept[] $pending */
/** @var bool $aiReady */

use app\models\Concept;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Conceptual map';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$flags = Concept::flagColumns();
$labels = (new Concept())->attributeLabels();
$canEdit = $canEdit && !$project->is_archived;
$aiReady = $aiReady && $canEdit;
?>
<div class="page-header">
    <h1><?= $this->title ?></h1>
    <?php if ($aiReady && !$pending): ?>
        <?= Html::a('<i class="bi bi-stars"></i> AI ช่วย', ['ai', 'projectId' => $project->id], [
            'class' => 'btn btn-outline-primary ai-concept-btn',
            'title' => 'ให้ AI เสนอแนวคิดจากประเด็นสังเคราะห์ (IPO) ของทุกคำถาม (ใช้เครดิต)',
            'data' => ['method' => 'post', 'confirm' => 'ให้ AI เสนอแนวคิดสำหรับ Conceptual map จากประเด็นของทุกคำถามการวิจัย? (ใช้เครดิตของคุณ)'],
        ]) ?>
    <?php endif ?>
</div>

<?php if ($canEdit && $pending): ?>
    <div class="card mb-3 border-primary-subtle">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div><i class="bi bi-stars text-primary"></i> AI เสนอ <?= count($pending) ?> แนวคิด — ตรวจแล้วกดรับหรือปฏิเสธ</div>
            <?= Html::a('รับทั้งหมด', ['accept-all', 'projectId' => $project->id], [
                'class' => 'btn btn-sm btn-success', 'data' => ['method' => 'post', 'confirm' => 'รับแนวคิดจาก AI ทั้ง ' . count($pending) . ' รายการ?'],
            ]) ?>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($pending as $c): ?>
                <li class="list-group-item ai-suggestion-concept">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <b><?= Html::encode($c->name) ?></b>
                            <?php foreach ($flags as $f): ?>
                                <?php if ($c->$f): ?><span class="badge text-bg-secondary ms-1"><?= $labels[$f] ?></span><?php endif ?>
                            <?php endforeach ?>
                            <?php if ($m = $c->getMemberList()): ?>
                                <div class="small text-muted mt-1">ที่มา <?= count($m) ?> ประเด็น: <?= Html::encode(implode(' · ', $m)) ?></div>
                            <?php endif ?>
                        </div>
                        <div class="text-nowrap">
                            <?= Html::a('<i class="bi bi-check-lg"></i>', ['accept', 'id' => $c->id], ['class' => 'btn btn-sm btn-outline-success', 'title' => 'รับ', 'data-method' => 'post']) ?>
                            <?= Html::a('<i class="bi bi-x-lg"></i>', ['reject', 'id' => $c->id], ['class' => 'btn btn-sm btn-outline-danger', 'title' => 'ปฏิเสธ', 'data-method' => 'post']) ?>
                        </div>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<?php if ($canEdit): ?>
    <div class="card mb-3">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'name')->textarea(['rows' => 2, 'data-autosize' => true]) ?>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <?php foreach ($flags as $f): ?>
                    <?= $form->field($model, $f, ['options' => ['class' => 'mb-0']])->checkbox() ?>
                <?php endforeach ?>
            </div>
            <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่ม</button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
<?php endif ?>

<?php if ($canEdit): foreach ($concepts as $c): ?>
    <?= Html::beginForm(['update', 'id' => $c->id], 'post', ['id' => 'cf' . $c->id]) ?><?= Html::endForm() ?>
<?php endforeach; endif ?>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>แนวคิด/องค์ประกอบ</th>
                <?php foreach ($flags as $f): ?><th class="text-center small"><?= $labels[$f] ?></th><?php endforeach ?>
                <?php if ($canEdit): ?><th></th><?php endif ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($concepts as $c): ?>
                <tr>
                    <?php if ($canEdit): ?>
                        <td><textarea form="cf<?= $c->id ?>" name="Concept[name]" class="form-control form-control-sm" rows="1" data-autosize><?= Html::encode($c->name) ?></textarea></td>
                        <?php foreach ($flags as $f): ?>
                            <td class="text-center"><input form="cf<?= $c->id ?>" type="checkbox" class="form-check-input" name="Concept[<?= $f ?>]" value="1" <?= $c->$f ? 'checked' : '' ?> onchange="this.form.submit()"></td>
                        <?php endforeach ?>
                        <td class="text-end text-nowrap">
                            <button form="cf<?= $c->id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg"></i></button>
                            <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $c->id], [
                                'class' => 'btn btn-sm btn-outline-danger', 'data' => ['method' => 'post', 'confirm' => 'ลบรายการนี้?'],
                            ]) ?>
                        </td>
                    <?php else: ?>
                        <td><?= Html::encode($c->name) ?><?php if ($c->source !== 'human'): ?> <i class="bi bi-stars text-primary small" title="แนวคิดจาก AI"></i><?php endif ?></td>
                        <?php foreach ($flags as $f): ?><td class="text-center"><?= $c->$f ? '<i class="bi bi-check-lg text-primary"></i>' : '' ?></td><?php endforeach ?>
                    <?php endif ?>
                </tr>
            <?php endforeach ?>
            <?php if (!$concepts): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
