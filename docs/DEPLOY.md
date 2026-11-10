# ขึ้นระบบจริง (Production)

ไฟล์ฐานข้อมูล: `runtime/deploy/qualitative-20261110.sql.gz` (9 MB, ข้อมูลจริงทั้งหมด + โครงสร้าง + ประวัติ migration)
ไม่ถูก commit เข้า git — คัดลอกไปที่เซิร์ฟเวอร์เอง (เช่น `scp`) ห้ามวางไว้ในโฟลเดอร์ `web/`

ต้องการ: PHP 8.1+ (pdo_mysql, mbstring, iconv, zip, gd), MySQL 8.0+ (ทดสอบนำเข้าแล้วบน MySQL 9.5), Composer
— MariaDB น่าจะใช้ได้ (ใช้ generated column แบบ STORED) แต่ยังไม่ได้ทดสอบ

## 1. ฐานข้อมูล

```bash
mysql -u root -p -e "CREATE DATABASE qualitative CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p -e "CREATE USER 'qualitative'@'localhost' IDENTIFIED BY '<รหัสผ่านใหม่>'; GRANT ALL ON qualitative.* TO 'qualitative'@'localhost'"
gunzip -c qualitative-20261110.sql.gz | mysql -u root -p --default-character-set=utf8mb4 qualitative
```

ใช้ phpMyAdmin ก็ได้ (Import → เลือกไฟล์ `.sql.gz`) แต่ถ้าเกินขนาดอัปโหลดของ phpMyAdmin
ให้ใช้คำสั่งด้านบนแทน (phpMyAdmin เคยตัดไฟล์ dump กลางทางมาแล้ว)

ตรวจหลังนำเข้า — ต้องได้ตัวเลขนี้:

```sql
SELECT (SELECT COUNT(*) FROM user) users, (SELECT COUNT(*) FROM project) projects,
       (SELECT COUNT(*) FROM informant) informants, (SELECT COUNT(*) FROM response) responses;
-- 204 | 623 | 29156 | 76832
```

## 2. โค้ด

```bash
git clone -b yii2-rewrite https://github.com/manitgithub/QualitativeResearch.git
cd QualitativeResearch
composer install --no-dev --optimize-autoloader
cp config/db-local.example.php config/db-local.php          # ใส่ user/รหัสผ่านฐานข้อมูล
cp config/params-local.example.php config/params-local.php  # cookieValidationKey ใหม่ + Google/LINE client ID
# หรือใช้ไฟล์ที่เตรียมไว้แล้วใน runtime/deploy/server-config/ (db-local.php, params-local.php) อัปโหลดไปที่ config/
php yii migrate --interactive=0                              # ควรขึ้นว่า "No new migrations found"
chmod -R 775 runtime web/assets
```

- ตั้ง document root ของ web server ไปที่ `web/`
- แก้ `web/index.php`: `YII_DEBUG` = `false`, `YII_ENV` = `'prod'`
- ลบ `web/index-test.php` ออกจากเซิร์ฟเวอร์จริง
- ใช้ HTTPS; Redirect URI ของ Google/LINE ต้องเป็นโดเมนจริง: `https://<โดเมน>/site/auth?authclient=google` และ `...=line`

**ความลับอยู่ใน `config/*-local.php` เท่านั้น** (รหัสผ่านฐานข้อมูล, client secret ของ Google/LINE, cookieValidationKey)
ไฟล์เหล่านี้ไม่ถูก commit — ห้ามใส่ค่าจริงใน `config/db.php` / `config/params.php`
(`tests/Unit/ConfigSecretsTest.php` จะล้มถ้ามีค่าความลับในไฟล์ที่ git ติดตาม)

## 3. ผู้ดูแลคนแรก

ระบบเข้าได้เฉพาะ Gmail / LINE — ต้องผูก Gmail ให้ผู้ดูแลก่อนจึงจะมีคนอนุมัติผู้อื่นได้

```bash
php yii user/admin <gmail ของผู้ดูแล> manit
```

จากนั้นเข้าระบบด้วย Gmail นั้น ผู้ใช้เดิมคนอื่นที่เข้าด้วย Gmail/LINE ครั้งแรกจะขึ้นในเมนู "ผู้ใช้" → "รออนุมัติ"
ให้เลือก "รวมกับบัญชีเดิม" เพื่อให้เห็นโครงการเดิมของตัวเอง

## 4. หลังเปิดใช้

- ลบฐานข้อมูล `nurseapp_legacy` (เก็บรหัสผ่านเดิมแบบไม่เข้ารหัส) และไฟล์ dump ระบบเดิม
- ลบไฟล์ `qualitative-*.sql.gz` ออกจากเซิร์ฟเวอร์หลังนำเข้าแล้ว (มีข้อมูลสัมภาษณ์)
- สำรองข้อมูลประจำ: `mysqldump --single-transaction --set-gtid-purged=OFF qualitative | gzip > backup-$(date +%F).sql.gz`

## อัปเดตฐานข้อมูลเซิร์ฟเวอร์เดิม (ฟีเจอร์ AI / เครดิต / ออกเล่ม)

สำรองฐานข้อมูลก่อนเสมอ แล้วเลือกทางใดทางหนึ่ง:

```bash
php yii migrate --interactive=0           # ทางหลัก
php yii credits/welcome-existing          # แจกเครดิตต้อนรับให้ผู้ใช้เดิม (รันซ้ำได้ ไม่แจกซ้ำ)
```

ถ้ารันคำสั่ง `php yii` บนเซิร์ฟเวอร์ไม่ได้ ให้รัน SQL [`docs/sql/2026-11-10_ai_features.sql`](sql/2026-11-10_ai_features.sql)
ผ่าน phpMyAdmin แทน (ทั้งไฟล์ ครั้งเดียว) — ไฟล์นี้สร้างตาราง/คอลัมน์ให้เหมือน `php yii migrate` ทุกประการ
(ตรวจเทียบ column, index และ foreign key แล้ว) และบันทึกลงตาราง `migration` ให้ด้วย
ส่วนท้ายไฟล์คือการแจกเครดิตต้อนรับ (ไม่บังคับ) ซึ่งเทียบเท่า `php yii credits/welcome-existing`

**หลังรัน SQL ต้องล้าง schema cache ทุกครั้ง** — production จำโครงสร้างตารางไว้ 1 ชั่วโมง
ถ้าไม่ล้าง ทุกหน้าหลัง login จะขึ้น "An internal server error occurred."
(`php yii migrate` ล้างให้เอง แต่ SQL ที่รันผ่าน phpMyAdmin ไม่ล้าง)

```bash
php yii cache/flush-schema db --interactive=0
```

ถ้ารัน `php yii` ไม่ได้ ให้ลบไฟล์ทั้งหมดใน `runtime/cache/` (ลบได้ปลอดภัย ระบบสร้างใหม่เอง)

ก่อนรัน SQL ตรวจว่ายังไม่เคยอัปเดต:

```sql
SELECT version FROM migration WHERE version LIKE 'm261110_00000%';
-- ต้องมีแค่ m261110_000001 และ m261110_000002
```

หลังอัปเดต ใส่ `openrouter.apiKey` ใน `config/params-local.php` เพื่อเปิดฟีเจอร์ AI
