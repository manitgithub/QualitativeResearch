<?php

use yii\db\Migration;

/**
 * โครงสร้างฐานข้อมูลใหม่ (แทน dt_* ของระบบเดิม)
 *
 * ตารางเดิม → ตารางใหม่
 *   dt_userkey      → user (+ user_auth สำหรับ Google/LINE)
 *   dt_log          → login_log
 *   dt_main         → project
 *   dt_join         → project_member
 *   dt_information  → collection_method
 *   dt_group        → informant_group
 *   dt_qution       → question
 *   dt_anwer        → question_option
 *   dt_people       → informant
 *   dt_interview    → response
 *   dt_interpret    → interpretation
 *   dt_conceptual   → concept
 *   dt_typequtionและ dt_typegution → ค่าคงที่ใน model
 *
 * ทุกตารางที่รับข้อมูลจากระบบเดิมมีคอลัมน์ legacy_id ไว้อ้างอิงกลับ
 */
class m261109_000001_init_schema extends Migration
{
    public function safeUp()
    {
        $opts = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        // ---------- ผู้ใช้ ----------
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->unique()->comment('ชื่อเข้าระบบ (null ได้ถ้าใช้ Google/LINE อย่างเดียว)'),
            'email' => $this->string(255)->unique(),
            'prefix' => $this->string(50)->comment('คำนำหน้า'),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100),
            'password_hash' => $this->string(255),
            'legacy_password' => $this->string(255)->comment('รหัสผ่านเดิม (plain text) จะถูกแปลงเป็น hash และลบทิ้งเมื่อเข้าระบบครั้งแรก'),
            'auth_key' => $this->string(32)->notNull(),
            'role' => $this->string(20)->notNull()->defaultValue('user')->comment('admin/user'),
            'status' => $this->smallInteger()->notNull()->defaultValue(10)->comment('10=ใช้งาน, 5=รออนุมัติ, 0=ระงับ'),
            'avatar_url' => $this->string(500),
            'last_login_at' => $this->integer(),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);

        $this->createTable('{{%user_auth}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'source' => $this->string(20)->notNull()->comment('google/line'),
            'source_id' => $this->string(191)->notNull(),
            'email' => $this->string(255),
            'display_name' => $this->string(255),
            'created_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('ux-user_auth-source', '{{%user_auth}}', ['source', 'source_id'], true);
        $this->createIndex('ux-user_auth-user_source', '{{%user_auth}}', ['user_id', 'source'], true);
        $this->addForeignKey('fk-user_auth-user', '{{%user_auth}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->createTable('{{%login_log}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->integer(),
            'method' => $this->string(20)->notNull()->comment('password/google/line'),
            'ip' => $this->string(45),
            'user_agent' => $this->string(500),
            'created_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('idx-login_log-user', '{{%login_log}}', ['user_id', 'created_at']);
        $this->addForeignKey('fk-login_log-user', '{{%login_log}}', 'user_id', '{{%user}}', 'id', 'SET NULL');

        // ---------- โครงการวิจัย ----------
        $this->createTable('{{%project}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(500)->notNull(),
            'description' => $this->text(),
            'started_on' => $this->date(),
            'owner_id' => $this->integer()->notNull(),
            'is_archived' => $this->boolean()->notNull()->defaultValue(false),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->addForeignKey('fk-project-owner', '{{%project}}', 'owner_id', '{{%user}}', 'id', 'RESTRICT');

        $this->createTable('{{%project_member}}', [
            'project_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'role' => $this->string(20)->notNull()->defaultValue('member')->comment('owner/member/viewer'),
            'created_at' => $this->integer()->notNull(),
        ], $opts);
        $this->addPrimaryKey('pk-project_member', '{{%project_member}}', ['project_id', 'user_id']);
        $this->addForeignKey('fk-project_member-project', '{{%project_member}}', 'project_id', '{{%project}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-project_member-user', '{{%project_member}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        // ---------- ข้อมูลอ้างอิง ----------
        $this->createTable('{{%collection_method}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'legacy_id' => $this->integer()->unique(),
        ], $opts);

        $this->createTable('{{%informant_group}}', [
            'id' => $this->primaryKey(),
            'project_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'legacy_id' => $this->integer()->unique(),
        ], $opts);
        $this->addForeignKey('fk-informant_group-project', '{{%informant_group}}', 'project_id', '{{%project}}', 'id', 'CASCADE');

        // ---------- คำถาม ----------
        $this->createTable('{{%question}}', [
            'id' => $this->primaryKey(),
            'project_id' => $this->integer()->notNull(),
            'category' => $this->string(20)->notNull()->defaultValue('research')->comment('research=คำถามการวิจัย, demographic=ข้อมูลพื้นฐาน'),
            'answer_type' => $this->string(20)->notNull()->defaultValue('text')->comment('text/choice/boolean'),
            'title' => $this->text()->notNull(),
            'detail' => $this->text(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
            'created_by' => $this->integer(),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('idx-question-project', '{{%question}}', ['project_id', 'category', 'is_deleted', 'sort_order']);
        $this->addForeignKey('fk-question-project', '{{%question}}', 'project_id', '{{%project}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-question-created_by', '{{%question}}', 'created_by', '{{%user}}', 'id', 'SET NULL');

        $this->createTable('{{%question_option}}', [
            'id' => $this->primaryKey(),
            'question_id' => $this->integer()->notNull(),
            'label' => $this->string(500)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'legacy_id' => $this->integer()->unique(),
        ], $opts);
        $this->addForeignKey('fk-question_option-question', '{{%question_option}}', 'question_id', '{{%question}}', 'id', 'CASCADE');

        // ---------- ผู้ให้ข้อมูลและคำตอบ ----------
        $this->createTable('{{%informant}}', [
            'id' => $this->primaryKey(),
            'project_id' => $this->integer()->notNull(),
            'code' => $this->string(100)->notNull()->comment('รหัสผู้ให้ข้อมูล'),
            'age' => $this->smallInteger(),
            'group_id' => $this->integer(),
            'collection_method_id' => $this->integer(),
            'interviewed_on' => $this->date(),
            'note' => $this->text(),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
            'created_by' => $this->integer(),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('idx-informant-project', '{{%informant}}', ['project_id', 'is_deleted', 'code']);
        $this->addForeignKey('fk-informant-project', '{{%informant}}', 'project_id', '{{%project}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-informant-group', '{{%informant}}', 'group_id', '{{%informant_group}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-informant-method', '{{%informant}}', 'collection_method_id', '{{%collection_method}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-informant-created_by', '{{%informant}}', 'created_by', '{{%user}}', 'id', 'SET NULL');

        $this->createTable('{{%response}}', [
            'id' => $this->primaryKey(),
            'informant_id' => $this->integer()->notNull(),
            'question_id' => $this->integer()->notNull(),
            'value_text' => $this->text()->comment('คำตอบแบบข้อความ / ใช่-ไม่ใช่ (1/0)'),
            'option_id' => $this->integer()->comment('คำตอบแบบตัวเลือก'),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
            'created_by' => $this->integer(),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('idx-response-question', '{{%response}}', ['question_id', 'is_deleted']);
        $this->createIndex('idx-response-informant', '{{%response}}', ['informant_id', 'question_id']);
        $this->addForeignKey('fk-response-informant', '{{%response}}', 'informant_id', '{{%informant}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-response-question', '{{%response}}', 'question_id', '{{%question}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-response-option', '{{%response}}', 'option_id', '{{%question_option}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-response-created_by', '{{%response}}', 'created_by', '{{%user}}', 'id', 'SET NULL');

        // ---------- การตีความ / ยกประเด็น ----------
        $this->createTable('{{%interpretation}}', [
            'id' => $this->primaryKey(),
            'question_id' => $this->integer()->notNull(),
            'response_id' => $this->integer()->comment('null ได้สำหรับการสังเคราะห์ระดับคำถาม (รอบ 4)'),
            'round' => $this->smallInteger()->notNull()->defaultValue(1)->comment('1=สรุปประเด็น 2=ยกประเด็นรอบ1 3=ยกประเด็นรอบ2 4=สังเคราะห์'),
            'ipo' => $this->string(10)->comment('input/process/output'),
            'text' => $this->text()->notNull(),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
            'created_by' => $this->integer(),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->createIndex('idx-interpretation-question', '{{%interpretation}}', ['question_id', 'round', 'is_deleted']);
        $this->createIndex('idx-interpretation-response', '{{%interpretation}}', ['response_id', 'round']);
        $this->addForeignKey('fk-interpretation-question', '{{%interpretation}}', 'question_id', '{{%question}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-interpretation-response', '{{%interpretation}}', 'response_id', '{{%response}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-interpretation-created_by', '{{%interpretation}}', 'created_by', '{{%user}}', 'id', 'SET NULL');

        // ---------- Conceptual map ----------
        $this->createTable('{{%concept}}', [
            'id' => $this->primaryKey(),
            'project_id' => $this->integer()->notNull(),
            'name' => $this->text()->notNull(),
            'is_input' => $this->boolean()->notNull()->defaultValue(false),
            'is_process' => $this->boolean()->notNull()->defaultValue(false),
            'is_output' => $this->boolean()->notNull()->defaultValue(false),
            'is_problem' => $this->boolean()->notNull()->defaultValue(false),
            'is_success' => $this->boolean()->notNull()->defaultValue(false),
            'legacy_id' => $this->integer()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $opts);
        $this->addForeignKey('fk-concept-project', '{{%concept}}', 'project_id', '{{%project}}', 'id', 'CASCADE');

        // ค่าเริ่มต้น
        $this->batchInsert('{{%collection_method}}', ['name', 'sort_order'], [
            ['สัมภาษณ์เชิงลึก', 1],
            ['สนทนากลุ่ม', 2],
            ['แบบสอบถามปลายเปิด', 3],
            ['สังเกต', 4],
        ]);
    }

    public function safeDown()
    {
        foreach (['concept', 'interpretation', 'response', 'informant', 'question_option', 'question',
                     'informant_group', 'collection_method', 'project_member', 'project', 'login_log',
                     'user_auth', 'user'] as $t) {
            $this->dropTable('{{%' . $t . '}}');
        }
    }
}
