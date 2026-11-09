<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * จัดการผู้ใช้จาก command line
 */
class UserController extends Controller
{
    /**
     * สร้าง/ตั้งรหัสผ่านผู้ดูแลระบบ:  ./yii user/admin <username> [email]
     * ระบบจะถามรหัสผ่านแบบไม่แสดงบนจอ
     */
    public function actionAdmin(string $username, ?string $email = null): int
    {
        $user = User::findOne(['username' => $username]) ?? new User(['username' => $username, 'first_name' => $username]);
        if ($email) {
            $user->email = $email;
        }
        $password = $this->promptPassword();
        $user->setPassword($password);
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_ACTIVE;
        if (!$user->save()) {
            $this->stderr(implode("\n", $user->getFirstErrors()) . "\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout("ผู้ดูแลระบบ '{$username}' พร้อมใช้งาน (id={$user->id})\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function promptPassword(): string
    {
        if ($env = getenv('ADMIN_PASSWORD')) {
            return $env;
        }
        while (true) {
            $this->stdout('รหัสผ่าน (อย่างน้อย 8 ตัว): ');
            system('stty -echo');
            $p = trim((string)fgets(STDIN));
            system('stty echo');
            $this->stdout("\n");
            if (mb_strlen($p) >= 8) {
                return $p;
            }
            $this->stderr("สั้นเกินไป\n", Console::FG_RED);
        }
    }
}
