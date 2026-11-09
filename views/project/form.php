<?php

/** @var yii\web\View $this */
/** @var app\models\Project $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->isNewRecord ? 'สร้างโครงการวิจัย' : 'แก้ไขโครงการ';
if (!$model->isNewRecord) {
    $this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header"><h1><?= Html::encode($this->title) ?></h1></div>

<div class="card" style="max-width: 820px">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'title')->textarea(['rows' => 2, 'data-autosize' => true]) ?>
        <?= $form->field($model, 'description')->textarea(['rows' => 4, 'data-autosize' => true])
            ->hint('วัตถุประสงค์ ขอบเขต กลุ่มเป้าหมาย ฯลฯ') ?>
        <div class="row">
            <div class="col-sm-6"><?= $form->field($model, 'started_on')->input('date') ?></div>
        </div>
        <div class="d-flex gap-2">
            <?= Html::submitButton('<i class="bi bi-check-lg"></i> บันทึก', ['class' => 'btn btn-primary']) ?>
            <a href="<?= $model->isNewRecord ? Url::home() : Url::to(['view', 'id' => $model->id]) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php if (!$model->isNewRecord): ?>
    <div class="card mt-4 border-danger-subtle" style="max-width: 820px">
        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <div class="fw-semibold"><?= $model->is_archived ? 'นำออกจากคลัง' : 'เก็บเข้าคลัง' ?></div>
                <div class="small text-muted">โครงการในคลังยังดูข้อมูลได้ แต่แก้ไขไม่ได้ และไม่แสดงในหน้าหลัก</div>
            </div>
            <?= Html::a($model->is_archived ? 'นำออกจากคลัง' : 'เก็บเข้าคลัง',
                [$model->is_archived ? 'restore' : 'archive', 'id' => $model->id],
                ['class' => 'btn btn-outline-danger', 'data' => ['method' => 'post', 'confirm' => 'ยืนยัน?']]) ?>
        </div>
    </div>
<?php endif ?>
