<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Question[] $questions */
/** @var string|int|null $questionId */
/** @var int $round */
/** @var array $data */
/** @var string|null $q */
/** @var bool $canEdit */
/** @var yii\data\Pagination|null $pagination */

use app\models\Interpretation;
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'วิเคราะห์ตามคำถามการวิจัย';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$rounds = Interpretation::roundOptions();
$base = ['question', 'projectId' => $project->id, 'questionId' => $questionId, 'round' => $round, 'q' => $q];
$canEdit = $canEdit && !$project->is_archived;
?>
<div class="page-header no-print">
    <h1><?= $this->title ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Url::to(array_merge($base, ['export' => 1])) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i></button>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="get" action="<?= Url::to(['question', 'projectId' => $project->id]) ?>" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small text-muted mb-1">คำถาม</label>
                <select name="questionId" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $questionId === 'all' ? 'selected' : '' ?>>— ทุกข้อ —</option>
                    <?php foreach ($questions as $i => $qq): ?>
                        <option value="<?= $qq->id ?>" <?= (string)$questionId === (string)$qq->id ? 'selected' : '' ?>>
                            <?= $i + 1 ?>. <?= Html::encode(mb_strimwidth($qq->title, 0, 110, '…')) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small text-muted mb-1">แสดงถึงรอบ</label>
                <select name="round" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($rounds as $r => $label): ?>
                        <option value="<?= $r ?>" <?= $r === $round ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small text-muted mb-1">ค้นหาในคำตอบ</label>
                <input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control" placeholder="คำค้น / รหัส">
            </div>
        </form>
    </div>
</div>

<?php if (!$questions): ?>
    <div class="alert alert-info">ยังไม่มีคำถามการวิจัย</div>
<?php endif ?>

<?php foreach ($data as $block): ?>
    <?php /** @var app\models\Question $question */ $question = $block['question']; ?>
    <div class="card mb-4">
        <div class="card-header question-sticky">
            <div class="d-flex justify-content-between gap-2 flex-wrap">
                <div><?= Html::encode($question->title) ?></div>
                <div class="small text-muted text-nowrap">
                    <?= count($block['responses']) ?> คำตอบ ·
                    <a href="<?= Url::to(['wordcloud', 'projectId' => $project->id, 'questionId' => $question->id, 'round' => $round]) ?>">word cloud</a>
                </div>
            </div>
        </div>

        <?php if ($block['synth'] && $round >= 2): ?>
            <div class="card-body border-bottom">
                <div class="small text-muted mb-1">ประเด็นระดับคำถาม</div>
                <?php foreach ($block['synth'] as $it): ?>
                    <?= $this->render('_interp', ['it' => $it, 'canEdit' => $canEdit]) ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($canEdit && $round === 4): ?>
            <?php // รอบสังเคราะห์: เพิ่มประเด็นระดับคำถาม (ไม่ผูกกับคำตอบ) ?>
            <div class="card-body border-bottom bg-warning-subtle no-print">
                <?= Html::beginForm(['interpretation/create'], 'post', ['class' => 'row g-2']) ?>
                <?= Html::hiddenInput('Interpretation[question_id]', $question->id) ?>
                <?= Html::hiddenInput('Interpretation[round]', 4) ?>
                <div class="col"><input name="Interpretation[text]" class="form-control" placeholder="ประเด็นสังเคราะห์ระดับคำถาม"></div>
                <div class="col-auto"><?= Html::dropDownList('Interpretation[ipo]', null, Interpretation::ipoOptions(), ['class' => 'form-select', 'prompt' => '— IPO —']) ?></div>
                <div class="col-auto"><button class="btn btn-warning">เพิ่ม</button></div>
                <?= Html::endForm() ?>
            </div>
        <?php endif ?>

        <div class="table-responsive">
            <table class="table analysis-table mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width: 110px">ผู้ให้ข้อมูล</th>
                    <th>ข้อมูลที่ได้</th>
                    <?php for ($r = 1; $r <= $round; $r++): ?>
                        <th style="min-width: 220px; width: <?= $round > 2 ? 20 : 28 ?>%"><?= $rounds[$r] ?></th>
                    <?php endfor ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($block['responses'] as $resp): ?>
                    <tr id="r<?= $resp->id ?>">
                        <td>
                            <a href="<?= Url::to(['informant/view', 'id' => $resp->informant_id]) ?>" class="fw-semibold"><?= Html::encode($resp->informant->code) ?></a>
                        </td>
                        <td class="answer" style="min-width: 280px"><?= Html::encode($resp->getDisplayValue()) ?></td>
                        <?php for ($r = 1; $r <= $round; $r++): ?>
                            <td>
                                <?php foreach ($resp->interpretations as $it): ?>
                                    <?php if ((int)$it->round === $r): ?>
                                        <?= $this->render('_interp', ['it' => $it, 'canEdit' => $canEdit]) ?>
                                    <?php endif ?>
                                <?php endforeach ?>
                                <?php if ($canEdit): ?>
                                    <a href="#" class="small no-print" data-interp-add="<?= $resp->id ?>" data-round="<?= $r ?>"><i class="bi bi-plus"></i> เพิ่ม</a>
                                <?php endif ?>
                            </td>
                        <?php endfor ?>
                    </tr>
                <?php endforeach ?>
                <?php if (!$block['responses']): ?>
                    <tr><td colspan="<?= 2 + $round ?>" class="text-center text-muted py-4">ยังไม่มีคำตอบ</td></tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach ?>

<?php if ($pagination && $pagination->pageCount > 1): ?>
    <div class="no-print d-flex align-items-center gap-3 flex-wrap">
        <?= LinkPager::widget(['pagination' => $pagination]) ?>
        <span class="small text-muted mb-3">หน้าละ 3 คำถาม · ส่งออก Excel ได้ครบทุกข้อ</span>
    </div>
<?php endif ?>

<?php if ($canEdit): ?>
    <?= $this->render('_interp_templates') ?>
<?php endif ?>
