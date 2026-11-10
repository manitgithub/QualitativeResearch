<?php

/**
 * @var yii\web\View $this
 * @var app\models\Project $project
 * @var app\models\Question[] $questions
 * @var array<int, app\models\Interpretation[]> $themes
 * @var array<int|string, app\models\ReportSection> $sections
 * @var bool $canEdit
 * @var bool $aiReady
 */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'เล่มรายงาน / บทวิเคราะห์';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$f = Yii::$app->formatter;

$sourceBadge = function ($sec) {
    if (!$sec) {
        return '';
    }
    $label = match ($sec->source) {
        Interpretation::SOURCE_AI => '<span class="badge text-bg-primary"><i class="bi bi-stars"></i> AI เขียน (ยังไม่แก้ไข)</span>',
        Interpretation::SOURCE_AI_EDITED => '<span class="badge text-bg-info"><i class="bi bi-stars"></i> AI เขียน · นักวิจัยแก้ไขแล้ว</span>',
        default => '<span class="badge text-bg-secondary">นักวิจัยเขียน</span>',
    };
    return $label . ' <span class="small text-muted">แก้ไขล่าสุด ' . Yii::$app->formatter->asRelativeTime($sec->updated_at) . '</span>';
};

$editor = function (string $key, $sec, string $aiLabel, string $aiConfirm) use ($project, $canEdit, $aiReady) {
    if (!$canEdit) {
        return $sec ? $this->render('_paragraphs', ['text' => $sec->content]) : '<p class="text-muted">ยังไม่มีเนื้อหา</p>';
    }
    $out = Html::beginForm(['save', 'projectId' => $project->id, 'questionId' => $key], 'post');
    $out .= Html::textarea('content', $sec->content ?? '', [
        'class' => 'form-control book-editor', 'rows' => $sec ? 14 : 4, 'data-autosize' => true,
        'placeholder' => 'เขียนบทวิเคราะห์เอง หรือกดให้ AI ร่างให้ แล้วแก้ไขต่อ',
    ]);
    $out .= '<div class="d-flex gap-2 mt-2 flex-wrap">';
    $out .= Html::submitButton('<i class="bi bi-check-lg"></i> บันทึก', ['class' => 'btn btn-sm btn-primary']);
    $out .= Html::endForm();
    if ($aiReady) {
        $out .= Html::a('<i class="bi bi-stars"></i> ' . $aiLabel, ['generate', 'projectId' => $project->id, 'questionId' => $key], [
            'class' => 'btn btn-sm btn-outline-primary ai-write-btn',
            'data' => ['method' => 'post', 'confirm' => $aiConfirm . ($sec ? "\nเนื้อหาปัจจุบันจะถูกแทนที่ (ฉบับเดิมเก็บไว้ในระบบ)" : '')],
        ]);
    }
    $out .= '</div>';
    return $out;
};
?>
<div class="page-header">
    <h1><?= $this->title ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Url::to(['export', 'projectId' => $project->id, 'format' => 'doc']) ?>" class="btn btn-outline-primary"><i class="bi bi-file-earmark-word"></i> ดาวน์โหลด Word</a>
        <a href="<?= Url::to(['export', 'projectId' => $project->id, 'format' => 'print']) ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> พิมพ์ / PDF</a>
    </div>
</div>

<div class="alert alert-light border small">
    เล่มประกอบด้วย <b>บทที่ 4 ผลการวิเคราะห์ข้อมูล</b> (ตาราง IPO จากประเด็นสังเคราะห์ + บทวิเคราะห์รายคำถาม) และ <b>บทที่ 5 สรุปผลการวิจัย</b>
    <?php if ($aiReady): ?>
        · AI เขียนจากประเด็นสังเคราะห์ (IPO) ที่รับไว้แล้ว และยกคำพูดของผู้ให้ข้อมูลพร้อมรหัสอ้างอิง <b>ต้องตรวจทานทุกครั้ง</b>
    <?php elseif ($canEdit && !$project->ai_enabled): ?>
        · ต้องการให้ AI ช่วยเขียน: หัวหน้าโครงการเปิด "อนุญาตให้ใช้ AI" ที่หน้าแก้ไขโครงการ
    <?php endif ?>
</div>

<?php if (!$questions): ?>
    <div class="alert alert-info">ยังไม่มีคำถามการวิจัย</div>
<?php endif ?>

<?php foreach ($questions as $i => $q): ?>
    <?php $sec = $sections[$q->id] ?? null; $t = $themes[$q->id] ?? []; ?>
    <div class="card mb-3" id="q<?= $q->id ?>">
        <div class="card-header d-flex justify-content-between gap-2 flex-wrap">
            <div>4.<?= $i + 1 ?> <?= Html::encode($q->title) ?></div>
            <div><?= $sourceBadge($sec) ?></div>
        </div>
        <div class="card-body">
            <?php if ($t): ?>
                <div class="small text-muted mb-1">ประเด็นสังเคราะห์ (IPO) <?= count($t) ?> ประเด็น</div>
                <?= $this->render('_ipo_table', ['themes' => $t]) ?>
            <?php else: ?>
                <div class="small text-muted mb-2">
                    ยังไม่มีประเด็นสังเคราะห์ (IPO) —
                    <a href="<?= Url::to(['report/question', 'projectId' => $project->id, 'questionId' => $q->id, 'round' => 4]) ?>">สังเคราะห์ก่อน</a>
                    จะได้บทวิเคราะห์ที่ดีกว่า (ถ้าไม่มี AI จะเขียนจากประเด็นรอบ 1–3)
                </div>
            <?php endif ?>
            <div class="mt-2">
                <?= $editor((string)$q->id, $sec, $sec ? 'AI เขียนใหม่' : 'AI เขียนบทวิเคราะห์',
                    'ให้ AI เขียนบทวิเคราะห์ของคำถามนี้? (ใช้เครดิตของคุณ)') ?>
            </div>
        </div>
    </div>
<?php endforeach ?>

<?php if ($questions): ?>
    <?php $sec = $sections['summary'] ?? null; ?>
    <div class="card mb-3 border-primary-subtle" id="summary">
        <div class="card-header d-flex justify-content-between gap-2 flex-wrap">
            <div>บทที่ 5 สรุปผลการวิจัย</div>
            <div><?= $sourceBadge($sec) ?></div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">AI สรุปจากบทวิเคราะห์รายคำถามที่บันทึกไว้ทั้งหมด (ควรเขียนบทวิเคราะห์ให้ครบก่อน)</div>
            <?= $editor('summary', $sec, $sec ? 'AI สรุปใหม่' : 'AI เขียนบทสรุป',
                'ให้ AI เขียนบทสรุปผลการวิจัยจากบทวิเคราะห์ทุกข้อ? (ใช้เครดิตของคุณ)') ?>
        </div>
    </div>
<?php endif ?>
