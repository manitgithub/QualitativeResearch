<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * ผู้ให้ข้อมูล (เดิม dt_people)
 *
 * @property int $id
 * @property int $project_id
 * @property string $code
 * @property int|null $age
 * @property int|null $group_id
 * @property int|null $collection_method_id
 * @property string|null $interviewed_on
 * @property string|null $note
 * @property bool $is_deleted
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Project $project
 * @property InformantGroup|null $group
 * @property CollectionMethod|null $collectionMethod
 * @property Response[] $responses
 */
class Informant extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%informant}}';
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
            [['project_id', 'code'], 'required'],
            [['code', 'note'], 'trim'],
            [['code'], 'string', 'max' => 100],
            [['note'], 'string'],
            [['age'], 'integer', 'min' => 0, 'max' => 150],
            [['interviewed_on'], 'date', 'format' => 'php:Y-m-d'],
            [['group_id', 'collection_method_id', 'age'], 'default', 'value' => null],
            ['group_id', 'exist', 'targetClass' => InformantGroup::class, 'targetAttribute' => ['group_id' => 'id', 'project_id' => 'project_id']],
            ['collection_method_id', 'exist', 'targetClass' => CollectionMethod::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'code' => 'รหัสผู้ให้ข้อมูล',
            'age' => 'อายุ',
            'group_id' => 'กลุ่ม',
            'collection_method_id' => 'วิธีการเก็บข้อมูล',
            'interviewed_on' => 'วันที่เก็บข้อมูล',
            'note' => 'บันทึกเพิ่มเติม',
            'created_at' => 'บันทึกเมื่อ',
        ];
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

    public function getGroup()
    {
        return $this->hasOne(InformantGroup::class, ['id' => 'group_id']);
    }

    public function getCollectionMethod()
    {
        return $this->hasOne(CollectionMethod::class, ['id' => 'collection_method_id']);
    }

    public function getResponses()
    {
        return $this->hasMany(Response::class, ['informant_id' => 'id'])
            ->andOnCondition(['response.is_deleted' => 0]);
    }

    public function softDelete(): bool
    {
        return (bool)$this->updateAttributes(['is_deleted' => 1, 'updated_at' => time()]);
    }
}
