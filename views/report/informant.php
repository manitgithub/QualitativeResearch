<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Question[] $questions */
/** @var app\models\Informant[] $informants */
/** @var array $matrix */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ข้อมูลตามผู้ให้ข้อมูล';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header">
    <h1><?= $this->title ?> <span class="badge text-bg-light fs-6"><?= count($informants) ?> คน</span></h1>
    <a href="<?= Url::to(['informant', 'projectId' => $project->id, 'export' => 1]) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
</div>

<div class="card">
    <div class="table-responsive" style="max-height: 75vh">
        <table class="table table-bordered table-sm matrix-table mb-0">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 2">
            <tr>
                <th>รหัส</th>
                <?php foreach ($questions as $q): ?>
                    <th><?= Html::encode(mb_strimwidth($q->title, 0, 90, '…')) ?></th>
                <?php endforeach ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($informants as $inf): ?>
                <tr>
                    <td><a href="<?= Url::to(['informant/view', 'id' => $inf->id]) ?>" class="fw-semibold"><?= Html::encode($inf->code) ?></a></td>
                    <?php foreach ($questions as $q): ?>
                        <?php $v = $matrix[$inf->id][$q->id] ?? ''; ?>
                        <td title="<?= Html::encode($v) ?>"><?= Html::encode(mb_strimwidth($v, 0, 220, '…')) ?></td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
