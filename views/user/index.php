<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $provider */
/** @var string|null $status */
/** @var string|null $q */
/** @var app\models\User[] $pendingUsers */
/** @var array<int,string> $mergeTargets */

use app\components\UserMerger;
use app\models\User;
use app\models\UserAuth;
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ผู้ใช้';
$this->params['breadcrumbs'][] = $this->title;
$sort = $provider->sort;
$statusBadge = [User::STATUS_ACTIVE => 'success', User::STATUS_PENDING => 'warning', User::STATUS_INACTIVE => 'secondary'];
$authBadges = function (User $u) {
    $out = '';
    foreach ($u->auths as $a) {
        $label = UserAuth::sourceLabels()[$a->source] ?? $a->source;
        $out .= Html::tag('span', ($a->source === 'line' ? '<i class="bi bi-line"></i> ' : '<i class="bi bi-google"></i> ') . $label,
            ['class' => 'badge ' . ($a->source === 'line' ? 'btn-line' : 'text-bg-light border'), 'title' => $a->email ?: $a->display_name]) . ' ';
    }
    return $out ?: '<span class="small text-muted">ยังไม่ได้ผูก</span>';
};
?>
<div class="page-header">
    <h1>ผู้ใช้</h1>
    <a href="<?= Url::to(['create']) ?>" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> เพิ่มผู้ใช้ล่วงหน้า</a>
</div>

<?php if ($pendingUsers): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning-subtle">
            รออนุมัติ <span class="badge text-bg-warning"><?= count($pendingUsers) ?></span>
            <div class="small fw-normal text-muted mt-1">
                ถ้าเป็นผู้ใช้เดิมของระบบ ให้เลือก "รวมกับบัญชีเดิม" เพื่อให้เห็นโครงการเดิมของตัวเอง
            </div>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($pendingUsers as $p): ?>
                <?php $suggest = UserMerger::suggestions($p); $best = array_key_first($suggest); ?>
                <li class="list-group-item py-3">
                    <div class="d-flex flex-wrap gap-3 align-items-start">
                        <div class="d-flex gap-2 align-items-center" style="min-width: 240px">
                            <?php if ($p->avatar_url): ?>
                                <img src="<?= Html::encode($p->avatar_url) ?>" class="rounded-circle" width="40" height="40" alt="" referrerpolicy="no-referrer">
                            <?php endif ?>
                            <div>
                                <div class="fw-semibold"><?= Html::encode($p->getFullName()) ?></div>
                                <div class="small text-muted"><?= Html::encode($p->email ?: '— ไม่มีอีเมล —') ?></div>
                                <div class="mt-1"><?= $authBadges($p) ?></div>
                                <div class="small text-muted">ขอเข้าใช้ <?= Yii::$app->formatter->asRelativeTime($p->created_at) ?></div>
                            </div>
                        </div>
                        <?= Html::beginForm(['approve', 'id' => $p->id], 'post', ['class' => 'flex-grow-1 row g-2 align-items-center']) ?>
                        <div class="col-lg">
                            <?= Html::dropDownList('mergeInto', $best, $mergeTargets, [
                                'class' => 'form-select',
                                'prompt' => '— อนุมัติเป็นผู้ใช้ใหม่ —',
                                'aria-label' => 'รวมกับบัญชีเดิม',
                            ]) ?>
                            <?php if ($suggest): ?>
                                <div class="small text-muted mt-1">ชื่อใกล้เคียง: <?= Html::encode(implode(' · ', $suggest)) ?></div>
                            <?php endif ?>
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <button class="btn btn-success" data-confirm="ยืนยันการอนุมัติ?">อนุมัติ</button>
                        </div>
                        <?= Html::endForm() ?>
                        <?= Html::a('ปฏิเสธ', ['reject', 'id' => $p->id], [
                            'class' => 'btn btn-outline-danger',
                            'data' => ['method' => 'post', 'confirm' => 'ปฏิเสธและลบคำขอของ ' . $p->getFullName() . '?'],
                        ]) ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<div class="card">
    <div class="card-body border-bottom">
        <form method="get" class="row g-2">
            <div class="col-sm-5"><input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control" placeholder="ชื่อ / อีเมล / ชื่อผู้ใช้เดิม"></div>
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
                    <td><?= $authBadges($u) ?></td>
                    <td><?= $u->isAdmin() ? '<span class="badge text-bg-primary">ผู้ดูแลระบบ</span>' : 'ผู้ใช้' ?></td>
                    <td><span class="badge text-bg-<?= $statusBadge[$u->status] ?? 'light' ?>"><?= User::statusOptions()[$u->status] ?? $u->status ?></span></td>
                    <td class="small"><?= $u->last_login_at ? Yii::$app->formatter->asRelativeTime($u->last_login_at) : '-' ?></td>
                    <td class="text-end text-nowrap">
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
