<?php

namespace app\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * คำถาม (เดิม dt_qution)
 *
 * @property int $id
 * @property int $project_id
 * @property string $category     research/demographic
 * @property string $answer_type  text/choice/boolean
 * @property string $title
 * @property string|null $detail
 * @property int $sort_order
 * @property bool $is_deleted
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Project $project
 * @property QuestionOption[] $options
 * @property Response[] $responses
 */
class Question extends ActiveRecord
{
    public const CATEGORY_RESEARCH = 'research';
    public const CATEGORY_DEMOGRAPHIC = 'demographic';

    public const TYPE_TEXT = 'text';
    public const TYPE_CHOICE = 'choice';
    public const TYPE_BOOLEAN = 'boolean';

    /** @var string ตัวเลือก (บรรทัดละ 1 ตัวเลือก) จากฟอร์ม */
    public $optionsText;

    public static function tableName()
    {
        return '{{%question}}';
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
            [['project_id', 'title', 'category', 'answer_type'], 'required'],
            [['title', 'detail'], 'trim'],
            [['title', 'detail', 'optionsText'], 'string'],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            ['category', 'in', 'range' => array_keys(self::categoryOptions())],
            ['answer_type', 'in', 'range' => array_keys(self::typeOptions())],
            ['optionsText', 'required', 'when' => fn($m) => $m->answer_type === self::TYPE_CHOICE && $m->isNewRecord,
                'whenClient' => "function(){return $('#question-answer_type').val()==='choice';}",
                'message' => 'กรุณาระบุตัวเลือกอย่างน้อย 1 รายการ'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'category' => 'หมวด',
            'answer_type' => 'รูปแบบคำตอบ',
            'title' => 'คำถาม',
            'detail' => 'คำอธิบาย/แนวคำถามย่อย',
            'sort_order' => 'ลำดับ',
            'optionsText' => 'ตัวเลือก (บรรทัดละ 1 ตัวเลือก)',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_RESEARCH => 'คำถามการวิจัย',
            self::CATEGORY_DEMOGRAPHIC => 'ข้อมูลพื้นฐาน',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_TEXT => 'ข้อความ',
            self::TYPE_CHOICE => 'เลือกตอบ',
            self::TYPE_BOOLEAN => 'ใช่ / ไม่ใช่',
        ];
    }

    /**
     * เติม optionsText จากตัวเลือกที่มีอยู่ (ใช้ก่อนแสดงฟอร์มแก้ไข)
     */
    public function loadOptionsText(): void
    {
        $this->optionsText = implode("\n", array_map(fn($o) => $o->label, $this->options));
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->answer_type === self::TYPE_CHOICE && $this->optionsText !== null) {
            $this->syncOptions();
        }
    }

    /**
     * ปรับตัวเลือกให้ตรงกับข้อความในฟอร์ม โดยไม่ลบตัวเลือกที่มีคำตอบอ้างอิงอยู่แล้ว
     * (ตัวเลือกเดิมที่ชื่อตรงกันจะคง id เดิม)
     */
    protected function syncOptions(): void
    {
        $labels = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', (string)$this->optionsText)))));
        $existing = QuestionOption::find()->where(['question_id' => $this->id])->indexBy('label')->all();

        foreach ($labels as $i => $label) {
            $opt = $existing[$label] ?? new QuestionOption(['question_id' => $this->id, 'label' => $label]);
            $opt->sort_order = $i + 1;
            $opt->save(false);
            unset($existing[$label]);
        }
        foreach ($existing as $opt) {
            if (!Response::find()->where(['option_id' => $opt->id])->exists()) {
                $opt->delete();
            }
        }
        unset($this->options);
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

    public function getOptions()
    {
        return $this->hasMany(QuestionOption::class, ['question_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getResponses()
    {
        return $this->hasMany(Response::class, ['question_id' => 'id'])
            ->andOnCondition(['response.is_deleted' => 0]);
    }

    public function getCategoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? $this->category;
    }

    public function getTypeLabel(): string
    {
        return self::typeOptions()[$this->answer_type] ?? $this->answer_type;
    }

    public function softDelete(): bool
    {
        return (bool)$this->updateAttributes(['is_deleted' => 1, 'updated_at' => time()]);
    }
}
