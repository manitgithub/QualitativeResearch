<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * บทวิเคราะห์ในเล่มรายงาน: 1 แถวต่อคำถามการวิจัย และบทสรุปภาพรวม (question_id = NULL)
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $question_id
 * @property string $content
 * @property string $source  human / ai / ai_edited
 * @property int|null $ai_job_id
 * @property bool $flagDel
 * @property int|null $updated_by
 * @property int $created_at
 * @property int $updated_at
 */
class ReportSection extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%report_section}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            ['class' => BlameableBehavior::class, 'createdByAttribute' => false, 'updatedByAttribute' => 'updated_by'],
        ];
    }

    public function rules()
    {
        return [
            [['content'], 'trim'],
            [['content'], 'required'],
            [['content'], 'string', 'max' => 60000],
        ];
    }

    /** แก้ข้อความที่ AI เขียน → บันทึกว่านักวิจัยแก้แล้ว */
    public function beforeSave($insert)
    {
        if (!$insert && $this->source === Interpretation::SOURCE_AI && $this->isAttributeChanged('content')) {
            $this->source = Interpretation::SOURCE_AI_EDITED;
        }
        return parent::beforeSave($insert);
    }

    public static function forProject(int $projectId): array
    {
        $out = [];
        foreach (static::find()->where(['project_id' => $projectId, 'flagDel' => 0])->all() as $s) {
            $out[$s->question_id ?? 'summary'] = $s;
        }
        return $out;
    }
}
