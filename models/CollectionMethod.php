<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * วิธีการเก็บข้อมูล (เดิม dt_information)
 *
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int|null $legacy_id
 */
class CollectionMethod extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%collection_method}}';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['sort_order'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return ['name' => 'วิธีการเก็บข้อมูล', 'sort_order' => 'ลำดับ'];
    }

    public static function options(): array
    {
        return ArrayHelper::map(static::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all(), 'id', 'name');
    }
}
