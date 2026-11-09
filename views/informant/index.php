<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var yii\data\ActiveDataProvider $provider */
/** @var array $answerCounts */
/** @var array $groups */
/** @var string|null $q */
/** @var string|null $groupId */
/** @var bool $canEdit */

use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ผู้ให้ข้อมูล';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$p = ['projectId' => $project->id];
$sort = $provider->sort;
?>
<div class="page-header">
    <h1>ผู้ให้ข้อมูล <span class="badge text-bg-light fs-6"><?= $provider->totalCount ?></span></h1>
    <?php if ($canEdit): ?>
        <div class="d-flex gap-2">
            <a href="<?= Url::to(['import'] + $p) ?>" class="btn btn-outline-primary"><i class="bi bi-file-earmark-arrow-up"></i> นำเข้า</a>
            <a href="<?= Url::to(['create'] + $p) ?>" class="btn btn-primary"><i class="bi bi-mic"></i> บันทึกการสัมภาษณ์</a>
        </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-body border-bottom">
        <form method="get" action="<?= Url::to(['index'] + $p) ?>" class="row g-2">
            <div class="col-sm-5"><input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control" placeholder="ค้นหารหัสผู้ให้ข้อมูล"></div>
            <?php if ($groups): ?>
                <div class="col-sm-4"><?= Html::dropDownList('groupId', $groupId, $groups, ['class' => 'form-select', 'prompt' => 'ทุกกลุ่ม']) ?></div>
            <?php endif ?>
            <div class="col-sm-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> ค้นหา</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
            <tr>
                <th><?= $sort->link('code') ?></th>
                <th><?= $sort->link('age') ?></th>
                <th>กลุ่ม</th>
                <th>วิธีการเก็บข้อมูล</th>
                <th><?= $sort->link('interviewed_on') ?></th>
                <th class="text-center">ตอบแล้ว</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($provider->getModels() as $m): ?>
                <tr>
                    <td><a href="<?= Url::to(['view', 'id' => $m->id]) ?>" class="fw-semibold"><?= Html::encode($m->code) ?></a></td>
                    <td><?= $m->age ?? '-' ?></td>
                    <td><?= Html::encode($m->group->name ?? '-') ?></td>
                    <td><?= Html::encode($m->collectionMethod->name ?? '-') ?></td>
                    <td><?= $m->interviewed_on ? Yii::$app->formatter->asDate($m->interviewed_on, 'medium') : '-' ?></td>
                    <td class="text-center"><?= (int)($answerCounts[$m->id] ?? 0) ?> ข้อ</td>
                    <td class="text-end text-nowrap">
                        <a href="<?= Url::to(['view', 'id' => $m->id]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <?php if ($canEdit): ?>
                            <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (!$provider->getCount()): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <?php if ($provider->pagination->pageCount > 1): ?>
        <div class="card-body"><?= LinkPager::widget(['pagination' => $provider->pagination]) ?></div>
    <?php endif ?>
</div>
