<?php

use yii\db\Migration;

/**
 * เนื้อหาเล่มรายงาน (บทวิเคราะห์) ต่อคำถามการวิจัย + บทสรุปภาพรวม (question_id = NULL)
 */
class m261110_000005_report_section extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_section}}', [
            'id' => $this->primaryKey(),
            'project_id' => $this->integer()->notNull(),
            'question_id' => $this->integer()->comment('NULL = บทสรุปภาพรวม'),
            'content' => $this->text()->notNull(),
            'source' => $this->string(16)->notNull()->defaultValue('human')->comment('human, ai, ai_edited'),
            'ai_job_id' => $this->integer(),
            'flagDel' => $this->boolean()->notNull()->defaultValue(false)->comment('1 = ลบแล้ว (ซ่อน)'),
            'updated_by' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        $this->addForeignKey('fk-report_section-project', '{{%report_section}}', 'project_id', '{{%project}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-report_section-question', '{{%report_section}}', 'question_id', '{{%question}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-report_section-job', '{{%report_section}}', 'ai_job_id', '{{%ai_job}}', 'id', 'SET NULL');
        $this->createIndex('idx-report_section-project', '{{%report_section}}', ['project_id', 'flagDel', 'question_id']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%report_section}}');
    }
}
