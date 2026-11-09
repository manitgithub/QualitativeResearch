<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var int $round */
/** @var array $themes */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ประเด็นที่ได้';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header">
    <h1><?= $this->title ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Url::to(['themes', 'projectId' => $project->id, 'round' => $round, 'export' => 1]) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="<?= Url::to(['mindmap', 'projectId' => $project->id, 'round' => $round, 'mode' => 'themes']) ?>" class="btn btn-outline-primary"><i class="bi bi-diagram-3"></i> แผนผัง</a>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <?php foreach (Interpretation::roundOptions() as $r => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $r === $round ? 'active' : '' ?>" href="<?= Url::to(['themes', 'projectId' => $project->id, 'round' => $r]) ?>"><?= $label ?></a></li>
    <?php endforeach ?>
</ul>

<?php foreach ($themes as $i => $t): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between gap-2">
            <span><?= $i + 1 ?>. <?= Html::encode($t['question']->title) ?></span>
            <a href="<?= Url::to(['question', 'projectId' => $project->id, 'questionId' => $t['question']->id, 'round' => $round]) ?>" class="small text-nowrap">แก้ไขประเด็น →</a>
        </div>
        <?php if (!$t['themes']): ?>
            <div class="card-body text-muted small">ยังไม่มีประเด็นในรอบนี้</div>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead><tr><th>ประเด็น</th><th class="text-center" style="width:80px">จำนวน</th><th style="width:40%">ผู้ให้ข้อมูล</th></tr></thead>
                <tbody>
                <?php $max = max(array_column($t['themes'], 'count')); ?>
                <?php foreach ($t['themes'] as $th): ?>
                    <tr>
                        <td>
                            <?= Html::encode($th['text']) ?>
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-primary" style="width: <?= round($th['count'] / $max * 100) ?>%"></div></div>
                        </td>
                        <td class="text-center fw-semibold"><?= $th['count'] ?></td>
                        <td class="small text-muted"><?= Html::encode(implode(', ', $th['informants'])) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
<?php endforeach ?>
