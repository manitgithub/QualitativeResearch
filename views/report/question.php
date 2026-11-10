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
/** @var array $suggestions [response_id][round] => AiSuggestion[] */
/** @var bool $aiReady */

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
$aiReady = $aiReady && $canEdit;
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
                    <?php
                    $pendingByRound = [];
                    foreach (array_merge(array_map(fn($x) => $x->id, $block['responses']), ['q' . $question->id]) as $key) {
                        foreach ($suggestions[$key] ?? [] as $sr => $list) {
                            $pendingByRound[$sr] = ($pendingByRound[$sr] ?? 0) + count($list);
                        }
                    }
                    ?>
                    <?php if ($canEdit): foreach ($pendingByRound as $sr => $n): ?>
                        <span class="ai-pending-bar no-print">
                            <i class="bi bi-stars"></i> AI เสนอ <?= $n ?> ประเด็น (<?= $rounds[$sr] ?>)
                            <?= Html::a('รับทั้งหมด', ['ai/accept-all', 'questionId' => $question->id, 'round' => $sr],
                                ['class' => 'btn btn-sm btn-success py-0', 'data' => ['method' => 'post', 'confirm' => "รับข้อเสนอของ AI ทั้ง {$n} ประเด็น?"]]) ?>
                            <?= Html::a('ปฏิเสธทั้งหมด', ['ai/reject-all', 'questionId' => $question->id, 'round' => $sr],
                                ['class' => 'btn btn-sm btn-outline-secondary py-0', 'data' => ['method' => 'post', 'confirm' => "ปฏิเสธข้อเสนอของ AI ทั้ง {$n} ประเด็น?"]]) ?>
                        </span> ·
                    <?php endforeach; endif ?>
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

        <?php $synthPending = $suggestions['q' . $question->id][4] ?? []; ?>
        <?php if ($canEdit && $round === 4 && ($aiReady || $synthPending)): ?>
            <div class="card-body border-bottom no-print">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                    <div class="small text-muted">
                        <i class="bi bi-stars text-primary"></i> AI รวมประเด็นจากรอบ 1–3 ของคำถามนี้ให้เหลือกลุ่มน้อยที่สุด แล้วจัดเป็น Input / Process / Output
                    </div>
                    <?php if ($aiReady && $question->answer_type === \app\models\Question::TYPE_TEXT && !$synthPending): ?>
                        <?= Html::a('<i class="bi bi-stars"></i> AI สังเคราะห์ IPO', ['ai/suggest', 'questionId' => $question->id, 'round' => 4], [
                            'class' => 'btn btn-sm btn-primary ai-synth-btn',
                            'data' => ['method' => 'post', 'confirm' => 'ให้ AI สังเคราะห์ประเด็นรอบ 1–3 ของคำถามนี้เป็นกลุ่ม IPO? (ใช้เครดิตของคุณ)'],
                        ]) ?>
                    <?php endif ?>
                </div>
                <?php foreach (\app\models\Interpretation::ipoOptions() + ['' => 'ไม่ระบุ'] as $ipoKey => $ipoLabel): ?>
                    <?php $group = array_filter($synthPending, fn($sg) => (string)$sg->ipo === (string)$ipoKey); ?>
                    <?php if (!$group) continue; ?>
                    <div class="small fw-semibold mt-2"><?= $ipoLabel ?></div>
                    <?php foreach ($group as $sg): ?>
                        <div class="ai-suggestion">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <i class="bi bi-stars text-primary" title="ข้อเสนอจาก AI"></i>
                                    <b><?= Html::encode($sg->text) ?></b>
                                    <?php if ($sg->ipo): ?><span class="badge text-bg-secondary ipo-badge"><?= $sg->ipo ?></span><?php endif ?>
                                    <?php if ($m = $sg->getMemberList()): ?>
                                        <div class="small text-muted mt-1">รวมจาก <?= count($m) ?> ประเด็น: <?= Html::encode(implode(' · ', $m)) ?></div>
                                    <?php endif ?>
                                </div>
                                <div class="text-nowrap">
                                    <?= Html::a('<i class="bi bi-check-lg"></i>', ['ai/accept', 'id' => $sg->id], ['class' => 'text-success', 'title' => 'รับ', 'data-method' => 'post']) ?>
                                    <?= Html::a('<i class="bi bi-x-lg"></i>', ['ai/reject', 'id' => $sg->id], ['class' => 'text-danger ms-1', 'title' => 'ปฏิเสธ', 'data-method' => 'post']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
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
                        <th style="min-width: 220px; width: <?= $round > 2 ? 20 : 28 ?>%">
                            <?= $rounds[$r] ?>
                            <?php if ($aiReady && $r <= \app\components\ai\InterpretationSuggester::MAX_ROUND && $question->answer_type === \app\models\Question::TYPE_TEXT): ?>
                                <?= Html::a('<i class="bi bi-stars"></i> AI ช่วย', ['ai/suggest', 'questionId' => $question->id, 'round' => $r], [
                                    'class' => 'btn btn-sm btn-outline-primary py-0 ms-1 no-print ai-suggest-btn',
                                    'title' => $r === 1 ? 'ให้ AI สรุปประเด็นจากคำตอบที่ยังไม่มีประเด็น (ใช้เครดิต)' : 'ให้ AI ยกประเด็นจากรอบก่อนหน้า (ใช้เครดิต)',
                                    'data' => ['method' => 'post', 'confirm' => 'ให้ AI เสนอประเด็น ' . $rounds[$r] . ' ของคำถามนี้? (ใช้เครดิตของคุณ, ทำครั้งละไม่เกิน ' . (int)(Yii::$app->params['ai.batchSize'] ?? 10) . ' คำตอบ อาจใช้เวลาครึ่งนาที)'],
                                ]) ?>
                            <?php endif ?>
                        </th>
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
                                <?php if ($canEdit): foreach ($suggestions[$resp->id][$r] ?? [] as $sg): ?>
                                    <div class="ai-suggestion no-print">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <i class="bi bi-stars text-primary" title="ข้อเสนอจาก AI"></i>
                                                <?= Html::encode($sg->text) ?>
                                                <?php if ($sg->ipo): ?><span class="badge text-bg-secondary ipo-badge"><?= $sg->ipo ?></span><?php endif ?>
                                            </div>
                                            <div class="text-nowrap">
                                                <?= Html::a('<i class="bi bi-check-lg"></i>', ['ai/accept', 'id' => $sg->id], ['class' => 'text-success', 'title' => 'รับ', 'data-method' => 'post']) ?>
                                                <?= Html::a('<i class="bi bi-x-lg"></i>', ['ai/reject', 'id' => $sg->id], ['class' => 'text-danger ms-1', 'title' => 'ปฏิเสธ', 'data-method' => 'post']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; endif ?>
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
