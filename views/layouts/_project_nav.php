<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var string $route */

use yii\helpers\Html;
use yii\helpers\Url;

$me = Yii::$app->user->identity;
$canEdit = $project->canEdit($me);
$canManage = $project->canManage($me);
$p = ['projectId' => $project->id];

$sections = [
    'โครงการ' => [
        ['ภาพรวม', 'bi-speedometer2', ['/project/view', 'id' => $project->id], ['project/view']],
        ['คำถาม', 'bi-list-ol', ['/question/index'] + $p, ['question/index', 'question/create', 'question/update']],
        ['ผู้ให้ข้อมูล', 'bi-person-lines-fill', ['/informant/index'] + $p, ['informant/index', 'informant/view', 'informant/update']],
    ],
    'บันทึกข้อมูล' => $canEdit ? [
        ['บันทึกการสัมภาษณ์', 'bi-mic', ['/informant/create'] + $p, ['informant/create']],
        ['นำเข้า Excel/CSV', 'bi-file-earmark-arrow-up', ['/informant/import'] + $p, ['informant/import']],
    ] : [],
    'วิเคราะห์' => [
        ['ตามคำถามการวิจัย', 'bi-chat-left-text', ['/report/question'] + $p, ['report/question']],
        ['ประเด็นที่ได้', 'bi-tags', ['/report/themes'] + $p, ['report/themes']],
        ['ตามผู้ให้ข้อมูล', 'bi-table', ['/report/informant'] + $p, ['report/informant']],
        ['สถิติ', 'bi-bar-chart', ['/report/stats'] + $p, ['report/stats']],
        ['แผนผังความคิด', 'bi-diagram-3', ['/report/mindmap'] + $p, ['report/mindmap']],
        ['Word cloud', 'bi-cloud', ['/report/wordcloud'] + $p, ['report/wordcloud']],
        ['Input-Process-Output', 'bi-arrow-right-square', ['/report/ipo'] + $p, ['report/ipo']],
        ['Conceptual map', 'bi-bounding-box', ['/concept/index'] + $p, ['concept/index']],
    ],
    'ออกเล่ม' => [
        ['เล่มรายงาน / บทวิเคราะห์', 'bi-journal-text', ['/book/index'] + $p, ['book/index']],
    ],
    'ตั้งค่า' => $canManage ? [
        ['แก้ไขโครงการ', 'bi-pencil-square', ['/project/update', 'id' => $project->id], ['project/update']],
        ['ทีมงานและกลุ่ม', 'bi-people', ['/project/members', 'id' => $project->id], ['project/members']],
    ] : [],
];
?>
<aside class="project-nav border-end bg-body d-none d-lg-block">
    <div class="p-3 border-bottom">
        <div class="small text-muted">โครงการ</div>
        <a href="<?= Url::to(['/project/view', 'id' => $project->id]) ?>" class="fw-semibold text-body text-decoration-none d-block project-title">
            <?= Html::encode($project->title) ?>
        </a>
        <?php if ($project->is_archived): ?>
            <span class="badge text-bg-secondary mt-1">อยู่ในคลัง</span>
        <?php endif ?>
    </div>
    <nav class="py-2">
        <?php foreach ($sections as $title => $items): ?>
            <?php if (!$items) continue; ?>
            <div class="px-3 pt-2 pb-1 small text-uppercase text-muted fw-semibold"><?= $title ?></div>
            <?php foreach ($items as [$label, $icon, $url, $routes]): ?>
                <a href="<?= Url::to($url) ?>" class="nav-item-link <?= in_array($route, $routes, true) ? 'active' : '' ?>">
                    <i class="bi <?= $icon ?>"></i> <?= $label ?>
                </a>
            <?php endforeach ?>
        <?php endforeach ?>
    </nav>
</aside>

<?php // เมนูสำหรับจอเล็ก ?>
<div class="d-lg-none position-fixed bottom-0 end-0 m-3" style="z-index:1030">
    <div class="dropup">
        <button class="btn btn-primary rounded-pill shadow" data-bs-toggle="dropdown"><i class="bi bi-list"></i> เมนูโครงการ</button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="max-height:70vh;overflow:auto">
            <?php foreach ($sections as $title => $items): ?>
                <?php if (!$items) continue; ?>
                <li><h6 class="dropdown-header"><?= $title ?></h6></li>
                <?php foreach ($items as [$label, $icon, $url]): ?>
                    <li><a class="dropdown-item" href="<?= Url::to($url) ?>"><i class="bi <?= $icon ?>"></i> <?= $label ?></a></li>
                <?php endforeach ?>
            <?php endforeach ?>
        </ul>
    </div>
</div>
