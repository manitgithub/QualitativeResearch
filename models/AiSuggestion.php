<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * ประเด็นที่ AI เสนอสำหรับคำตอบหนึ่งข้อ รอนักวิจัยรับหรือปฏิเสธ
 *
 * @property int $id
 * @property int $ai_job_id
 * @property int $question_id
 * @property int|null $response_id  NULL = ประเด็นระดับคำถาม (สังเคราะห์)
 * @property int $round
 * @property string $text
 * @property string|null $ipo
 * @property string|null $members  JSON ประเด็นเดิมที่ถูกรวม (สังเคราะห์)
 * @property string $status
 * @property int|null $interpretation_id
 * @property int|null $decided_by
 * @property int|null $decided_at
 * @property int $created_at
 *
 * @property Question $question
 * @property Response $response
 */
class AiSuggestion extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public static function tableName()
    {
        return '{{%ai_suggestion}}';
    }

    public function getQuestion()
    {
        return $this->hasOne(Question::class, ['id' => 'question_id']);
    }

    public function getResponse()
    {
        return $this->hasOne(Response::class, ['id' => 'response_id']);
    }

    /**
     * รับข้อเสนอ: สร้าง Interpretation (source = ai) แล้วผูกกลับมา
     */
    public function accept(int $userId): Interpretation
    {
        $it = new Interpretation([
            'question_id' => $this->question_id,
            'response_id' => $this->response_id,
            'round' => $this->round,
            'text' => $this->text,
            'ipo' => $this->ipo,
            'source' => Interpretation::SOURCE_AI,
        ]);
        if (!$it->save()) {
            throw new \yii\base\UserException(implode(' ', $it->getFirstErrors()));
        }
        $this->updateAttributes([
            'status' => self::STATUS_ACCEPTED,
            'interpretation_id' => $it->id,
            'decided_by' => $userId,
            'decided_at' => time(),
        ]);
        return $it;
    }

    /** @return string[] */
    public function getMemberList(): array
    {
        $m = $this->members ? json_decode($this->members, true) : [];
        return is_array($m) ? $m : [];
    }

    public function reject(int $userId): void
    {
        $this->updateAttributes([
            'status' => self::STATUS_REJECTED,
            'decided_by' => $userId,
            'decided_at' => time(),
        ]);
    }
}
