<?php

use yii\db\Migration;

/**
 * ลบแบบ soft delete ทุกตาราง: คอลัมน์ flagDel (0 = ใช้งาน, 1 = ลบแล้ว) แทนการลบแถวจริง
 *
 * - 4 ตารางที่มี is_deleted อยู่แล้ว (question, informant, response, interpretation) → เปลี่ยนชื่อเป็น flagDel
 * - ตารางที่เหลือ → เพิ่ม flagDel
 * - unique index ที่ต้องไม่นับแถวที่ลบแล้ว (อีเมล/ชื่อผู้ใช้ และการผูก Google/LINE) เปลี่ยนไปใช้
 *   generated column ที่เป็น NULL เมื่อ flagDel = 1 (MySQL อนุญาต NULL ซ้ำใน unique index)
 */
class m261110_000002_add_flag_del extends Migration
{
    private const RENAME = ['question', 'informant', 'response', 'interpretation'];
    private const ADD = ['user', 'user_auth', 'login_log', 'project', 'project_member',
        'collection_method', 'informant_group', 'question_option', 'concept'];

    public function safeUp()
    {
        foreach (self::RENAME as $t) {
            $this->renameColumn('{{%' . $t . '}}', 'is_deleted', 'flagDel');
        }
        foreach (self::ADD as $t) {
            $this->addColumn('{{%' . $t . '}}', 'flagDel',
                $this->boolean()->notNull()->defaultValue(false)->comment('1 = ลบแล้ว (ซ่อน)'));
        }
        $this->createIndex('idx-project-flagDel', '{{%project}}', ['flagDel', 'is_archived']);

        // user: อีเมล/ชื่อผู้ใช้ ต้องไม่ซ้ำเฉพาะในบัญชีที่ยังไม่ถูกลบ
        $this->execute("ALTER TABLE {{%user}}
            ADD COLUMN email_uq VARCHAR(255) GENERATED ALWAYS AS (IF(flagDel = 0, email, NULL)) STORED,
            ADD COLUMN username_uq VARCHAR(64) GENERATED ALWAYS AS (IF(flagDel = 0, username, NULL)) STORED");
        $this->createIndex('ux-user-email', '{{%user}}', 'email_uq', true);
        $this->createIndex('ux-user-username', '{{%user}}', 'username_uq', true);
        $this->dropIndex('email', '{{%user}}');
        $this->dropIndex('username', '{{%user}}');

        // user_auth: บัญชี Google/LINE หนึ่งบัญชีผูกได้กับผู้ใช้เดียว และผู้ใช้หนึ่งคนผูกได้ 1 บัญชีต่อ provider
        $this->execute("ALTER TABLE {{%user_auth}}
            ADD COLUMN source_id_uq VARCHAR(191) GENERATED ALWAYS AS (IF(flagDel = 0, source_id, NULL)) STORED,
            ADD COLUMN source_uq VARCHAR(20) GENERATED ALWAYS AS (IF(flagDel = 0, source, NULL)) STORED");
        $this->createIndex('ux-user_auth-active_source', '{{%user_auth}}', ['source', 'source_id_uq'], true);
        $this->createIndex('ux-user_auth-active_user_source', '{{%user_auth}}', ['user_id', 'source_uq'], true);
        $this->dropIndex('ux-user_auth-source', '{{%user_auth}}');
        $this->dropIndex('ux-user_auth-user_source', '{{%user_auth}}');
        $this->createIndex('idx-user_auth-source', '{{%user_auth}}', ['source', 'source_id']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-user_auth-source', '{{%user_auth}}');
        $this->createIndex('ux-user_auth-user_source', '{{%user_auth}}', ['user_id', 'source'], true);
        $this->createIndex('ux-user_auth-source', '{{%user_auth}}', ['source', 'source_id'], true);
        $this->dropIndex('ux-user_auth-active_user_source', '{{%user_auth}}');
        $this->dropIndex('ux-user_auth-active_source', '{{%user_auth}}');
        $this->dropColumn('{{%user_auth}}', 'source_uq');
        $this->dropColumn('{{%user_auth}}', 'source_id_uq');

        $this->createIndex('username', '{{%user}}', 'username', true);
        $this->createIndex('email', '{{%user}}', 'email', true);
        $this->dropIndex('ux-user-username', '{{%user}}');
        $this->dropIndex('ux-user-email', '{{%user}}');
        $this->dropColumn('{{%user}}', 'username_uq');
        $this->dropColumn('{{%user}}', 'email_uq');

        $this->dropIndex('idx-project-flagDel', '{{%project}}');
        foreach (self::ADD as $t) {
            $this->dropColumn('{{%' . $t . '}}', 'flagDel');
        }
        foreach (self::RENAME as $t) {
            $this->renameColumn('{{%' . $t . '}}', 'flagDel', 'is_deleted');
        }
    }
}
