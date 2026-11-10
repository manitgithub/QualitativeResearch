<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * บัญชี Google/LINE ที่ผูกกับผู้ใช้
 *
 * @property int $id
 * @property bool $flagDel  1 = ลบแล้ว (ซ่อน)
 * @property int $user_id
 * @property string $source
 * @property string $source_id
 * @property string|null $email
 * @property string|null $display_name
 * @property int $created_at
 *
 * @property User $user
 */
class UserAuth extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%user_auth}}';
    }

    public function behaviors()
    {
        return [['class' => TimestampBehavior::class, 'updatedAtAttribute' => false]];
    }

    public function rules()
    {
        return [
            [['user_id', 'source', 'source_id'], 'required'],
            [['source', 'source_id'], 'unique', 'targetAttribute' => ['source', 'source_id']],
            [['email', 'display_name'], 'string', 'max' => 255],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function sourceLabels(): array
    {
        return ['google' => 'Google', 'line' => 'LINE'];
    }
}
