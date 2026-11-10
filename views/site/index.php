<?php

/** @var yii\web\View $this */
/** @var app\models\Project[] $projects */
/** @var array $counts */
/** @var int $archived */
/** @var string $q */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $archived ? 'โครงการในคลัง' : 'โครงการของฉัน';
$me = Yii::$app->user->identity;
?>
<div class="page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="d-flex gap-2">
        <?php if ($archived): ?>
            <a href="<?= Url::to(['index']) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> โครงการปัจจุบัน</a>
        <?php else: ?>
            <a href="<?= Url::to(['index', 'archived' => 1]) ?>" class="btn btn-outline-secondary"><i class="bi bi-archive"></i> คลัง</a>
            <a href="<?= Url::to(['project/create']) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> สร้างโครงการวิจัย</a>
        <?php endif ?>
    </div>
</div>

<form method="get" action="<?= Url::to(['index']) ?>" class="mb-3" role="search">
    <?php if ($archived): ?><input type="hidden" name="archived" value="1"><?php endif ?>
    <div class="input-group" style="max-width: 640px">
        <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control"
               placeholder="ค้นหาชื่อโครงการ รายละเอียด หรือชื่อหัวหน้าโครงการ" aria-label="ค้นหาโครงการ"
               autocomplete="off" <?= $q !== '' ? 'autofocus' : '' ?>>
        <button class="btn btn-primary" type="submit">ค้นหา</button>
        <?php if ($q !== ''): ?>
            <a href="<?= Url::to(['index', 'archived' => $archived ?: null]) ?>" class="btn btn-outline-secondary" title="ล้างคำค้น"><i class="bi bi-x-lg"></i></a>
        <?php endif ?>
    </div>
    <?php if ($q !== ''): ?>
        <div class="small text-muted mt-2">พบ <?= count($projects) ?> โครงการ สำหรับ “<?= Html::encode($q) ?>”</div>
    <?php endif ?>
</form>

<?php if (!$projects): ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-<?= $q !== '' ? 'search' : 'folder2' ?> display-4 d-block mb-2"></i>
            <?php if ($q !== ''): ?>
                ไม่พบโครงการที่ตรงกับ “<?= Html::encode($q) ?>”
            <?php else: ?>
                <?= $archived ? 'ไม่มีโครงการในคลัง' : 'ยังไม่มีโครงการ เริ่มสร้างโครงการวิจัยแรกได้เลย' ?>
            <?php endif ?>
        </div>
    </div>
<?php endif ?>

<div class="row g-3">
    <?php foreach ($projects as $p): ?>
        <?php
        $role = $p->roleOf($me);
        $team = array_filter($p->members, fn($m) => $m->user_id !== $p->owner_id);
        ?>
        <div class="col-md-6 col-xl-4">
            <a href="<?= Url::to(['project/view', 'id' => $p->id]) ?>" class="card project-card h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <h2 class="h6 fw-bold mb-0"><?= Html::encode($p->title) ?></h2>
                        <?php if ($role === 'owner' && (int)$p->owner_id === (int)$me->id): ?>
                            <span class="badge text-bg-primary flex-shrink-0">หัวหน้า</span>
                        <?php elseif ($role === 'viewer'): ?>
                            <span class="badge text-bg-light flex-shrink-0">ดูอย่างเดียว</span>
                        <?php else: ?>
                            <span class="badge text-bg-info flex-shrink-0">ทีมงาน</span>
                        <?php endif ?>
                    </div>
                    <div class="small text-muted mb-3">
                        <i class="bi bi-person"></i> <?= Html::encode($p->owner->getFullName() ?? '-') ?>
                        <?php if ($team): ?>
                            · ทีม <?= count($team) ?> คน
                        <?php endif ?>
                    </div>
                    <div class="d-flex gap-3 small">
                        <span><i class="bi bi-list-ol text-primary"></i> <?= $counts[$p->id]['questions'] ?? 0 ?> คำถาม</span>
                        <span><i class="bi bi-people text-primary"></i> <?= $counts[$p->id]['informants'] ?? 0 ?> ผู้ให้ข้อมูล</span>
                    </div>
                </div>
                <div class="card-footer bg-transparent small text-muted border-0 pt-0">
                    แก้ไขล่าสุด <?= Yii::$app->formatter->asRelativeTime($p->updated_at) ?>
                </div>
            </a>
        </div>
    <?php endforeach ?>
</div>
