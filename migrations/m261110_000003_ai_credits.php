<?php

use yii\db\Migration;

/**
 * ระบบเครดิต AI และข้อเสนอแนะการตีความจาก AI
 *
 * - credit_wallet  ยอดคงเหลือของผู้ใช้ (แก้ภายใน transaction + SELECT ... FOR UPDATE เท่านั้น)
 * - credit_ledger  ประวัติการเคลื่อนไหวเครดิต (เพิ่มอย่างเดียว ห้ามแก้/ลบ)
 * - ai_job         การเรียก AI แต่ละครั้ง: token, ค่าใช้จ่ายจริง, เครดิตที่กันไว้/ที่หักจริง
 * - ai_suggestion  ประเด็นที่ AI เสนอ รอนักวิจัยรับ/ปฏิเสธ
 * ทุกตารางมี flagDel ตามแบบแผนของระบบ
 * - interpretation.source  human / ai / ai_edited เพื่อเปิดเผยระเบียบวิธีว่าประเด็นไหนมาจาก AI
 * - project.ai_enabled     หัวหน้าโครงการต้องเปิดเองก่อนส่งข้อมูลให้ AI
 */
class m261110_000003_ai_credits extends Migration
{
    public function safeUp()
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%credit_wallet}}', [
            'user_id' => $this->integer()->notNull(),
            'balance' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
            'PRIMARY KEY (user_id)',
        ], $opts);
        $this->addForeignKey('fk-credit_wallet-user', '{{%credit_wallet}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->createTable('{{%ai_job}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'project_id' => $this->integer()->notNull(),
            'question_id' => $this->integer(),
            'feature' => $this->string(32)->notNull(),
            'round' => $this->tinyInteger(),
            'model' => $this->string(80)->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('running'),
            'item_count' => $this->integer()->notNull()->defaultValue(0),
            'credits_held' => $this->integer()->notNull()->defaultValue(0),
            'credits_charged' => $this->integer()->notNull()->defaultValue(0),
            'input_tokens' => $this->integer()->notNull()->defaultValue(0),
            'output_tokens' => $this->integer()->notNull()->defaultValue(0),
            'cost_usd' => $this->decimal(12, 6)->notNull()->defaultValue(0),
            'error' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'finished_at' => $this->integer(),
        ], $opts);
        $this->addForeignKey('fk-ai_job-user', '{{%ai_job}}', 'user_id', '{{%user}}', 'id');
        $this->addForeignKey('fk-ai_job-project', '{{%ai_job}}', 'project_id', '{{%project}}', 'id', 'CASCADE');
        $this->createIndex('idx-ai_job-user', '{{%ai_job}}', ['user_id', 'created_at']);

        $this->createTable('{{%credit_ledger}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'delta' => $this->integer()->notNull()->comment('+ เพิ่ม / - ลด'),
            'kind' => $this->string(16)->notNull()->comment('grant, usage, refund'),
            'balance_after' => $this->integer()->notNull(),
            'ai_job_id' => $this->integer(),
            'note' => $this->string(255),
            'created_by' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ], $opts);
        $this->addForeignKey('fk-credit_ledger-user', '{{%credit_ledger}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-credit_ledger-job', '{{%credit_ledger}}', 'ai_job_id', '{{%ai_job}}', 'id', 'SET NULL');
        $this->createIndex('idx-credit_ledger-user', '{{%credit_ledger}}', ['user_id', 'id']);

        $this->createTable('{{%ai_suggestion}}', [
            'id' => $this->primaryKey(),
            'ai_job_id' => $this->integer()->notNull(),
            'question_id' => $this->integer()->notNull(),
            'response_id' => $this->integer()->notNull(),
            'round' => $this->tinyInteger()->notNull(),
            'text' => $this->text()->notNull(),
            'ipo' => $this->string(16),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'interpretation_id' => $this->integer(),
            'decided_by' => $this->integer(),
            'decided_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ], $opts);
        $this->addForeignKey('fk-ai_suggestion-job', '{{%ai_suggestion}}', 'ai_job_id', '{{%ai_job}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-ai_suggestion-response', '{{%ai_suggestion}}', 'response_id', '{{%response}}', 'id', 'CASCADE');
        $this->createIndex('idx-ai_suggestion-question', '{{%ai_suggestion}}', ['question_id', 'status', 'round']);

        foreach (['credit_wallet', 'ai_job', 'credit_ledger', 'ai_suggestion'] as $t) {
            $this->addColumn('{{%' . $t . '}}', 'flagDel',
                $this->boolean()->notNull()->defaultValue(false)->comment('1 = ลบแล้ว (ซ่อน)'));
        }

        $this->addColumn('{{%interpretation}}', 'source',
            $this->string(16)->notNull()->defaultValue('human')->comment('human, ai, ai_edited')->after('text'));
        $this->addColumn('{{%project}}', 'ai_enabled',
            $this->boolean()->notNull()->defaultValue(false)->comment('หัวหน้าโครงการอนุญาตให้ส่งข้อมูลให้ AI')->after('is_archived'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%project}}', 'ai_enabled');
        $this->dropColumn('{{%interpretation}}', 'source');
        $this->dropTable('{{%ai_suggestion}}');
        $this->dropTable('{{%credit_ledger}}');
        $this->dropTable('{{%ai_job}}');
        $this->dropTable('{{%credit_wallet}}');
    }
}
