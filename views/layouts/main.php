<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\models\User;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Url;

$this->render('_head');

/** @var User|null $me */
$me = Yii::$app->user->identity;
/** @var app\models\Project|null $project */
$project = $this->params['project'] ?? null;
$route = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
$isActive = fn(string ...$routes) => in_array($route, $routes, true) || in_array(Yii::$app->controller->id . '/*', $routes, true);
$pending = $me && $me->isAdmin() ? (int)User::find()->where(['status' => User::STATUS_PENDING])->count() : 0;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="th" class="h-100">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · ' . Yii::$app->name : Yii::$app->name) ?></title>
</head>
<body class="d-flex flex-column h-100 bg-body-tertiary">
<?php $this->beginBody() ?>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= Url::home() ?>">
            <i class="bi bi-chat-square-quote"></i>
            <span class="d-none d-sm-inline"><?= Html::encode(Yii::$app->name) ?></span>
            <span class="d-sm-none">QR</span>
        </a>
        <?php if ($me): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topnav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $route === 'site/index' ? 'active' : '' ?>" href="<?= Url::to(['/site/index']) ?>">
                            <i class="bi bi-folder2-open"></i> โครงการของฉัน
                        </a>
                    </li>
                    <?php if ($me->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActive('user/*') ? 'active' : '' ?>" href="<?= Url::to(['/user/index']) ?>">
                                <i class="bi bi-people"></i> ผู้ใช้
                                <?php if ($pending): ?><span class="badge text-bg-warning"><?= $pending ?></span><?php endif ?>
                            </a>
                        </li>
                    <?php endif ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <?php if ($me->avatar_url): ?>
                                <img src="<?= Html::encode($me->avatar_url) ?>" class="rounded-circle" width="26" height="26" alt="" referrerpolicy="no-referrer">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-5"></i>
                            <?php endif ?>
                            <?= Html::encode($me->first_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= Url::to(['/profile/index']) ?>"><i class="bi bi-person-gear"></i> ข้อมูลส่วนตัว / ผูกบัญชี</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <?= Html::beginForm(['/site/logout'], 'post') ?>
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</button>
                                <?= Html::endForm() ?>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>
    </div>
</nav>

<div class="d-flex flex-grow-1">
    <?php if ($project): ?>
        <?= $this->render('_project_nav', ['project' => $project, 'route' => $route]) ?>
    <?php endif ?>

    <main class="flex-grow-1 p-3 p-lg-4 min-w-0" role="main">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget([
                'homeLink' => ['label' => 'หน้าหลัก', 'url' => Yii::$app->homeUrl],
                'links' => $this->params['breadcrumbs'],
            ]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </main>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
