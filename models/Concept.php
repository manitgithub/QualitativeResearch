<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * องค์ประกอบของ Conceptual map (เดิม dt_conceptual)
 *
 * @property int $id
 * @property bool $flagDel  1 = ลบแล้ว (ซ่อน)
 * @property int $project_id
 * @property string $name
 * @property bool $is_input
 * @property bool $is_process
 * @property bool $is_output
 * @property bool $is_problem
 * @property bool $is_success
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 */
class Concept extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%concept}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['project_id', 'name'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string'],
            [['is_input', 'is_process', 'is_output', 'is_problem', 'is_success'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'แนวคิด/องค์ประกอบ',
            'is_input' => 'Input',
            'is_process' => 'Process',
            'is_output' => 'Output',
            'is_problem' => 'ปัญหา/อุปสรรค',
            'is_success' => 'ปัจจัยความสำเร็จ',
        ];
    }

    public static function flagColumns(): array
    {
        return ['is_input', 'is_process', 'is_output', 'is_problem', 'is_success'];
    }
}
