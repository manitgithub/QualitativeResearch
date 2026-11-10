<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * การเรียก AI หนึ่งครั้ง
 *
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property int|null $question_id
 * @property string $feature
 * @property int|null $round
 * @property string $model
 * @property string $status
 * @property int $item_count
 * @property int $credits_held
 * @property int $credits_charged
 * @property int $input_tokens
 * @property int $output_tokens
 * @property string $cost_usd
 * @property string|null $error
 * @property int $created_at
 * @property int|null $finished_at
 */
class AiJob extends ActiveRecord
{
    public const FEATURE_SUGGEST_INTERPRETATION = 'suggest_interpretation';
    public const FEATURE_SYNTHESIZE_IPO = 'synthesize_ipo';
    public const FEATURE_WRITE_SECTION = 'write_section';
    public const FEATURE_WRITE_SUMMARY = 'write_summary';
    public const FEATURE_CONCEPT_MAP = 'concept_map';

    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public static function tableName()
    {
        return '{{%ai_job}}';
    }

    public static function featureLabels(): array
    {
        return [
            self::FEATURE_SUGGEST_INTERPRETATION => 'AI ช่วยสรุป/ยกประเด็น',
            self::FEATURE_SYNTHESIZE_IPO => 'AI สังเคราะห์ IPO',
            self::FEATURE_WRITE_SECTION => 'AI เขียนบทวิเคราะห์',
            self::FEATURE_WRITE_SUMMARY => 'AI เขียนบทสรุป',
            self::FEATURE_CONCEPT_MAP => 'AI ช่วย Conceptual map',
        ];
    }
}
