<?php

use yii\db\Migration;

/**
 * AI สังเคราะห์ (IPO) ระดับคำถาม: ข้อเสนอไม่ผูกกับคำตอบใดคำตอบหนึ่ง
 * - ai_suggestion.response_id เป็น NULL ได้ (NULL = ประเด็นระดับคำถาม)
 * - ai_suggestion.members เก็บ JSON รายการประเด็นเดิมที่ถูกรวมเข้ากลุ่มนี้ ไว้ให้นักวิจัยตรวจ
 */
class m261110_000004_ai_synthesis extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('fk-ai_suggestion-response', '{{%ai_suggestion}}');
        $this->alterColumn('{{%ai_suggestion}}', 'response_id', $this->integer()->null());
        $this->addForeignKey('fk-ai_suggestion-response', '{{%ai_suggestion}}', 'response_id', '{{%response}}', 'id', 'CASCADE');
        $this->addColumn('{{%ai_suggestion}}', 'members', $this->text()->null()->comment('JSON ประเด็นเดิมที่ถูกรวม')->after('ipo'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%ai_suggestion}}', 'members');
        $this->delete('{{%ai_suggestion}}', ['response_id' => null]);
        $this->dropForeignKey('fk-ai_suggestion-response', '{{%ai_suggestion}}');
        $this->alterColumn('{{%ai_suggestion}}', 'response_id', $this->integer()->notNull());
        $this->addForeignKey('fk-ai_suggestion-response', '{{%ai_suggestion}}', 'response_id', '{{%response}}', 'id', 'CASCADE');
    }
}
