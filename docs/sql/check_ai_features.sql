-- ตรวจว่าอัปเดตฐานข้อมูล (2026-11-10_ai_features.sql) ครบหรือไม่ — อ่านอย่างเดียว ไม่แก้ข้อมูล
-- รันใน phpMyAdmin บนฐานข้อมูลของเว็บ แล้วดูคอลัมน์ status: ต้องเป็น OK ทุกแถว

SELECT x.item, IF(x.found, 'OK', 'MISSING') AS status
FROM (
          SELECT 'table credit_wallet' AS item, EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_wallet') AS found
    UNION ALL SELECT 'table ai_job', EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_job')
    UNION ALL SELECT 'table credit_ledger', EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_ledger')
    UNION ALL SELECT 'table ai_suggestion', EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_suggestion')
    UNION ALL SELECT 'table report_section', EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'report_section')
    UNION ALL SELECT 'column interpretation.source', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'interpretation' AND COLUMN_NAME = 'source')
    UNION ALL SELECT 'column project.ai_enabled', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project' AND COLUMN_NAME = 'ai_enabled')
    UNION ALL SELECT 'column concept.source', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'concept' AND COLUMN_NAME = 'source')
    UNION ALL SELECT 'column concept.ai_pending', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'concept' AND COLUMN_NAME = 'ai_pending')
    UNION ALL SELECT 'column concept.members', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'concept' AND COLUMN_NAME = 'members')
    UNION ALL SELECT 'column concept.ai_job_id', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'concept' AND COLUMN_NAME = 'ai_job_id')
    UNION ALL SELECT 'column ai_suggestion.members', EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_suggestion' AND COLUMN_NAME = 'members')
    UNION ALL SELECT 'migration rows 000003-000006 (ต้องได้ 4)', (SELECT COUNT(*) FROM migration WHERE version IN ('m261110_000003_ai_credits', 'm261110_000004_ai_synthesis', 'm261110_000005_report_section', 'm261110_000006_concept_ai')) = 4
) x;

-- ข้อมูลประกอบ: engine และชนิดคอลัมน์ id ของตารางที่ตารางใหม่อ้างอิง (ต้องเป็น InnoDB และ int)
SELECT t.TABLE_NAME, t.ENGINE, c.COLUMN_TYPE AS id_type
FROM information_schema.TABLES t
JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND c.COLUMN_NAME = 'id'
WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_NAME IN ('user', 'project', 'question', 'response');
