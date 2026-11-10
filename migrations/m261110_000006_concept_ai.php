<?php

use yii\db\Migration;

/**
 * AI ช่วยสร้าง Conceptual map: แนวคิดที่ AI เสนอเก็บในตาราง concept แบบ "รอรับ" (ai_pending = 1)
 * จนกว่านักวิจัยจะกดรับ — ไม่แสดงในรายงานจนกว่าจะรับ
 */
class m261110_000006_concept_ai extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%concept}}', 'source', $this->string(16)->notNull()->defaultValue('human')->comment('human, ai, ai_edited')->after('is_success'));
        $this->addColumn('{{%concept}}', 'ai_pending', $this->boolean()->notNull()->defaultValue(false)->comment('1 = ข้อเสนอจาก AI รอนักวิจัยรับ')->after('source'));
        $this->addColumn('{{%concept}}', 'members', $this->text()->null()->comment('JSON ประเด็นที่เป็นที่มาของแนวคิด')->after('ai_pending'));
        $this->addColumn('{{%concept}}', 'ai_job_id', $this->integer()->null()->after('members'));
        $this->addForeignKey('fk-concept-job', '{{%concept}}', 'ai_job_id', '{{%ai_job}}', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-concept-job', '{{%concept}}');
        $this->dropColumn('{{%concept}}', 'ai_job_id');
        $this->dropColumn('{{%concept}}', 'members');
        $this->dropColumn('{{%concept}}', 'ai_pending');
        $this->dropColumn('{{%concept}}', 'source');
    }
}
