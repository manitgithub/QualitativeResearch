<?php

use yii\db\Migration;

/**
 * เลิกใช้การเข้าระบบด้วยรหัสผ่าน: เข้าได้เฉพาะ Google / LINE (อนุมัติโดยผู้ดูแลระบบ)
 * ลบคอลัมน์รหัสผ่านทั้งหมด รวมถึงรหัสผ่านเดิมแบบไม่เข้ารหัสที่ย้ายมาจากระบบเก่า
 */
class m261110_000001_drop_user_passwords extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{%user}}', 'legacy_password');
        $this->dropColumn('{{%user}}', 'password_hash');
    }

    public function safeDown()
    {
        $this->addColumn('{{%user}}', 'password_hash', $this->string(255)->after('last_name'));
        $this->addColumn('{{%user}}', 'legacy_password', $this->string(255)->after('password_hash'));
    }
}
