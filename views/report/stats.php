<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var array $charts */
/** @var array $ageBins */

use yii\helpers\Html;
use yii\helpers\Json;

$this->title = 'สถิติ';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js');
$payload = [];
if ($ageBins) {
    $payload[] = ['id' => 'chart-age', 'type' => 'bar', 'labels' => array_column($ageBins, 'label'), 'values' => array_column($ageBins, 'n')];
}
foreach ($charts as $i => $c) {
    $payload[] = ['id' => 'chart-' . $i, 'type' => count($c['labels']) <= 5 ? 'doughnut' : 'bar', 'labels' => $c['labels'], 'values' => $c['values']];
}
$json = Json::htmlEncode($payload);
$this->registerJs(<<<JS
Chart.defaults.font.family = 'Sarabun, sans-serif';
var palette = ['#0f766e','#2563eb','#d97706','#db2777','#7c3aed','#059669','#dc2626','#0891b2','#65a30d','#9333ea'];
$json.forEach(function (c) {
    new Chart(document.getElementById(c.id), {
        type: c.type,
        data: {labels: c.labels, datasets: [{data: c.values, backgroundColor: c.type === 'bar' ? '#0f766e' : palette, borderWidth: c.type === 'bar' ? 0 : 2}]},
        options: {
            plugins: {legend: {display: c.type !== 'bar', position: 'right'}},
            indexAxis: c.type === 'bar' && c.labels.length > 6 ? 'y' : 'x',
            scales: c.type === 'bar' ? {x: {ticks: {precision: 0}}, y: {ticks: {precision: 0}}} : {}
        }
    });
});
JS);
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<div class="row g-3">
    <?php if ($ageBins): ?>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">กลุ่มอายุผู้ให้ข้อมูล</div>
                <div class="card-body"><canvas id="chart-age" height="220"></canvas></div>
            </div>
        </div>
    <?php endif ?>
    <?php foreach ($charts as $i => $c): ?>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><?= Html::encode($c['question']->title) ?></div>
                <div class="card-body">
                    <canvas id="chart-<?= $i ?>" height="220"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <?php foreach ($c['labels'] as $k => $label): ?>
                            <tr>
                                <td><?= Html::encode($label) ?></td>
                                <td class="text-end"><?= $c['values'][$k] ?></td>
                                <td class="text-end text-muted" style="width:80px"><?= $c['total'] ? Yii::$app->formatter->asPercent($c['values'][$k] / $c['total'], 1) : '-' ?></td>
                            </tr>
                        <?php endforeach ?>
                        <tr class="fw-semibold"><td>รวม</td><td class="text-end"><?= $c['total'] ?></td><td></td></tr>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    <?php if (!$charts && !$ageBins): ?>
        <div class="col-12"><div class="alert alert-info">ยังไม่มีคำถามแบบเลือกตอบหรือใช่/ไม่ใช่</div></div>
    <?php endif ?>
</div>
