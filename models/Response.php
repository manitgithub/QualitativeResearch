<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * คำตอบของผู้ให้ข้อมูลต่อคำถาม 1 ข้อ (เดิม dt_interview)
 *
 * @property int $id
 * @property int $informant_id
 * @property int $question_id
 * @property string|null $value_text
 * @property int|null $option_id
 * @property bool $flagDel
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Informant $informant
 * @property Question $question
 * @property QuestionOption|null $option
 * @property Interpretation[] $interpretations
 */
class Response extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%response}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            ['class' => BlameableBehavior::class, 'updatedByAttribute' => false, 'preserveNonEmptyValues' => true],
        ];
    }

    public function rules()
    {
        return [
            [['informant_id', 'question_id'], 'required'],
            [['value_text'], 'string'],
            [['option_id'], 'integer'],
            [['option_id'], 'default', 'value' => null],
            ['option_id', 'exist', 'targetClass' => QuestionOption::class,
                'targetAttribute' => ['option_id' => 'id', 'question_id' => 'question_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'value_text' => 'คำตอบ',
            'option_id' => 'ตัวเลือก',
        ];
    }

    public function getInformant()
    {
        return $this->hasOne(Informant::class, ['id' => 'informant_id']);
    }

    public function getQuestion()
    {
        return $this->hasOne(Question::class, ['id' => 'question_id']);
    }

    public function getOption()
    {
        return $this->hasOne(QuestionOption::class, ['id' => 'option_id']);
    }

    public function getInterpretations()
    {
        return $this->hasMany(Interpretation::class, ['response_id' => 'id'])
            ->andOnCondition(['interpretation.flagDel' => 0])
            ->orderBy(['interpretation.round' => SORT_ASC, 'interpretation.id' => SORT_ASC]);
    }

    /**
     * ค่าที่แสดงผล ตามรูปแบบคำถาม
     */
    public function getDisplayValue(): string
    {
        $type = $this->question->answer_type ?? Question::TYPE_TEXT;
        if ($type === Question::TYPE_CHOICE) {
            return $this->option->label ?? (string)$this->value_text;
        }
        if ($type === Question::TYPE_BOOLEAN) {
            return $this->value_text === '1' ? 'ใช่' : 'ไม่ใช่';
        }
        return (string)$this->value_text;
    }

}
