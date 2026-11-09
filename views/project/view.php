<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var array $stats */
/** @var array $progress */
/** @var bool $canEdit */
/** @var bool $canManage */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $project->title;
$p = ['projectId' => $project->id];
$questions = $project->researchQuestions;
?>
<div class="page-header">
    <div>
        <h1><?= Html::encode($project->title) ?></h1>
        <div class="text-muted small mt-1">
            หัวหน้าโครงการ: <?= Html::encode($project->owner->getFullName()) ?>
            <?php if ($project->started_on): ?> · เริ่ม <?= Yii::$app->formatter->asDate($project->started_on, 'long') ?><?php endif ?>
        </div>
    </div>
    <?php if ($canEdit && !$project->is_archived): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= Url::to(['informant/create'] + $p) ?>" class="btn btn-primary"><i class="bi bi-mic"></i> บันทึกการสัมภาษณ์</a>
            <a href="<?= Url::to(['question/create'] + $p) ?>" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> เพิ่มคำถาม</a>
        </div>
    <?php endif ?>
</div>

<?php if ($project->description): ?>
    <p class="text-body-secondary" style="white-space: pre-wrap; max-width: 900px"><?= Html::encode($project->description) ?></p>
<?php endif ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['ผู้ให้ข้อมูล', $stats['informants'], 'bi-people', '#ccfbf1', ['informant/index'] + $p],
        ['คำถามการวิจัย', $stats['research'], 'bi-chat-left-text', '#dbeafe', ['question/index'] + $p],
        ['คำถามข้อมูลพื้นฐาน', $stats['demographic'], 'bi-card-list', '#fae8ff', ['question/index'] + $p],
        ['คำตอบทั้งหมด', $stats['responses'], 'bi-chat-dots', '#fef3c7', ['report/question'] + $p],
    ] as [$label, $value, $icon, $bg, $url]): ?>
        <div class="col-6 col-xl-3">
            <a href="<?= Url::to($url) ?>" class="card stat-card h-100 text-decoration-none text-body">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: <?= $bg ?>"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-value"><?= Yii::$app->formatter->asInteger($value) ?></div>
                        <div class="small text-muted"><?= $label ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach ?>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>ความคืบหน้าการสรุปประเด็นรายคำถาม</span>
                <a href="<?= Url::to(['report/question'] + $p) ?>" class="small">ไปหน้าวิเคราะห์ →</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (!$questions): ?>
                    <div class="list-group-item text-muted py-4 text-center">
                        ยังไม่มีคำถามการวิจัย
                        <?php if ($canEdit): ?><a href="<?= Url::to(['question/create'] + $p) ?>">เพิ่มคำถาม</a><?php endif ?>
                    </div>
                <?php endif ?>
                <?php foreach ($questions as $i => $q): ?>
                    <?php
                    $answered = (int)($progress['answered'][$q->id] ?? 0);
                    $coded = (int)($progress['coded'][$q->id] ?? 0);
                    $pct = $answered ? round($coded / $answered * 100) : 0;
                    ?>
                    <a href="<?= Url::to(['report/question', 'questionId' => $q->id] + $p) ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between gap-3">
                            <div class="text-truncate"><span class="text-muted"><?= $i + 1 ?>.</span> <?= Html::encode($q->title) ?></div>
                            <div class="small text-muted text-nowrap"><?= $coded ?>/<?= $answered ?></div>
                        </div>
                        <div class="progress mt-2" style="height: 6px">
                            <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header">ประเด็นที่บันทึกแล้ว</div>
            <ul class="list-group list-group-flush">
                <?php foreach (Interpretation::roundOptions() as $r => $label): ?>
                    <a href="<?= Url::to(['report/themes', 'round' => $r] + $p) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span><?= $label ?></span>
                        <span class="badge text-bg-light"><?= (int)($stats['rounds'][$r] ?? 0) ?></span>
                    </a>
                <?php endforeach ?>
            </ul>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>ทีมงาน</span>
                <?php if ($canManage): ?><a href="<?= Url::to(['members', 'id' => $project->id]) ?>" class="small">จัดการ</a><?php endif ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($project->members as $m): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= Html::encode($m->user->getFullName()) ?></span>
                        <span class="small text-muted"><?= app\models\ProjectMember::roleOptions()[$m->role] ?? $m->role ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>
