<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-exclamation-triangle display-4 text-warning d-block mb-3"></i>
        <h1 class="h4"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-4"><?= nl2br(Html::encode($message)) ?></p>
        <a href="<?= Yii::$app->homeUrl ?>" class="btn btn-primary">กลับหน้าหลัก</a>
    </div>
</div>
