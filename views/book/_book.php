<?php

/**
 * เนื้อหาเล่ม (ใช้ทั้งหน้าพิมพ์และไฟล์ Word)
 *
 * @var yii\web\View $this
 * @var app\models\Project $project
 * @var app\models\Question[] $questions
 * @var array<int, app\models\Interpretation[]> $themes
 * @var array<int|string, app\models\ReportSection> $sections
 * @var string[] $team
 * @var bool $forWord
 */

use yii\helpers\Html;

$n = 0;
// วันที่แบบไทย พ.ศ. (ไม่พึ่ง intl ซึ่งบางเซิร์ฟเวอร์ไม่มี)
$months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$now = new DateTime('now', new DateTimeZone(Yii::$app->timeZone));
$thaiDate = $now->format('j') . ' ' . $months[(int)$now->format('n') - 1] . ' ' . ((int)$now->format('Y') + 543);
?>
<div class="book-cover">
    <h1 class="book-title"><?= Html::encode($project->title) ?></h1>
    <?php if ($team): ?>
        <p class="book-center">คณะผู้วิจัย<br><?= implode('<br>', array_map(fn($t) => Html::encode($t), $team)) ?></p>
    <?php endif ?>
    <p class="book-center"><?= $thaiDate ?></p>
</div>
<?php if ($forWord): ?><br clear="all" style="page-break-before:always"><?php endif ?>

<h2 class="book-chapter">บทที่ 4<br>ผลการวิเคราะห์ข้อมูล</h2>
<?php foreach ($questions as $q): ?>
    <?php $sec = $sections[$q->id] ?? null; $t = $themes[$q->id] ?? []; ?>
    <?php if (!$sec && !$t) continue; $n++; ?>
    <h3 class="book-h">4.<?= $n ?> <?= Html::encode($q->title) ?></h3>
    <?php if ($t): ?>
        <p class="book-caption">ตารางที่ 4.<?= $n ?> ประเด็นสังเคราะห์ตามกรอบ Input-Process-Output</p>
        <?= $this->render('_ipo_table', ['themes' => $t]) ?>
    <?php endif ?>
    <?php if ($sec): ?>
        <?= $this->render('_paragraphs', ['text' => $sec->content]) ?>
    <?php endif ?>
<?php endforeach ?>
<?php if (!$n): ?>
    <p class="book-p"><i>ยังไม่มีบทวิเคราะห์</i></p>
<?php endif ?>

<?php if (isset($sections['summary'])): ?>
    <?php if ($forWord): ?><br clear="all" style="page-break-before:always"><?php endif ?>
    <h2 class="book-chapter">บทที่ 5<br>สรุปผลการวิจัย</h2>
    <?= $this->render('_paragraphs', ['text' => $sections['summary']->content]) ?>
<?php endif ?>
