<?php

namespace tests\unit\models;

use app\models\forms\InterviewForm;
use app\models\Informant;
use app\models\Interpretation;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use app\tests\Support\Factory;
use Yii;

class InterviewTest extends \Codeception\Test\Unit
{
    private $project;
    private $qText;
    private $qChoice;
    private $qBool;

    protected function _before()
    {
        Factory::reset();
        $owner = Factory::user();
        Yii::$app->user->login($owner);
        $this->project = Factory::project($owner);
        $this->qText = Factory::question($this->project, ['title' => 'บทบาท']);
        $this->qChoice = Factory::question($this->project, [
            'category' => Question::CATEGORY_DEMOGRAPHIC, 'answer_type' => Question::TYPE_CHOICE,
            'title' => 'เพศ', 'optionsText' => "ชาย\nหญิง",
        ]);
        $this->qBool = Factory::question($this->project, [
            'category' => Question::CATEGORY_DEMOGRAPHIC, 'answer_type' => Question::TYPE_BOOLEAN, 'title' => 'อบรม',
        ]);
    }

    private function optionId(string $label): int
    {
        return (int)QuestionOption::find()->select('id')->where(['question_id' => $this->qChoice->id, 'label' => $label])->scalar();
    }

    public function testChoiceOptionsCreatedFromText()
    {
        verify(QuestionOption::find()->where(['question_id' => $this->qChoice->id])->select('label')->orderBy('sort_order')->column())
            ->equals(['ชาย', 'หญิง']);
    }

    public function testOptionSyncKeepsUsedOptionsAndIds()
    {
        $inf = Factory::informant($this->project);
        $male = $this->optionId('ชาย');
        Factory::response($inf, $this->qChoice, null, $male);

        $q = Question::findOne($this->qChoice->id);
        $q->optionsText = "หญิง\nอื่นๆ";      // remove "ชาย" (in use), add "อื่นๆ"
        verify($q->save())->true();

        $labels = QuestionOption::find()->where(['question_id' => $q->id])->select('label')->column();
        verify($labels)->arrayContains('ชาย');      // kept because answered
        verify($labels)->arrayContains('อื่นๆ');
        verify($this->optionId('ชาย'))->equals($male);
    }

    public function testSaveInterviewWithAllAnswerTypesAndInterpretation()
    {
        $form = new InterviewForm($this->project);
        $form->load([
            'Informant' => ['code' => 'P01', 'age' => '40'],
            'answers' => [
                $this->qText->id => 'เป็นอาสาสมัคร',
                $this->qChoice->id => (string)$this->optionId('หญิง'),
                $this->qBool->id => '0',
            ],
            'interpretations' => [$this->qText->id => [['text' => 'บทบาทอาสา', 'ipo' => 'process'], ['text' => '  ']]],
        ]);
        verify($form->save())->true();

        $inf = Informant::findOne(['code' => 'P01']);
        $rs = Response::find()->where(['informant_id' => $inf->id])->indexBy('question_id')->all();
        verify($rs)->arrayCount(3);
        verify($rs[$this->qText->id]->value_text)->equals('เป็นอาสาสมัคร');
        verify($rs[$this->qChoice->id]->option_id)->equals($this->optionId('หญิง'));
        verify($rs[$this->qBool->id]->value_text)->equals('0');
        verify($rs[$this->qBool->id]->getDisplayValue())->equals('ไม่ใช่');

        $its = Interpretation::find()->where(['response_id' => $rs[$this->qText->id]->id])->all();
        verify($its)->arrayCount(1);   // blank interpretation ignored
        verify($its[0]->round)->equals(1);
        verify($its[0]->ipo)->equals('process');
    }

    public function testChoiceFromAnotherQuestionIsRejected()
    {
        $other = Factory::question($this->project, ['answer_type' => Question::TYPE_CHOICE, 'optionsText' => 'X']);
        $foreignOpt = (int)QuestionOption::find()->select('id')->where(['question_id' => $other->id])->scalar();

        $form = new InterviewForm($this->project);
        $form->load(['Informant' => ['code' => 'P02'], 'answers' => [$this->qChoice->id => (string)$foreignOpt]]);
        verify($form->save())->true();
        verify(Response::find()->where(['question_id' => $this->qChoice->id])->exists())->false();
    }

    public function testDuplicateCodeRejectedOnCreateButLegacyDuplicatesEditable()
    {
        Factory::informant($this->project, 'DUP');
        $form = new InterviewForm($this->project);
        $form->load(['Informant' => ['code' => 'DUP']]);
        verify($form->save())->false();
        verify($form->informant->getFirstError('code'))->stringContainsString('มีอยู่แล้ว');

        // legacy data can contain duplicate codes: editing one must still work
        $second = Factory::informant($this->project, 'DUP');
        $edit = new InterviewForm($this->project, Informant::findOne($second->id));
        $edit->load(['Informant' => ['code' => 'DUP', 'age' => '33'], 'answers' => [$this->qText->id => 'แก้']]);
        verify($edit->save())->true();
    }

    public function testEditingClearsRemovedAnswersAsSoftDelete()
    {
        $form = new InterviewForm($this->project);
        $form->load(['Informant' => ['code' => 'P03'], 'answers' => [$this->qText->id => 'เดิม']]);
        $form->save();
        $inf = Informant::findOne(['code' => 'P03']);

        $edit = new InterviewForm($this->project, $inf);
        verify($edit->answers[$this->qText->id])->equals('เดิม');
        $edit->load(['Informant' => ['code' => 'P03'], 'answers' => [$this->qText->id => '']]);
        verify($edit->save())->true();

        verify(Response::findOne(['informant_id' => $inf->id, 'question_id' => $this->qText->id]))->null();   // hidden
        $r = Response::find()->withDeleted()->where(['informant_id' => $inf->id, 'question_id' => $this->qText->id])->one();
        verify((int)$r->flagDel)->equals(1);                                                                 // but kept
    }

    public function testOnlyQuestionModeLimitsQuestions()
    {
        $form = new InterviewForm($this->project, null, $this->qText->id);
        verify($form->questions)->arrayCount(1);
        verify($form->questions[0]->id)->equals($this->qText->id);
    }

    public function testDemographicQuestionsComeFirst()
    {
        $form = new InterviewForm($this->project);
        verify($form->questions[0]->category)->equals(Question::CATEGORY_DEMOGRAPHIC);
        verify(end($form->questions)->category)->equals(Question::CATEGORY_RESEARCH);
    }
}
