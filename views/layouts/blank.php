<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="th" class="h-100">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · ' . Yii::$app->name : Yii::$app->name) ?></title>
</head>
<body class="h-100 login-bg">
<?php $this->beginBody() ?>
<main class="h-100 d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width: 420px">
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
