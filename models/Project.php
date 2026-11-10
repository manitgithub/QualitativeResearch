<?php

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * หัวข้อ/โครงการวิจัย (เดิม dt_main)
 *
 * @property int $id
 * @property bool $flagDel  1 = ลบแล้ว (ซ่อน)
 * @property string $title
 * @property string|null $description
 * @property string|null $started_on
 * @property int $owner_id
 * @property bool $is_archived
 * @property bool $ai_enabled  หัวหน้าโครงการอนุญาตให้ส่งข้อมูลให้ AI
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $owner
 * @property ProjectMember[] $members
 * @property Question[] $questions
 * @property Informant[] $informants
 */
class Project extends ActiveRecord
{
    use \app\components\softdelete\SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%project}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'owner_id',
                'updatedByAttribute' => false,
                'preserveNonEmptyValues' => true,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title', 'description'], 'trim'],
            [['title'], 'string', 'max' => 500],
            [['description'], 'string'],
            [['started_on'], 'date', 'format' => 'php:Y-m-d'],
            [['is_archived', 'ai_enabled'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => 'ชื่องานวิจัย',
            'description' => 'รายละเอียด',
            'started_on' => 'วันที่เริ่ม',
            'owner_id' => 'หัวหน้าโครงการ',
            'is_archived' => 'เก็บเข้าคลัง',
            'ai_enabled' => 'อนุญาตให้ใช้ AI ช่วยวิเคราะห์',
            'created_at' => 'สร้างเมื่อ',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $m = new ProjectMember([
                'project_id' => $this->id,
                'user_id' => $this->owner_id,
                'role' => ProjectMember::ROLE_OWNER,
            ]);
            $m->save(false);
        }
    }

    public function getOwner()
    {
        return $this->hasOne(User::class, ['id' => 'owner_id']);
    }

    public function getMembers()
    {
        return $this->hasMany(ProjectMember::class, ['project_id' => 'id'])->with('user');
    }

    public function getQuestions()
    {
        return $this->hasMany(Question::class, ['project_id' => 'id'])
            ->andOnCondition(['question.flagDel' => 0])
            ->orderBy(['question.sort_order' => SORT_ASC, 'question.id' => SORT_ASC]);
    }

    public function getResearchQuestions()
    {
        return $this->getQuestions()->andOnCondition(['question.category' => Question::CATEGORY_RESEARCH]);
    }

    public function getDemographicQuestions()
    {
        return $this->getQuestions()->andOnCondition(['question.category' => Question::CATEGORY_DEMOGRAPHIC]);
    }

    public function getInformants()
    {
        return $this->hasMany(Informant::class, ['project_id' => 'id'])
            ->andOnCondition(['informant.flagDel' => 0]);
    }

    public function getGroups()
    {
        return $this->hasMany(InformantGroup::class, ['project_id' => 'id']);
    }

    public function getConcepts()
    {
        return $this->hasMany(Concept::class, ['project_id' => 'id']);
    }

    /**
     * บทบาทของผู้ใช้ในโครงการ: owner/member/viewer หรือ null ถ้าไม่ใช่สมาชิก
     * admin ถือเป็น owner ทุกโครงการ
     */
    public function roleOf(?User $user): ?string
    {
        if (!$user) {
            return null;
        }
        if ($user->isAdmin()) {
            return ProjectMember::ROLE_OWNER;
        }
        if ((int)$this->owner_id === (int)$user->id) {
            return ProjectMember::ROLE_OWNER;
        }
        return ProjectMember::find()
            ->select('role')
            ->where(['project_id' => $this->id, 'user_id' => $user->id])
            ->scalar() ?: null;
    }

    public function canView(?User $user): bool
    {
        return $this->roleOf($user) !== null;
    }

    public function canEdit(?User $user): bool
    {
        return in_array($this->roleOf($user), [ProjectMember::ROLE_OWNER, ProjectMember::ROLE_MEMBER], true);
    }

    public function canManage(?User $user): bool
    {
        return $this->roleOf($user) === ProjectMember::ROLE_OWNER;
    }

    /**
     * โครงการที่ผู้ใช้เห็นได้
     */
    public static function findVisibleTo(User $user)
    {
        $q = static::find()->with('owner');
        if (!$user->isAdmin()) {
            // ระบุชื่อตาราง เพราะผู้เรียกอาจ join ตาราง user เพิ่ม (เช่น ค้นหาตามชื่อหัวหน้าโครงการ)
            $q->where(['or',
                ['project.owner_id' => $user->id],
                ['project.id' => ProjectMember::find()->select('project_id')->where(['user_id' => $user->id])],
            ]);
        }
        return $q;
    }

    public function countInformants(): int
    {
        return (int)$this->getInformants()->count();
    }
}
