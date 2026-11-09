<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'เข้าสู่ระบบ';
$clients = Yii::$app->authClientCollection->getClients();
?>
<div class="text-center mb-4">
    <div class="d-inline-grid rounded-4 text-white mb-3" style="width:64px;height:64px;place-items:center;background:#0f766e;font-size:1.8rem">
        <i class="bi bi-chat-square-quote"></i>
    </div>
    <h1 class="h4 fw-bold mb-1"><?= Html::encode(Yii::$app->name) ?></h1>
    <div class="text-muted">Qualitative Research Analysis</div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php if ($clients): ?>
            <div class="d-grid gap-2 mb-3">
                <?php if (isset($clients['google'])): ?>
                    <a href="<?= Url::to(['site/auth', 'authclient' => 'google']) ?>" class="btn btn-google btn-lg d-flex align-items-center justify-content-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                        เข้าสู่ระบบด้วย Google
                    </a>
                <?php endif ?>
                <?php if (isset($clients['line'])): ?>
                    <a href="<?= Url::to(['site/auth', 'authclient' => 'line']) ?>" class="btn btn-line btn-lg d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-line"></i> เข้าสู่ระบบด้วย LINE
                    </a>
                <?php endif ?>
            </div>
            <div class="divider-text my-3">หรือใช้ชื่อผู้ใช้</div>
        <?php endif ?>

        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
        <?= $form->field($model, 'username')->textInput(['autofocus' => !$clients, 'autocomplete' => 'username']) ?>
        <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password']) ?>
        <?= $form->field($model, 'rememberMe')->checkbox() ?>
        <div class="d-grid">
            <?= Html::submitButton('เข้าสู่ระบบ', ['class' => 'btn btn-primary btn-lg']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<p class="text-center text-muted small mt-3">
    ผู้ใช้ใหม่ที่เข้าด้วย Google/LINE ครั้งแรกจะต้องรอผู้ดูแลระบบอนุมัติก่อนใช้งาน
</p>
