<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string|null $username
 * @property string|null $email
 * @property string|null $prefix
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $password_hash
 * @property string|null $legacy_password
 * @property string $auth_key
 * @property string $role
 * @property int $status
 * @property string|null $avatar_url
 * @property int|null $last_login_at
 * @property int|null $legacy_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read string $fullName
 * @property-read UserAuth[] $auths
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_PENDING = 5;
    public const STATUS_ACTIVE = 10;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /** @var string|null รหัสผ่านใหม่จากฟอร์ม */
    public $newPassword;

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['first_name'], 'required'],
            [['username', 'email', 'prefix', 'first_name', 'last_name'], 'trim'],
            [['username', 'email'], 'default', 'value' => null],
            ['username', 'string', 'min' => 3, 'max' => 64],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_.\-@]+$/', 'message' => 'ใช้ได้เฉพาะ a-z, 0-9, _ . - @'],
            ['username', 'unique'],
            ['email', 'email'],
            ['email', 'unique'],
            [['prefix'], 'string', 'max' => 50],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            ['role', 'in', 'range' => array_keys(self::roleOptions())],
            ['status', 'in', 'range' => array_keys(self::statusOptions())],
            ['newPassword', 'string', 'min' => 8],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'ชื่อผู้ใช้',
            'email' => 'อีเมล',
            'prefix' => 'คำนำหน้า',
            'first_name' => 'ชื่อ',
            'last_name' => 'นามสกุล',
            'role' => 'สิทธิ์',
            'status' => 'สถานะ',
            'newPassword' => 'รหัสผ่านใหม่',
            'last_login_at' => 'เข้าระบบล่าสุด',
            'created_at' => 'สร้างเมื่อ',
        ];
    }

    public static function roleOptions(): array
    {
        return [self::ROLE_USER => 'ผู้ใช้ทั่วไป', self::ROLE_ADMIN => 'ผู้ดูแลระบบ'];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'ใช้งาน',
            self::STATUS_PENDING => 'รออนุมัติ',
            self::STATUS_INACTIVE => 'ระงับ',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert && !$this->auth_key) {
            $this->generateAuthKey();
        }
        if ($this->newPassword) {
            $this->setPassword($this->newPassword);
        }
        return true;
    }

    // ---------- IdentityInterface ----------

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    // ---------- รหัสผ่าน ----------

    public static function findByLogin(string $login): ?self
    {
        return static::find()
            ->where(['username' => $login])
            ->orWhere(['email' => $login])
            ->one();
    }

    /**
     * ตรวจรหัสผ่าน รองรับรหัสเดิมจากระบบเก่าที่เป็น plain text
     * ถ้าตรงกับรหัสเดิม จะแปลงเป็น hash ทันทีและลบรหัสเดิมออก
     */
    public function validatePassword(string $password): bool
    {
        if ($this->password_hash) {
            return Yii::$app->security->validatePassword($password, $this->password_hash);
        }
        if ($this->legacy_password !== null && $this->legacy_password !== ''
            && hash_equals($this->legacy_password, $password)) {
            $this->setPassword($password);
            $this->legacy_password = null;
            $this->save(false, ['password_hash', 'legacy_password', 'updated_at']);
            return true;
        }
        return false;
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->legacy_password = null;
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function hasPassword(): bool
    {
        return (bool)($this->password_hash || $this->legacy_password);
    }

    // ---------- ข้อมูลอื่น ----------

    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([$this->prefix, $this->first_name, $this->last_name])));
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function getAuths()
    {
        return $this->hasMany(UserAuth::class, ['user_id' => 'id']);
    }

    public function getAuthBySource(string $source): ?UserAuth
    {
        foreach ($this->auths as $auth) {
            if ($auth->source === $source) {
                return $auth;
            }
        }
        return null;
    }

    public function getProjectMemberships()
    {
        return $this->hasMany(ProjectMember::class, ['user_id' => 'id']);
    }

    public static function listActive(): array
    {
        $rows = static::find()->where(['status' => self::STATUS_ACTIVE])->orderBy('first_name')->all();
        $out = [];
        foreach ($rows as $u) {
            $out[$u->id] = $u->getFullName() . ($u->email ? " ({$u->email})" : '');
        }
        return $out;
    }
}
