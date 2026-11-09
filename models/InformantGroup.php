<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * กลุ่มผู้ให้ข้อมูล (เดิม dt_group)
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int|null $legacy_id
 */
class InformantGroup extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%informant_group}}';
    }

    public function rules()
    {
        return [
            [['project_id', 'name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return ['name' => 'ชื่อกลุ่ม'];
    }

    public static function options(int $projectId): array
    {
        return ArrayHelper::map(static::find()->where(['project_id' => $projectId])->orderBy('name')->all(), 'id', 'name');
    }
}
