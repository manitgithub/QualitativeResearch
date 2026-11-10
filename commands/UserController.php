<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * จัดการผู้ใช้จาก command line (ระบบเข้าได้เฉพาะ Google / LINE จึงใช้คำสั่งนี้ตั้งผู้ดูแลคนแรก)
 */
class UserController extends Controller
{
    /**
     * ตั้งผู้ดูแลระบบด้วย Gmail
     *
     *   ./yii user/admin someone@gmail.com            สร้างผู้ดูแลใหม่ (หรือปรับบัญชีที่มีอีเมลนี้เป็นผู้ดูแล)
     *   ./yii user/admin someone@gmail.com manit      ใส่อีเมลให้บัญชีเดิม "manit" แล้วตั้งเป็นผู้ดูแล
     *
     * จากนั้นเข้าระบบด้วย Google ที่ใช้อีเมลนี้ ระบบจะผูกกับบัญชีให้อัตโนมัติ
     */
    public function actionAdmin(string $email, ?string $username = null): int
    {
        $email = mb_strtolower(trim($email));
        $byEmail = User::findOne(['email' => $email]);

        if ($username !== null) {
            $user = User::findOne(['username' => $username]);
            if (!$user) {
                $this->stderr("ไม่พบผู้ใช้ '{$username}'\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
            if ($byEmail && (int)$byEmail->id !== (int)$user->id) {
                $this->stderr("อีเมล {$email} ถูกใช้กับบัญชีอื่นแล้ว (id={$byEmail->id})\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
        } else {
            $user = $byEmail ?? new User(['first_name' => strstr($email, '@', true) ?: $email]);
        }

        $user->email = $email;
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_ACTIVE;
        if (!$user->save()) {
            $this->stderr(implode("\n", $user->getFirstErrors()) . "\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout("ผู้ดูแลระบบ: {$user->getFullName()} <{$email}> (id={$user->id})\n", Console::FG_GREEN);
        $this->stdout("เข้าระบบด้วย Gmail บัญชีนี้ แล้วระบบจะผูกให้อัตโนมัติ\n");
        return ExitCode::OK;
    }

    /**
     * ใส่อีเมลให้ผู้ใช้เดิม เพื่อให้ login ด้วย Google แล้วผูกบัญชีอัตโนมัติ:  ./yii user/email manit someone@gmail.com
     */
    public function actionEmail(string $username, string $email): int
    {
        $user = User::findOne(['username' => $username]);
        if (!$user) {
            $this->stderr("ไม่พบผู้ใช้ '{$username}'\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $user->email = mb_strtolower(trim($email));
        if (!$user->save()) {
            $this->stderr(implode("\n", $user->getFirstErrors()) . "\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout("ตั้งอีเมลของ {$user->getFullName()} เป็น {$user->email} แล้ว\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
