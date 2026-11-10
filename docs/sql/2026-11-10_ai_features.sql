-- =====================================================================
-- อัปเดตฐานข้อมูล: ระบบเครดิต AI, AI ช่วยวิเคราะห์, ออกเล่มรายงาน, AI ช่วย Conceptual map
-- เทียบเท่า: php yii migrate (m261110_000003 ถึง m261110_000006)
--
-- ใช้เมื่อรัน php yii migrate บนเซิร์ฟเวอร์ไม่ได้ (เช่น ใช้ phpMyAdmin)
-- ถ้ารัน php yii migrate ได้ ให้ใช้คำสั่งนั้นแทน ไม่ต้องใช้ไฟล์นี้
--
-- ก่อนรัน:
--   1. สำรองฐานข้อมูลก่อนทุกครั้ง
--   2. ตรวจว่ายังไม่เคยรัน:  SELECT version FROM migration WHERE version LIKE 'm261110_00000%';
--      ต้องมีแค่ m261110_000001 และ m261110_000002 — ถ้ามี 000003 ขึ้นไปแล้ว ห้ามรันซ้ำ
--   3. รันทั้งไฟล์ครั้งเดียว ตามลำดับ (MySQL 8 / MariaDB 10.5 ขึ้นไป)
--
-- หลังรัน *ต้อง* ล้าง schema cache ของระบบ (production จำโครงสร้างตารางไว้ 1 ชั่วโมง
-- ถ้าไม่ล้าง จะ error "An internal server error occurred." ทุกหน้าหลัง login):
--     php yii cache/flush-schema db --interactive=0
--   หรือถ้ารัน php ไม่ได้: ลบไฟล์ทั้งหมดในโฟลเดอร์ runtime/cache/ ของเว็บ (ลบได้ปลอดภัย ระบบสร้างใหม่เอง)
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- m261110_000003_ai_credits: กระเป๋าเครดิต, ประวัติเครดิต, งาน AI, ข้อเสนอจาก AI
-- (รวมผลของ m261110_000004: ai_suggestion.response_id เป็น NULL ได้ + คอลัมน์ members)
-- ---------------------------------------------------------------------
CREATE TABLE `credit_wallet` (
  `user_id` int NOT NULL,
  `balance` int NOT NULL DEFAULT '0',
  `updated_at` int NOT NULL DEFAULT '0',
  `flagDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ลบแล้ว (ซ่อน)',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk-credit_wallet-user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_job` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `question_id` int DEFAULT NULL,
  `feature` varchar(32) NOT NULL,
  `round` tinyint DEFAULT NULL,
  `model` varchar(80) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'running',
  `item_count` int NOT NULL DEFAULT '0',
  `credits_held` int NOT NULL DEFAULT '0',
  `credits_charged` int NOT NULL DEFAULT '0',
  `input_tokens` int NOT NULL DEFAULT '0',
  `output_tokens` int NOT NULL DEFAULT '0',
  `cost_usd` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `error` text,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  `flagDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ลบแล้ว (ซ่อน)',
  PRIMARY KEY (`id`),
  KEY `fk-ai_job-project` (`project_id`),
  KEY `idx-ai_job-user` (`user_id`, `created_at`),
  CONSTRAINT `fk-ai_job-project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-ai_job-user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `credit_ledger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `delta` int NOT NULL COMMENT '+ เพิ่ม / - ลด',
  `kind` varchar(16) NOT NULL COMMENT 'grant, usage, refund',
  `balance_after` int NOT NULL,
  `ai_job_id` int DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `flagDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ลบแล้ว (ซ่อน)',
  PRIMARY KEY (`id`),
  KEY `fk-credit_ledger-job` (`ai_job_id`),
  KEY `idx-credit_ledger-user` (`user_id`, `id`),
  CONSTRAINT `fk-credit_ledger-job` FOREIGN KEY (`ai_job_id`) REFERENCES `ai_job` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk-credit_ledger-user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_suggestion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ai_job_id` int NOT NULL,
  `question_id` int NOT NULL,
  `response_id` int DEFAULT NULL,
  `round` tinyint NOT NULL,
  `text` text NOT NULL,
  `ipo` varchar(16) DEFAULT NULL,
  `members` text COMMENT 'JSON ประเด็นเดิมที่ถูกรวม',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `interpretation_id` int DEFAULT NULL,
  `decided_by` int DEFAULT NULL,
  `decided_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `flagDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ลบแล้ว (ซ่อน)',
  PRIMARY KEY (`id`),
  KEY `fk-ai_suggestion-job` (`ai_job_id`),
  KEY `idx-ai_suggestion-question` (`question_id`, `status`, `round`),
  KEY `fk-ai_suggestion-response` (`response_id`),
  CONSTRAINT `fk-ai_suggestion-job` FOREIGN KEY (`ai_job_id`) REFERENCES `ai_job` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-ai_suggestion-response` FOREIGN KEY (`response_id`) REFERENCES `response` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `interpretation`
  ADD COLUMN `source` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'human' COMMENT 'human, ai, ai_edited' AFTER `text`;

ALTER TABLE `project`
  ADD COLUMN `ai_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'หัวหน้าโครงการอนุญาตให้ส่งข้อมูลให้ AI' AFTER `is_archived`;

-- ---------------------------------------------------------------------
-- m261110_000005_report_section: เนื้อหาเล่มรายงาน
-- ---------------------------------------------------------------------
CREATE TABLE `report_section` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `question_id` int DEFAULT NULL COMMENT 'NULL = บทสรุปภาพรวม',
  `content` text NOT NULL,
  `source` varchar(16) NOT NULL DEFAULT 'human' COMMENT 'human, ai, ai_edited',
  `ai_job_id` int DEFAULT NULL,
  `flagDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ลบแล้ว (ซ่อน)',
  `updated_by` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk-report_section-question` (`question_id`),
  KEY `fk-report_section-job` (`ai_job_id`),
  KEY `idx-report_section-project` (`project_id`, `flagDel`, `question_id`),
  CONSTRAINT `fk-report_section-job` FOREIGN KEY (`ai_job_id`) REFERENCES `ai_job` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk-report_section-project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-report_section-question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- m261110_000006_concept_ai: แนวคิดที่ AI เสนอใน Conceptual map
-- ---------------------------------------------------------------------
ALTER TABLE `concept`
  ADD COLUMN `source` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'human' COMMENT 'human, ai, ai_edited' AFTER `is_success`,
  ADD COLUMN `ai_pending` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ข้อเสนอจาก AI รอนักวิจัยรับ' AFTER `source`,
  ADD COLUMN `members` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON ประเด็นที่เป็นที่มาของแนวคิด' AFTER `ai_pending`,
  ADD COLUMN `ai_job_id` int DEFAULT NULL AFTER `members`,
  ADD CONSTRAINT `fk-concept-job` FOREIGN KEY (`ai_job_id`) REFERENCES `ai_job` (`id`) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- บันทึกว่ารัน migration แล้ว (เพื่อให้ php yii migrate ครั้งต่อไปไม่รันซ้ำ)
-- ---------------------------------------------------------------------
INSERT INTO `migration` (`version`, `apply_time`) VALUES
  ('m261110_000003_ai_credits', UNIX_TIMESTAMP()),
  ('m261110_000004_ai_synthesis', UNIX_TIMESTAMP()),
  ('m261110_000005_report_section', UNIX_TIMESTAMP()),
  ('m261110_000006_concept_ai', UNIX_TIMESTAMP());


-- =====================================================================
-- (ไม่บังคับ) แจกเครดิตต้อนรับ 5 เครดิต ให้ผู้ใช้ที่ใช้งานอยู่ทุกคนที่ยังไม่เคยได้
-- เทียบเท่า: php yii credits/welcome-existing  — รันซ้ำได้ปลอดภัย (คนที่ได้แล้วไม่ได้ซ้ำ)
-- ถ้าเปลี่ยนจำนวน ให้แก้เลข 5 ทั้งสองจุด ให้ตรงกับ ai.welcomeCredits ใน config
-- =====================================================================
INSERT INTO `credit_wallet` (`user_id`, `balance`, `updated_at`)
SELECT u.`id`, 5, UNIX_TIMESTAMP()
FROM `user` u
WHERE u.`flagDel` = 0 AND u.`status` = 10
  AND NOT EXISTS (SELECT 1 FROM `credit_ledger` l WHERE l.`user_id` = u.`id` AND l.`kind` = 'welcome')
ON DUPLICATE KEY UPDATE `balance` = `balance` + 5, `updated_at` = UNIX_TIMESTAMP();

INSERT INTO `credit_ledger` (`user_id`, `delta`, `kind`, `balance_after`, `note`, `created_at`)
SELECT w.`user_id`, 5, 'welcome', w.`balance`, 'เครดิตต้อนรับผู้ใช้ใหม่', UNIX_TIMESTAMP()
FROM `credit_wallet` w
JOIN `user` u ON u.`id` = w.`user_id`
WHERE u.`flagDel` = 0 AND u.`status` = 10
  AND NOT EXISTS (SELECT 1 FROM `credit_ledger` l WHERE l.`user_id` = w.`user_id` AND l.`kind` = 'welcome');
