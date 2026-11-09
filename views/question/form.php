<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Question $model */

use app\models\Question;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->isNewRecord ? 'เพิ่มคำถาม' : 'แก้ไขคำถาม';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = ['label' => 'คำถาม', 'url' => ['index', 'projectId' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<JS
var typeSel = document.getElementById('question-answer_type');
function toggleOptions() {
    document.getElementById('options-box').classList.toggle('d-none', typeSel.value !== 'choice');
}
typeSel.addEventListener('change', toggleOptions);
toggleOptions();
JS);
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<div class="card" style="max-width: 820px">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-sm-6"><?= $form->field($model, 'category')->dropDownList(Question::categoryOptions()) ?></div>
            <div class="col-sm-6"><?= $form->field($model, 'answer_type')->dropDownList(Question::typeOptions()) ?></div>
        </div>
        <?= $form->field($model, 'title')->textarea(['rows' => 2, 'data-autosize' => true, 'autofocus' => true]) ?>
        <?= $form->field($model, 'detail')->textarea(['rows' => 2, 'data-autosize' => true]) ?>
        <div id="options-box">
            <?= $form->field($model, 'optionsText')->textarea(['rows' => 5, 'placeholder' => "เช่น\nชาย\nหญิง"])
                ->hint($model->isNewRecord ? '' : 'ตัวเลือกที่มีคนตอบไปแล้วจะไม่ถูกลบแม้เอาออกจากรายการ') ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?= Html::submitButton('<i class="bi bi-check-lg"></i> บันทึก', ['class' => 'btn btn-primary']) ?>
            <?php if ($model->isNewRecord): ?>
                <?= Html::submitButton('บันทึกและเพิ่มข้อต่อไป', ['class' => 'btn btn-outline-primary', 'name' => 'addAnother', 'value' => 1]) ?>
            <?php endif ?>
            <a href="<?= Url::to(['index', 'projectId' => $project->id]) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
