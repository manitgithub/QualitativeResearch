<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * ทีมงานในโครงการ (เดิม dt_join)
 *
 * @property int $project_id
 * @property int $user_id
 * @property string $role
 * @property int $created_at
 *
 * @property User $user
 * @property Project $project
 */
class ProjectMember extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    public static function tableName()
    {
        return '{{%project_member}}';
    }

    public function behaviors()
    {
        return [['class' => TimestampBehavior::class, 'updatedAtAttribute' => false]];
    }

    public function rules()
    {
        return [
            [['project_id', 'user_id', 'role'], 'required'],
            [['project_id', 'user_id'], 'integer'],
            ['role', 'in', 'range' => array_keys(self::roleOptions())],
            ['user_id', 'unique', 'targetAttribute' => ['project_id', 'user_id'], 'message' => 'ผู้ใช้นี้อยู่ในทีมแล้ว'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return ['user_id' => 'ผู้ใช้', 'role' => 'บทบาท'];
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_OWNER => 'หัวหน้า/ผู้จัดการ',
            self::ROLE_MEMBER => 'ทีมงาน (บันทึก/แก้ไขได้)',
            self::ROLE_VIEWER => 'ดูอย่างเดียว',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }
}
