<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var int $round */
/** @var array $ipo */
/** @var app\models\Concept[] $concepts */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Input – Process – Output';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$cols = [
    'input' => ['Input', '#dbeafe', 'is_input'],
    'process' => ['Process', '#ccfbf1', 'is_process'],
    'output' => ['Output', '#fef3c7', 'is_output'],
];
?>
<div class="page-header">
    <h1><?= $this->title ?></h1>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="bi bi-printer"></i> พิมพ์</button>
</div>

<ul class="nav nav-pills mb-3 no-print">
    <?php foreach (Interpretation::roundOptions() as $r => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $r === $round ? 'active' : '' ?>" href="<?= Url::to(['ipo', 'projectId' => $project->id, 'round' => $r]) ?>"><?= $label ?></a></li>
    <?php endforeach ?>
</ul>

<h2 class="h6 text-muted">จากประเด็นที่ระบุ IPO (<?= Interpretation::roundOptions()[$round] ?>)</h2>
<div class="row g-3 mb-4 align-items-stretch">
    <?php $n = 0; foreach ($cols as $key => [$label, $bg]): ?>
        <div class="col-lg-4 d-flex">
            <div class="card flex-fill" style="background: <?= $bg ?>">
                <div class="card-header fs-5 text-center"><?= $label ?></div>
                <div class="card-body">
                    <?php if (!$ipo[$key]): ?><div class="text-muted small text-center">—</div><?php endif ?>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($ipo[$key] as $text => $count): ?>
                            <li><?= Html::encode($text) ?><?= $count > 1 ? " <span class=\"badge text-bg-light\">{$count}</span>" : '' ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<h2 class="h6 text-muted">จาก Conceptual map</h2>
<div class="row g-3">
    <?php foreach ($cols as $key => [$label, $bg, $flag]): ?>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header text-center"><?= $label ?></div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($concepts as $c): ?>
                        <?php if ($c->$flag): ?>
                            <li class="list-group-item">
                                <?= Html::encode($c->name) ?>
                                <?php if ($c->is_problem): ?><span class="badge text-bg-danger">ปัญหา</span><?php endif ?>
                                <?php if ($c->is_success): ?><span class="badge text-bg-success">ความสำเร็จ</span><?php endif ?>
                            </li>
                        <?php endif ?>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    <?php endforeach ?>
</div>
