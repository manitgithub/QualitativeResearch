<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Concept $model */
/** @var app\models\Concept[] $concepts */
/** @var bool $canEdit */

use app\models\Concept;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Conceptual map';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$flags = Concept::flagColumns();
$labels = (new Concept())->attributeLabels();
$canEdit = $canEdit && !$project->is_archived;
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

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
                        <td><?= Html::encode($c->name) ?></td>
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
