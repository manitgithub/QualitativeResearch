<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $provider */
/** @var string|null $status */
/** @var string|null $q */
/** @var int $pending */

use app\models\User;
use app\models\UserAuth;
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ผู้ใช้';
$this->params['breadcrumbs'][] = $this->title;
$sort = $provider->sort;
$statusBadge = [User::STATUS_ACTIVE => 'success', User::STATUS_PENDING => 'warning', User::STATUS_INACTIVE => 'secondary'];
?>
<div class="page-header">
    <h1>ผู้ใช้</h1>
    <a href="<?= Url::to(['create']) ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> เพิ่มผู้ใช้</a>
</div>

<?php if ($pending && (string)$status !== (string)User::STATUS_PENDING): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span>มีผู้ใช้รออนุมัติ <?= $pending ?> คน</span>
        <a href="<?= Url::to(['index', 'status' => User::STATUS_PENDING]) ?>" class="btn btn-sm btn-warning">ดูรายการ</a>
    </div>
<?php endif ?>

<div class="card">
    <div class="card-body border-bottom">
        <form method="get" class="row g-2">
            <div class="col-sm-5"><input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control" placeholder="ชื่อ / อีเมล / ชื่อผู้ใช้"></div>
            <div class="col-sm-3"><?= Html::dropDownList('status', $status, User::statusOptions(), ['class' => 'form-select', 'prompt' => 'ทุกสถานะ']) ?></div>
            <div class="col-sm-auto"><button class="btn btn-outline-secondary">ค้นหา</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th><?= $sort->link('first_name') ?></th>
                <th><?= $sort->link('username') ?></th>
                <th>เข้าระบบด้วย</th>
                <th>สิทธิ์</th>
                <th><?= $sort->link('status') ?></th>
                <th><?= $sort->link('last_login_at') ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($provider->getModels() as $u): ?>
                <tr>
                    <td>
                        <?= Html::encode($u->getFullName()) ?>
                        <div class="small text-muted"><?= Html::encode($u->email) ?></div>
                    </td>
                    <td><?= Html::encode($u->username ?? '-') ?></td>
                    <td>
                        <?php if ($u->hasPassword()): ?><span class="badge text-bg-light">รหัสผ่าน</span><?php endif ?>
                        <?php foreach ($u->auths as $a): ?>
                            <span class="badge <?= $a->source === 'line' ? 'btn-line' : 'text-bg-light border' ?>"><?= UserAuth::sourceLabels()[$a->source] ?? $a->source ?></span>
                        <?php endforeach ?>
                    </td>
                    <td><?= $u->isAdmin() ? '<span class="badge text-bg-primary">ผู้ดูแลระบบ</span>' : 'ผู้ใช้' ?></td>
                    <td><span class="badge text-bg-<?= $statusBadge[$u->status] ?? 'light' ?>"><?= User::statusOptions()[$u->status] ?? $u->status ?></span></td>
                    <td class="small"><?= $u->last_login_at ? Yii::$app->formatter->asRelativeTime($u->last_login_at) : '-' ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($u->status === User::STATUS_PENDING): ?>
                            <?= Html::a('อนุมัติ', ['approve', 'id' => $u->id], ['class' => 'btn btn-sm btn-success', 'data-method' => 'post']) ?>
                        <?php endif ?>
                        <a href="<?= Url::to(['update', 'id' => $u->id]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php if ($provider->pagination->pageCount > 1): ?>
        <div class="card-body"><?= LinkPager::widget(['pagination' => $provider->pagination]) ?></div>
    <?php endif ?>
</div>
