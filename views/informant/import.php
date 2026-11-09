<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\forms\ImportForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'นำเข้าข้อมูลจาก Excel / CSV';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = ['label' => 'ผู้ให้ข้อมูล', 'url' => ['index', 'projectId' => $project->id]];
$this->params['breadcrumbs'][] = 'นำเข้า';
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<div class="row g-3" style="max-width: 1100px">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
                <?= $form->field($model, 'file')->fileInput(['class' => 'form-control', 'accept' => '.xlsx,.xls,.csv']) ?>
                <?= $form->field($model, 'overwrite')->checkbox() ?>
                <?= Html::submitButton('<i class="bi bi-upload"></i> นำเข้า', ['class' => 'btn btn-primary']) ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">รูปแบบไฟล์</div>
            <div class="card-body">
                <ol class="mb-3">
                    <li>แถวแรกเป็นหัวคอลัมน์ คอลัมน์แรกคือ <b>รหัสผู้ให้ข้อมูล</b></li>
                    <li>คอลัมน์ <b>อายุ</b> (ถ้ามี)</li>
                    <li>คอลัมน์คำถามให้ขึ้นต้นด้วย <code>#รหัสคำถาม</code> หรือใช้ข้อความคำถามตรงกันทุกตัวอักษร</li>
                    <li>ถ้ารหัสผู้ให้ข้อมูลมีอยู่แล้ว จะเติมคำตอบให้คนเดิม</li>
                    <li>คำถามแบบเลือกตอบ ถ้าพบตัวเลือกใหม่ระบบจะเพิ่มให้อัตโนมัติ</li>
                </ol>
                <a href="<?= Url::to(['template', 'projectId' => $project->id]) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> ดาวน์โหลดแม่แบบ (.xlsx)
                </a>
            </div>
        </div>
    </div>
</div>
