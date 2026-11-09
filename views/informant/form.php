<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\forms\InterviewForm $form */
/** @var array $groups */
/** @var array $methods */

use app\models\Interpretation;
use app\models\Question;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$inf = $form->informant;
$isNew = $inf->isNewRecord;
$this->title = $isNew ? 'บันทึกการสัมภาษณ์' : 'แก้ไขข้อมูล ' . $inf->code;
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = ['label' => 'ผู้ให้ข้อมูล', 'url' => ['index', 'projectId' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$sections = [Question::CATEGORY_DEMOGRAPHIC => [], Question::CATEGORY_RESEARCH => []];
foreach ($form->questions as $q) {
    $sections[$q->category][] = $q;
}
$ipoOptions = ['' => '— IPO —'] + Interpretation::ipoOptions();
?>
<div class="page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php if ($form->onlyQuestionId): ?>
        <span class="badge text-bg-info">บันทึกเฉพาะคำถามข้อเดียว</span>
    <?php endif ?>
</div>

<?php $af = ActiveForm::begin(['options' => ['style' => 'max-width: 980px']]); ?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-person-badge"></i> ผู้ให้ข้อมูล</div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-4"><?= $af->field($inf, 'code')->textInput(['autofocus' => $isNew, 'placeholder' => 'เช่น P01, A-12']) ?></div>
            <div class="col-sm-2"><?= $af->field($inf, 'age')->input('number', ['min' => 0, 'max' => 150]) ?></div>
            <div class="col-sm-3"><?= $af->field($inf, 'collection_method_id')->dropDownList($methods, ['prompt' => '—']) ?></div>
            <div class="col-sm-3"><?= $af->field($inf, 'interviewed_on')->input('date') ?></div>
            <?php if ($groups): ?>
                <div class="col-sm-4"><?= $af->field($inf, 'group_id')->dropDownList($groups, ['prompt' => '—']) ?></div>
            <?php endif ?>
            <div class="col-12"><?= $af->field($inf, 'note')->textarea(['rows' => 2, 'data-autosize' => true]) ?></div>
        </div>
    </div>
</div>

<?php foreach ($sections as $category => $questions): ?>
    <?php if (!$questions) continue; ?>
    <h2 class="h5 fw-bold mt-4 mb-3"><?= Question::categoryOptions()[$category] ?></h2>
    <?php foreach ($questions as $i => $q): ?>
        <?php $val = $form->answers[$q->id] ?? ''; $name = "answers[{$q->id}]"; ?>
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold" for="a<?= $q->id ?>"><?= $i + 1 ?>. <?= Html::encode($q->title) ?></label>
                <?php if ($q->detail): ?><div class="small text-muted mb-2" style="white-space: pre-wrap"><?= Html::encode($q->detail) ?></div><?php endif ?>

                <?php if ($q->answer_type === Question::TYPE_CHOICE): ?>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($q->options as $o): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="<?= $name ?>" id="o<?= $o->id ?>" value="<?= $o->id ?>" <?= (string)$o->id === $val ? 'checked' : '' ?>>
                                <label class="form-check-label" for="o<?= $o->id ?>"><?= Html::encode($o->label) ?></label>
                            </div>
                        <?php endforeach ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="<?= $name ?>" id="o<?= $q->id ?>-none" value="" <?= $val === '' ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="o<?= $q->id ?>-none">ไม่ระบุ</label>
                        </div>
                    </div>
                <?php elseif ($q->answer_type === Question::TYPE_BOOLEAN): ?>
                    <div class="btn-group" role="group">
                        <?php foreach (['1' => 'ใช่', '0' => 'ไม่ใช่', '' => 'ไม่ระบุ'] as $v => $label): ?>
                            <input type="radio" class="btn-check" name="<?= $name ?>" id="b<?= $q->id ?>-<?= $v ?: 'n' ?>" value="<?= $v ?>" <?= $val === (string)$v ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="b<?= $q->id ?>-<?= $v ?: 'n' ?>"><?= $label ?></label>
                        <?php endforeach ?>
                    </div>
                <?php elseif ($category === Question::CATEGORY_RESEARCH): ?>
                    <textarea class="form-control" id="a<?= $q->id ?>" name="<?= $name ?>" rows="4" data-autosize><?= Html::encode($val) ?></textarea>
                <?php else: ?>
                    <input type="text" class="form-control" id="a<?= $q->id ?>" name="<?= $name ?>" value="<?= Html::encode($val) ?>">
                <?php endif ?>

                <?php if ($isNew && $category === Question::CATEGORY_RESEARCH): ?>
                    <div class="mt-3" id="interp-<?= $q->id ?>">
                        <div class="small text-muted mb-1">สรุปประเด็น (ใส่ภายหลังได้ในหน้าวิเคราะห์)</div>
                        <div class="row g-2 mb-2 interp-row">
                            <div class="col"><input type="text" class="form-control form-control-sm" name="interpretations[<?= $q->id ?>][0][text]" placeholder="ประเด็นที่ 1"></div>
                            <div class="col-auto"><?= Html::dropDownList("interpretations[{$q->id}][0][ipo]", null, $ipoOptions, ['class' => 'form-select form-select-sm']) ?></div>
                        </div>
                        <template>
                            <div class="row g-2 mb-2 interp-row">
                                <div class="col"><input type="text" class="form-control form-control-sm" name="interpretations[<?= $q->id ?>][__i__][text]" placeholder="ประเด็นเพิ่มเติม"></div>
                                <div class="col-auto"><?= Html::dropDownList("interpretations[{$q->id}][__i__][ipo]", null, $ipoOptions, ['class' => 'form-select form-select-sm']) ?></div>
                            </div>
                        </template>
                        <a href="#" class="small" data-add-interp="interp-<?= $q->id ?>"><i class="bi bi-plus"></i> เพิ่มประเด็น</a>
                    </div>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
<?php endforeach ?>

<?php if (!$form->questions): ?>
    <div class="alert alert-warning">
        ยังไม่มีคำถามในโครงการ <a href="<?= Url::to(['question/create', 'projectId' => $project->id]) ?>">เพิ่มคำถามก่อน</a>
    </div>
<?php endif ?>

<div class="d-flex gap-2 flex-wrap position-sticky bottom-0 bg-body-tertiary py-3">
    <?= Html::submitButton('<i class="bi bi-check-lg"></i> บันทึก', ['class' => 'btn btn-primary btn-lg']) ?>
    <?php if ($isNew): ?>
        <?= Html::submitButton('บันทึกและเพิ่มคนต่อไป', ['class' => 'btn btn-outline-primary btn-lg', 'name' => 'addAnother', 'value' => 1]) ?>
    <?php endif ?>
    <a href="<?= $isNew ? Url::to(['index', 'projectId' => $project->id]) : Url::to(['view', 'id' => $inf->id]) ?>" class="btn btn-outline-secondary btn-lg">ยกเลิก</a>
</div>
<?php ActiveForm::end(); ?>
