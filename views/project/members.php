<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\ProjectMember $member */
/** @var array $candidates */
/** @var app\models\InformantGroup $group */

use app\models\ProjectMember;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'ทีมงานและกลุ่มผู้ให้ข้อมูล';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">ทีมงาน</div>
            <?= Html::beginForm() ?>
            <table class="table mb-0 align-middle">
                <thead><tr><th>ชื่อ</th><th style="width: 260px">บทบาท</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($project->members as $m): ?>
                    <tr>
                        <td>
                            <?= Html::encode($m->user->getFullName()) ?>
                            <div class="small text-muted"><?= Html::encode($m->user->email ?: $m->user->username) ?></div>
                        </td>
                        <td>
                            <?php if ((int)$m->user_id === (int)$project->owner_id): ?>
                                <span class="badge text-bg-primary">หัวหน้าโครงการ</span>
                            <?php else: ?>
                                <?= Html::dropDownList("roles[{$m->user_id}]", $m->role, ProjectMember::roleOptions(), ['class' => 'form-select form-select-sm']) ?>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <?php if ((int)$m->user_id !== (int)$project->owner_id): ?>
                                <?= Html::a('<i class="bi bi-x-lg"></i>', ['remove-member', 'id' => $project->id, 'userId' => $m->user_id], [
                                    'class' => 'btn btn-sm btn-outline-danger', 'title' => 'นำออก',
                                    'data' => ['method' => 'post', 'confirm' => 'นำ ' . $m->user->getFullName() . ' ออกจากทีม?'],
                                ]) ?>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php if (count($project->members) > 1): ?>
                <div class="card-body border-top">
                    <button class="btn btn-outline-primary btn-sm">บันทึกบทบาท</button>
                </div>
            <?php endif ?>
            <?= Html::endForm() ?>
        </div>

        <div class="card mt-3">
            <div class="card-header">เพิ่มทีมงาน</div>
            <div class="card-body">
                <?php if (!$candidates): ?>
                    <p class="text-muted mb-0">ไม่มีผู้ใช้ที่เพิ่มได้ (ผู้ใช้ต้องเข้าระบบอย่างน้อย 1 ครั้งและได้รับอนุมัติก่อน)</p>
                <?php else: ?>
                    <?php $form = ActiveForm::begin(['layout' => 'inline', 'options' => ['class' => 'row g-2']]); ?>
                    <div class="col-md-7"><?= Html::activeDropDownList($member, 'user_id', $candidates, ['class' => 'form-select', 'prompt' => '— เลือกผู้ใช้ —']) ?></div>
                    <div class="col-md-3"><?= Html::activeDropDownList($member, 'role', array_diff_key(ProjectMember::roleOptions(), [ProjectMember::ROLE_OWNER => 1]), ['class' => 'form-select']) ?></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-primary">เพิ่ม</button></div>
                    <?php if ($member->hasErrors()): ?>
                        <div class="col-12 text-danger small"><?= Html::encode(implode(' ', $member->getFirstErrors())) ?></div>
                    <?php endif ?>
                    <?php ActiveForm::end(); ?>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">กลุ่มผู้ให้ข้อมูล</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($project->groups as $g): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= Html::encode($g->name) ?>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete-group', 'id' => $project->id, 'groupId' => $g->id], [
                            'class' => 'btn btn-sm btn-link text-danger',
                            'data' => ['method' => 'post', 'confirm' => 'ลบกลุ่มนี้? (ผู้ให้ข้อมูลในกลุ่มจะไม่ถูกลบ)'],
                        ]) ?>
                    </li>
                <?php endforeach ?>
                <?php if (!$project->groups): ?>
                    <li class="list-group-item text-muted">ยังไม่มีกลุ่ม</li>
                <?php endif ?>
            </ul>
            <div class="card-body border-top">
                <?php $form = ActiveForm::begin(['options' => ['class' => 'd-flex gap-2']]); ?>
                <?= Html::activeTextInput($group, 'name', ['class' => 'form-control', 'placeholder' => 'เช่น ผู้ป่วย, ญาติ, เจ้าหน้าที่']) ?>
                <button class="btn btn-primary text-nowrap">เพิ่มกลุ่ม</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
