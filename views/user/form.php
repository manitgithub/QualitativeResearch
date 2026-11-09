<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var app\models\LoginLog[]|null $logs */

use app\models\User;
use app\models\UserAuth;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->isNewRecord ? 'เพิ่มผู้ใช้' : $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'ผู้ใช้', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$isSelf = (int)$model->id === (int)Yii::$app->user->id;
?>
<div class="page-header"><h1><?= Html::encode($this->title) ?></h1></div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>
                <div class="row">
                    <div class="col-sm-3"><?= $form->field($model, 'prefix') ?></div>
                    <div class="col-sm-4"><?= $form->field($model, 'first_name') ?></div>
                    <div class="col-sm-5"><?= $form->field($model, 'last_name') ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'username') ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'email')->input('email')->hint('ใช้จับคู่บัญชี Google/LINE อัตโนมัติ') ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'role')->dropDownList(User::roleOptions(), ['disabled' => $isSelf]) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'status')->dropDownList(User::statusOptions(), ['disabled' => $isSelf]) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'newPassword')->passwordInput(['autocomplete' => 'new-password'])
                            ->hint($model->isNewRecord ? 'เว้นว่างได้ถ้าจะให้เข้าด้วย Google/LINE เท่านั้น' : 'เว้นว่างถ้าไม่ต้องการเปลี่ยน') ?></div>
                </div>
                <div class="d-flex gap-2">
                    <?= Html::submitButton('บันทึก', ['class' => 'btn btn-primary']) ?>
                    <a href="<?= Url::to(['index']) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
    <?php if (!$model->isNewRecord): ?>
        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header">บัญชีที่ผูกไว้</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($model->auths as $a): ?>
                        <li class="list-group-item">
                            <b><?= UserAuth::sourceLabels()[$a->source] ?? $a->source ?></b>
                            <?= Html::encode($a->display_name) ?> <span class="text-muted small"><?= Html::encode($a->email) ?></span>
                        </li>
                    <?php endforeach ?>
                    <?php if (!$model->auths): ?><li class="list-group-item text-muted">ไม่มี</li><?php endif ?>
                </ul>
            </div>
            <div class="card">
                <div class="card-header">ประวัติการเข้าระบบล่าสุด</div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($logs ?? [] as $l): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= Yii::$app->formatter->asDatetime($l->created_at, 'short') ?> · <?= Html::encode($l->method) ?></span>
                            <span class="text-muted"><?= Html::encode($l->ip) ?></span>
                        </li>
                    <?php endforeach ?>
                    <?php if (!$logs): ?><li class="list-group-item text-muted">ยังไม่เคยเข้าระบบ</li><?php endif ?>
                </ul>
            </div>
        </div>
    <?php endif ?>
</div>
