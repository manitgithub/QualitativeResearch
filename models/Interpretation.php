<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * การตีความ / ยกประเด็น (เดิม dt_interpret)
 *
 * round (เดิม channel):
 *   1 = สรุปประเด็น/ตีความจากคำตอบ
 *   2 = ยกประเด็นรอบที่ 1
 *   3 = ยกประเด็นรอบที่ 2
 *   4 = สังเคราะห์ (Input-Process-Output)
 *
 * @property int $id
 * @property int $question_id
 * @property int|null $response_id
 * @property int $round
 * @property string|null $ipo
 * @property string $text
 * @property string $source  human / ai / ai_edited
 * @property bool $flagDel
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Question $question
 * @property Response|null $response
 */
class Interpretation extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public const IPO_INPUT = 'input';
    public const IPO_PROCESS = 'process';
    public const IPO_OUTPUT = 'output';

    public const SOURCE_HUMAN = 'human';
    public const SOURCE_AI = 'ai';
    public const SOURCE_AI_EDITED = 'ai_edited';

    public static function tableName()
    {
        return '{{%interpretation}}';
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
            [['question_id', 'text', 'round'], 'required'],
            [['text'], 'trim'],
            [['text'], 'string'],
            ['round', 'in', 'range' => array_keys(self::roundOptions())],
            ['ipo', 'in', 'range' => array_keys(self::ipoOptions())],
            ['ipo', 'default', 'value' => null],
            ['source', 'in', 'range' => [self::SOURCE_HUMAN, self::SOURCE_AI, self::SOURCE_AI_EDITED]],
            ['source', 'default', 'value' => self::SOURCE_HUMAN],
            ['response_id', 'exist', 'targetClass' => Response::class,
                'targetAttribute' => ['response_id' => 'id', 'question_id' => 'question_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'round' => 'รอบ',
            'ipo' => 'Input / Process / Output',
            'text' => 'ประเด็น',
        ];
    }

    public static function roundOptions(): array
    {
        return [
            1 => 'สรุปประเด็น',
            2 => 'ยกประเด็น รอบ 1',
            3 => 'ยกประเด็น รอบ 2',
            4 => 'สังเคราะห์ (IPO)',
        ];
    }

    public static function ipoOptions(): array
    {
        return [
            self::IPO_INPUT => 'Input',
            self::IPO_PROCESS => 'Process',
            self::IPO_OUTPUT => 'Output',
        ];
    }

    public function isFromAi(): bool
    {
        return in_array($this->source, [self::SOURCE_AI, self::SOURCE_AI_EDITED], true);
    }

    /** แก้ข้อความประเด็นที่มาจาก AI → บันทึกว่านักวิจัยแก้แล้ว */
    public function beforeSave($insert)
    {
        if (!$insert && $this->source === self::SOURCE_AI && $this->isAttributeChanged('text')) {
            $this->source = self::SOURCE_AI_EDITED;
        }
        return parent::beforeSave($insert);
    }

    public function getQuestion()
    {
        return $this->hasOne(Question::class, ['id' => 'question_id']);
    }

    public function getResponse()
    {
        return $this->hasOne(Response::class, ['id' => 'response_id']);
    }

}
