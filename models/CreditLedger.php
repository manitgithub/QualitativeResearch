<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * ประวัติการเคลื่อนไหวเครดิต (เพิ่มอย่างเดียว) — เขียนผ่าน app\components\ai\Credits เท่านั้น
 *
 * @property int $id
 * @property int $user_id
 * @property int $delta
 * @property string $kind  grant / usage / refund / welcome
 * @property int $balance_after
 * @property int|null $ai_job_id
 * @property string|null $note
 * @property int|null $created_by
 * @property int $created_at
 *
 * @property AiJob|null $job
 * @property User|null $creator
 */
class CreditLedger extends ActiveRecord
{
    public const KIND_GRANT = 'grant';
    public const KIND_USAGE = 'usage';
    public const KIND_REFUND = 'refund';
    public const KIND_WELCOME = 'welcome';

    public static function tableName()
    {
        return '{{%credit_ledger}}';
    }

    public static function kindLabels(): array
    {
        return [
            self::KIND_GRANT => 'เติม/ปรับโดยผู้ดูแล',
            self::KIND_USAGE => 'ใช้ AI',
            self::KIND_REFUND => 'คืนเครดิต',
            self::KIND_WELCOME => 'เครดิตต้อนรับผู้ใช้ใหม่',
        ];
    }

    public function getJob()
    {
        return $this->hasOne(AiJob::class, ['id' => 'ai_job_id']);
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
