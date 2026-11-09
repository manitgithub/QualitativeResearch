<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var array<string,app\models\Question[]> $grouped */
/** @var bool $canEdit */

use app\models\Question;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'คำถาม';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;
$p = ['projectId' => $project->id];

if ($canEdit) {
    $this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js');
    $reorderUrl = Json::htmlEncode(Url::to(['reorder'] + $p));
    $csrf = Json::htmlEncode([Yii::$app->request->csrfParam => Yii::$app->request->csrfToken]);
    $this->registerJs(<<<JS
document.querySelectorAll('.sortable-questions').forEach(function (list) {
    Sortable.create(list, {
        handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost',
        onEnd: function () {
            var body = new URLSearchParams($csrf);
            list.querySelectorAll('[data-id]').forEach(function (el, i) {
                body.append('ids[]', el.dataset.id);
                el.querySelector('.q-no').textContent = (i + 1) + '.';
            });
            fetch($reorderUrl, {method: 'POST', body: body});
        }
    });
});
JS);
}
?>
<div class="page-header">
    <h1>คำถาม</h1>
</div>

<?php foreach ($grouped as $category => $questions): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><?= Question::categoryOptions()[$category] ?> <span class="badge text-bg-light"><?= count($questions) ?></span></span>
            <?php if ($canEdit): ?>
                <a href="<?= Url::to(['create', 'category' => $category] + $p) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> เพิ่ม</a>
            <?php endif ?>
        </div>
        <?php if (!$questions): ?>
            <div class="card-body text-muted">ยังไม่มีคำถาม</div>
        <?php endif ?>
        <ul class="list-group list-group-flush sortable-questions">
            <?php foreach ($questions as $i => $q): ?>
                <li class="list-group-item d-flex gap-3 align-items-start" data-id="<?= $q->id ?>">
                    <?php if ($canEdit): ?><i class="bi bi-grip-vertical drag-handle pt-1" title="ลากเพื่อเรียงลำดับ"></i><?php endif ?>
                    <div class="flex-grow-1">
                        <div><span class="q-no text-muted"><?= $i + 1 ?>.</span> <?= Html::encode($q->title) ?></div>
                        <?php if ($q->detail): ?><div class="small text-muted" style="white-space: pre-wrap"><?= Html::encode($q->detail) ?></div><?php endif ?>
                        <div class="small mt-1">
                            <span class="badge text-bg-light"><?= $q->getTypeLabel() ?></span>
                            <?php if ($q->answer_type === Question::TYPE_CHOICE): ?>
                                <span class="text-muted"><?= Html::encode(implode(' / ', array_map(fn($o) => $o->label, $q->options))) ?></span>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <?php if ($category === Question::CATEGORY_RESEARCH): ?>
                            <a href="<?= Url::to(['report/question', 'questionId' => $q->id] + $p) ?>" class="btn btn-sm btn-outline-secondary" title="ดูคำตอบ"><i class="bi bi-eye"></i></a>
                        <?php endif ?>
                        <?php if ($canEdit): ?>
                            <a href="<?= Url::to(['informant/create', 'questionId' => $q->id] + $p) ?>" class="btn btn-sm btn-outline-secondary" title="บันทึกคำตอบเฉพาะข้อนี้"><i class="bi bi-mic"></i></a>
                            <a href="<?= Url::to(['update', 'id' => $q->id]) ?>" class="btn btn-sm btn-outline-secondary" title="แก้ไข"><i class="bi bi-pencil"></i></a>
                            <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $q->id], [
                                'class' => 'btn btn-sm btn-outline-danger', 'title' => 'ลบ',
                                'data' => ['method' => 'post', 'confirm' => 'ลบคำถามนี้?'],
                            ]) ?>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endforeach ?>
