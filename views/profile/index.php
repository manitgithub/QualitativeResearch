<?php

/** @var yii\web\View $this */
/** @var app\models\User $profile */
/** @var yii\base\DynamicModel $pwd */
/** @var yii\authclient\ClientInterface[] $clients */

use app\models\UserAuth;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ข้อมูลส่วนตัว';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<div class="row g-3" style="max-width: 1200px">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">ข้อมูลทั่วไป</div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>
                <div class="row">
                    <div class="col-sm-3"><?= $form->field($profile, 'prefix') ?></div>
                    <div class="col-sm-4"><?= $form->field($profile, 'first_name') ?></div>
                    <div class="col-sm-5"><?= $form->field($profile, 'last_name') ?></div>
                    <div class="col-sm-6"><?= $form->field($profile, 'username')->hint('ใช้เข้าระบบด้วยรหัสผ่าน') ?></div>
                    <div class="col-sm-6">
                        <label class="form-label">อีเมล</label>
                        <input class="form-control" value="<?= Html::encode($profile->email) ?>" disabled>
                        <div class="form-text">ติดต่อผู้ดูแลระบบหากต้องการเปลี่ยน</div>
                    </div>
                </div>
                <?= Html::submitButton('บันทึก', ['class' => 'btn btn-primary mt-2']) ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><?= $profile->hasPassword() ? 'เปลี่ยนรหัสผ่าน' : 'ตั้งรหัสผ่าน' ?></div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>
                <?php if ($profile->hasPassword()): ?>
                    <?= $form->field($pwd, 'current')->passwordInput(['autocomplete' => 'current-password']) ?>
                <?php endif ?>
                <?= $form->field($pwd, 'password')->passwordInput(['autocomplete' => 'new-password'])->hint('อย่างน้อย 8 ตัวอักษร') ?>
                <?= $form->field($pwd, 'confirm')->passwordInput(['autocomplete' => 'new-password']) ?>
                <?= Html::submitButton('บันทึกรหัสผ่าน', ['class' => 'btn btn-outline-primary']) ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">เข้าระบบด้วยบัญชีอื่น</div>
            <ul class="list-group list-group-flush">
                <?php foreach (UserAuth::sourceLabels() as $source => $label): ?>
                    <?php $auth = $profile->getAuthBySource($source); ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2 py-3">
                        <div>
                            <div class="fw-semibold">
                                <?php if ($source === 'line'): ?><i class="bi bi-line text-success"></i><?php else: ?><i class="bi bi-google"></i><?php endif ?>
                                <?= $label ?>
                            </div>
                            <div class="small text-muted">
                                <?= $auth ? 'ผูกแล้ว: ' . Html::encode($auth->display_name ?: $auth->email ?: $auth->source_id) : 'ยังไม่ได้ผูก' ?>
                            </div>
                        </div>
                        <?php if ($auth): ?>
                            <?= Html::a('ยกเลิกการผูก', ['unlink', 'source' => $source], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data' => ['method' => 'post', 'confirm' => "ยกเลิกการผูกบัญชี {$label}?"],
                            ]) ?>
                        <?php elseif (isset($clients[$source])): ?>
                            <a href="<?= Url::to(['site/auth', 'authclient' => $source]) ?>" class="btn btn-sm <?= $source === 'line' ? 'btn-line' : 'btn-google' ?>">ผูกบัญชี</a>
                        <?php else: ?>
                            <span class="small text-muted">ยังไม่ได้ตั้งค่าในระบบ</span>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>
