<?php

/**
 * Layout หน้าแนะนำระบบ (สาธารณะ เต็มความกว้าง)
 *
 * @var yii\web\View $this
 * @var string $content
 */

use app\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

$this->render('_head');
$guest = Yii::$app->user->isGuest;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="th" class="h-100">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · ' . Yii::$app->name : Yii::$app->name) ?></title>
    <meta name="description" content="ระบบวิเคราะห์ข้อมูลเชิงคุณภาพ บันทึกสัมภาษณ์ ตีความ ยกประเด็น สังเคราะห์ Input-Process-Output และออกเล่มรายงาน พร้อม AI ช่วยวิเคราะห์">
</head>
<body class="d-flex flex-column h-100 landing">
<?php $this->beginBody() ?>
<?= $this->render('_credits') ?>
<nav class="navbar navbar-dark app-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= Url::home() ?>">
            <i class="bi bi-chat-square-quote"></i>
            <span class="d-none d-sm-inline"><?= Html::encode(Yii::$app->name) ?></span>
            <span class="d-sm-none">QR</span>
        </a>
        <?php if ($guest): ?>
            <a href="<?= Url::to(['/site/login']) ?>" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ</a>
        <?php else: ?>
            <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-folder2-open"></i> โครงการของฉัน</a>
        <?php endif ?>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container"><?= Alert::widget() ?></div>
    <?= $content ?>
</main>
<footer class="landing-footer py-4 mt-auto">
    <div class="container small d-flex flex-wrap justify-content-between gap-2">
        <span><?= Html::encode(Yii::$app->name) ?></span>
        <span>
            ศึกษาและพัฒนาโดย
            <a href="https://rps.wu.ac.th/researchersInfo/98" target="_blank" rel="noopener">รศ.ดร.อุไร จเรประพาฬ</a> ·
            <a href="https://manitgithub.github.io/" target="_blank" rel="noopener">มานิต จิตต์ประไพย</a>
        </span>
    </div>
</footer>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
