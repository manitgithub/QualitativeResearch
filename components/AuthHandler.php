<?php

namespace app\components;

use app\components\ai\Credits;
use app\models\LoginLog;
use app\models\User;
use app\models\UserAuth;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;

/**
 * จัดการผลลัพธ์จาก Google / LINE
 *
 * ลำดับการตัดสินใจ:
 *  1. เคยผูกบัญชีนี้ไว้แล้ว           → เข้าระบบเป็นผู้ใช้นั้น
 *  2. กำลังเข้าระบบอยู่ (หน้าโปรไฟล์)   → ผูกบัญชีเพิ่มให้ผู้ใช้ปัจจุบัน
 *  3. อีเมลตรงกับผู้ใช้ที่มีอยู่ (Google ยืนยันอีเมลแล้ว / LINE ผ่าน id_token) → ผูกให้อัตโนมัติแล้วเข้าระบบ
 *  4. ไม่พบ → สร้างผู้ใช้ใหม่ (สถานะรออนุมัติ ถ้าตั้งค่า socialSignupRequiresApproval)
 */
class AuthHandler
{
    /** @var string ข้อความที่จะแสดงให้ผู้ใช้ */
    public string $message = '';
    public bool $success = false;

    public function __construct(private ClientInterface $client)
    {
    }

    public function handle(): void
    {
        $source = $this->client->getId();
        $attrs = $this->client->getUserAttributes();

        $sourceId = (string)ArrayHelper::getValue($attrs, 'id', '');
        $email = $this->extractEmail($source, $attrs);
        $name = (string)ArrayHelper::getValue($attrs, 'name', '');
        $picture = ArrayHelper::getValue($attrs, 'picture');

        if ($sourceId === '') {
            $this->message = 'ไม่ได้รับข้อมูลบัญชีจาก ' . $this->client->getTitle();
            return;
        }

        $auth = UserAuth::find()->with('user')->where(['source' => $source, 'source_id' => $sourceId])->one();
        $webUser = Yii::$app->user;

        // 2. ผูกบัญชีให้ผู้ใช้ที่ล็อกอินอยู่
        if (!$webUser->isGuest) {
            /** @var User $current */
            $current = $webUser->identity;
            if ($auth) {
                $this->message = (int)$auth->user_id === (int)$current->id
                    ? 'บัญชีนี้ผูกไว้แล้ว'
                    : 'บัญชี ' . $this->client->getTitle() . ' นี้ถูกผูกกับผู้ใช้อื่นแล้ว';
                $this->success = (int)$auth->user_id === (int)$current->id;
                return;
            }
            if ($current->getAuthBySource($source)) {
                $this->message = 'คุณผูกบัญชี ' . $this->client->getTitle() . ' ไว้แล้ว กรุณายกเลิกบัญชีเดิมก่อน';
                return;
            }
            $this->createAuth($current, $source, $sourceId, $email, $name);
            $this->success = true;
            $this->message = 'ผูกบัญชี ' . $this->client->getTitle() . ' เรียบร้อย';
            return;
        }

        // 1. เคยผูกไว้แล้ว
        if ($auth) {
            $this->loginUser($auth->user, $source, $picture);
            return;
        }

        // 3. จับคู่ด้วยอีเมล
        $user = $email ? User::findOne(['email' => $email]) : null;

        // 4. สร้างผู้ใช้ใหม่
        if (!$user) {
            $user = $this->createUser($email, $name, $picture);
            if (!$user) {
                return;
            }
        }

        $this->createAuth($user, $source, $sourceId, $email, $name);
        $this->loginUser($user, $source, $picture);
    }

    private function extractEmail(string $source, array $attrs): ?string
    {
        $email = ArrayHelper::getValue($attrs, 'email');
        if (!$email) {
            return null;
        }
        // Google: ใช้เฉพาะอีเมลที่ยืนยันแล้ว
        if ($source === 'google' && isset($attrs['verified_email']) && !$attrs['verified_email']) {
            return null;
        }
        return mb_strtolower(trim($email));
    }

    private function loginUser(User $user, string $source, ?string $picture): void
    {
        if ($user->status === User::STATUS_PENDING) {
            $this->message = 'บัญชีของคุณ (' . ($user->email ?: $user->getFullName()) . ') รอผู้ดูแลระบบอนุมัติก่อนใช้งาน';
            return;
        }
        if ($user->status !== User::STATUS_ACTIVE) {
            $this->message = 'บัญชีนี้ถูกระงับการใช้งาน';
            return;
        }
        if ($picture && !$user->avatar_url) {
            $user->updateAttributes(['avatar_url' => $picture]);
        }
        Yii::$app->user->login($user, Yii::$app->params['user.rememberMeDuration']);
        LoginLog::record($user, $source);
        $this->success = true;
    }

    private function createUser(?string $email, string $name, ?string $picture): ?User
    {
        $parts = preg_split('/\s+/u', trim($name), 2);
        $user = new User([
            'email' => $email,
            'first_name' => $parts[0] ?: ($email ?: 'ผู้ใช้ใหม่'),
            'last_name' => $parts[1] ?? null,
            'avatar_url' => $picture,
            'role' => User::ROLE_USER,
            'status' => Yii::$app->params['socialSignupRequiresApproval'] ? User::STATUS_PENDING : User::STATUS_ACTIVE,
        ]);
        // ผู้ใช้คนแรกของระบบให้เป็น admin อัตโนมัติ
        if (!User::find()->exists()) {
            $user->role = User::ROLE_ADMIN;
            $user->status = User::STATUS_ACTIVE;
        }
        if (!$user->save()) {
            $this->message = 'สร้างบัญชีไม่สำเร็จ: ' . implode(' ', $user->getFirstErrors());
            return null;
        }
        if ((int)$user->status === User::STATUS_ACTIVE) {
            Credits::welcome($user->id);
        }
        return $user;
    }

    private function createAuth(User $user, string $source, string $sourceId, ?string $email, string $name): void
    {
        $auth = new UserAuth([
            'user_id' => $user->id,
            'source' => $source,
            'source_id' => $sourceId,
            'email' => $email,
            'display_name' => $name ?: null,
        ]);
        $auth->save(false);
        unset($user->auths);
    }
}
