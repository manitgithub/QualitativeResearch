<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * ตัวเลือกคำตอบ (เดิม dt_anwer)
 *
 * @property int $id
 * @property int $question_id
 * @property string $label
 * @property int $sort_order
 * @property int|null $legacy_id
 */
class QuestionOption extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%question_option}}';
    }

    public function rules()
    {
        return [
            [['question_id', 'label'], 'required'],
            [['label'], 'string', 'max' => 500],
            [['sort_order'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return ['label' => 'ตัวเลือก'];
    }
}
