<?php

namespace app\models\forms;

use app\models\Informant;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use Yii;
use yii\base\Model;

/**
 * ฟอร์มบันทึกการสัมภาษณ์: ผู้ให้ข้อมูล + คำตอบทุกข้อ + สรุปประเด็น (รอบ 1)
 * บันทึกทั้งหมดใน transaction เดียว
 */
class InterviewForm extends Model
{
    public Project $project;
    public Informant $informant;

    /** @var Question[] */
    public array $questions = [];

    /** @var array<int,string> question_id => ค่าคำตอบ */
    public array $answers = [];

    /** @var array<int,array<int,array{text:string,ipo:?string}>> question_id => รายการสรุปประเด็นใหม่ */
    public array $interpretations = [];

    /** @var int|null จำกัดเฉพาะคำถามข้อเดียว (เพิ่มคำตอบรายข้อ) */
    public ?int $onlyQuestionId = null;

    public function __construct(Project $project, ?Informant $informant = null, ?int $onlyQuestionId = null, $config = [])
    {
        $this->project = $project;
        $this->onlyQuestionId = $onlyQuestionId;
        $this->informant = $informant ?? new Informant([
            'project_id' => $project->id,
            'interviewed_on' => date('Y-m-d'),
        ]);

        $q = Question::find()
            ->with('options')
            ->where(['project_id' => $project->id, 'is_deleted' => 0])
            ->orderBy(['category' => SORT_ASC, 'sort_order' => SORT_ASC, 'id' => SORT_ASC]);
        if ($onlyQuestionId) {
            $q->andWhere(['id' => $onlyQuestionId]);
        }
        // demographic ก่อน research (เรียงตาม category desc: research < demographic ตามตัวอักษร)
        $all = $q->all();
        usort($all, fn($a, $b) => [$a->category === Question::CATEGORY_RESEARCH, $a->sort_order, $a->id]
            <=> [$b->category === Question::CATEGORY_RESEARCH, $b->sort_order, $b->id]);
        $this->questions = $all;

        if (!$this->informant->isNewRecord) {
            foreach ($this->informant->responses as $r) {
                $this->answers[$r->question_id] = $r->option_id !== null ? (string)$r->option_id : (string)$r->value_text;
            }
        }

        parent::__construct($config);
    }

    public function load($data, $formName = null)
    {
        $ok = $this->informant->load($data);
        $this->answers = array_map(fn($v) => is_array($v) ? '' : trim((string)$v), (array)($data['answers'] ?? []));
        $this->interpretations = (array)($data['interpretations'] ?? []);
        return $ok;
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        $ok = $this->informant->validate();

        $dup = Informant::find()
            ->where(['project_id' => $this->project->id, 'code' => $this->informant->code, 'is_deleted' => 0])
            ->andFilterWhere(['<>', 'id', $this->informant->id])
            ->exists();
        if ($dup) {
            $this->informant->addError('code', 'รหัสผู้ให้ข้อมูลนี้มีอยู่แล้วในโครงการ');
            $ok = false;
        }
        return $ok;
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            if (!$this->informant->save(false)) {
                throw new \RuntimeException('บันทึกผู้ให้ข้อมูลไม่สำเร็จ');
            }

            $existing = Response::find()
                ->where(['informant_id' => $this->informant->id, 'is_deleted' => 0])
                ->indexBy('question_id')
                ->all();

            foreach ($this->questions as $question) {
                $raw = $this->answers[$question->id] ?? '';
                $response = $existing[$question->id] ?? null;

                [$valueText, $optionId] = $this->normalize($question, $raw);
                $isEmpty = $valueText === null && $optionId === null;

                if ($isEmpty) {
                    // ไม่ได้ตอบ: ถ้าเคยมีคำตอบอยู่ (กรณีแก้ไข) ให้ลบแบบ soft delete
                    if ($response) {
                        $response->softDelete();
                    }
                    continue;
                }

                if (!$response) {
                    $response = new Response([
                        'informant_id' => $this->informant->id,
                        'question_id' => $question->id,
                    ]);
                }
                $response->value_text = $valueText;
                $response->option_id = $optionId;
                $response->save(false);

                foreach ((array)($this->interpretations[$question->id] ?? []) as $item) {
                    $text = trim((string)($item['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    $ipo = $item['ipo'] ?? null;
                    $i = new Interpretation([
                        'question_id' => $question->id,
                        'response_id' => $response->id,
                        'round' => 1,
                        'text' => $text,
                        'ipo' => isset(Interpretation::ipoOptions()[$ipo]) ? $ipo : null,
                    ]);
                    $i->save(false);
                }
            }

            $tx->commit();
            return true;
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e, __METHOD__);
            $this->informant->addError('code', 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{0:?string,1:?int} [value_text, option_id]
     */
    protected function normalize(Question $q, string $raw): array
    {
        switch ($q->answer_type) {
            case Question::TYPE_CHOICE:
                if ($raw === '') {
                    return [null, null];
                }
                $valid = QuestionOption::find()->where(['id' => (int)$raw, 'question_id' => $q->id])->exists();
                return $valid ? [null, (int)$raw] : [null, null];
            case Question::TYPE_BOOLEAN:
                return in_array($raw, ['0', '1'], true) ? [$raw, null] : [null, null];
            default:
                return $raw === '' ? [null, null] : [$raw, null];
        }
    }
}
