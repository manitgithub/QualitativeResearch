# ระบบวิเคราะห์ข้อมูลเชิงคุณภาพ (Yii2)

เขียนใหม่จากระบบเดิม (Yii 1.1 ในโฟลเดอร์ `old/`) — PHP 8.1+, Yii 2.0.54, Bootstrap 5, MySQL 8+

## ติดตั้ง

```bash
composer install
cp config/db-local.example.php config/db-local.php          # แก้ค่าฐานข้อมูล
cp config/params-local.example.php config/params-local.php  # ใส่ cookieValidationKey, Google/LINE
mysql -uroot -e "CREATE DATABASE qualitative CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php yii migrate
php yii user/admin admin you@example.com    # สร้างผู้ดูแลระบบ (ถามรหัสผ่าน)
php yii serve --port=8090                   # ทดสอบที่ http://localhost:8090
```

Production: ชี้ document root ไปที่ `web/`, ตั้ง `YII_DEBUG=false`/`YII_ENV=prod` ใน `web/index.php`

## ย้ายข้อมูลจากระบบเดิม

```bash
mysql -uroot -e "CREATE DATABASE nurseapp_legacy CHARACTER SET utf8mb4"
mysql -uroot --default-character-set=utf8mb4 nurseapp_legacy < nurseapp.sql
php yii legacy/check                 # ตรวจว่ามีตารางครบ
php yii legacy/import                # นำเข้า (รันซ้ำได้ ข้ามรายการที่นำเข้าแล้ว)
php yii legacy/import --fresh=1      # ลบข้อมูลวิจัยที่นำเข้าแล้วนำเข้าใหม่ (บัญชีผู้ใช้คงไว้)
```

- ทุกอย่างอยู่ใน transaction เดียว ถ้าผิดพลาดจะย้อนกลับทั้งหมด
- ผู้ใช้เดิมเข้าระบบด้วยชื่อผู้ใช้/รหัสผ่านเดิมได้ รหัสผ่านจะถูกแปลงเป็น hash อัตโนมัติเมื่อเข้าระบบครั้งแรก
- ข้อความที่ระบบเดิมเก็บเป็น HTML entity (`&#3612;`) จะถูกถอดกลับเป็นภาษาไทย
- ชื่อผู้ใช้ที่ซ้ำกันในระบบเดิม (สมัครซ้ำ, ต่างกันแค่ตัวพิมพ์/วรรณยุกต์) จะรวมเป็นบัญชีเดียว โดยใช้แถวที่เข้าระบบบ่อยที่สุดเป็นหลัก
  ข้อมูลโครงการ/ทีม/ประวัติของทุกแถวจะย้ายมาอยู่ที่บัญชีนั้น และชื่อผู้ใช้ภาษาไทยใช้ต่อได้ตามเดิม
- ถ้าผิดพลาด จะแสดงเฉพาะรหัส error ของฐานข้อมูล ไม่แสดงค่าที่ insert (เพราะมีรหัสผ่านเดิม)
- dump ควรทำด้วย `mysqldump` (phpMyAdmin ตัดกลางทางได้เมื่อไฟล์ใหญ่):
  `mysqldump -u<user> -p --default-character-set=utf8mb4 --single-transaction nurseapp > nurseapp.sql`

| ระบบเดิม | ระบบใหม่ |
|---|---|
| dt_userkey / dt_log | user / login_log |
| dt_main / dt_join | project / project_member |
| dt_information / dt_group | collection_method / informant_group |
| dt_qution / dt_anwer | question / question_option |
| dt_people / dt_interview | informant / response |
| dt_interpret (channel 1–4, types 1–3) | interpretation (round 1–4, ipo input/process/output) |
| dt_conceptual | concept |

## เข้าระบบด้วย Google / LINE

ใส่ค่าใน `config/params-local.php` (ปุ่มจะแสดงในหน้า login เมื่อมีค่า)

- **Google**: Google Cloud Console → Credentials → OAuth client ID (Web) → Redirect URI `https://<host>/site/auth?authclient=google`
- **LINE**: LINE Developers → LINE Login channel → Callback URL `https://<host>/site/auth?authclient=line`
  ถ้าต้องการให้จับคู่บัญชีด้วยอีเมล ให้ขอสิทธิ์ *Email address permission* ใน channel

การจับคู่บัญชี:
1. เคยผูกไว้แล้ว → เข้าระบบ
2. อีเมล (ที่ยืนยันแล้ว) ตรงกับผู้ใช้ที่มีอยู่ → ผูกให้อัตโนมัติ — ผู้ดูแลใส่อีเมลให้ผู้ใช้เดิมก่อนได้ที่เมนู "ผู้ใช้"
3. ไม่พบ → สร้างบัญชีใหม่สถานะ "รออนุมัติ" (ปิดได้ที่ `socialSignupRequiresApproval`)
4. ผู้ใช้ผูก/ยกเลิกบัญชีเพิ่มเองได้ที่ "ข้อมูลส่วนตัว" (อีเมลแก้ได้เฉพาะผู้ดูแลระบบ)

## สิทธิ์

| บทบาท | ดู | บันทึก/ตีความ | ตั้งค่าโครงการ/ทีม |
|---|---|---|---|
| ผู้ดูแลระบบ | ทุกโครงการ | ✓ | ✓ |
| หัวหน้าโครงการ | ✓ | ✓ | ✓ |
| ทีมงาน | ✓ | ✓ | |
| ดูอย่างเดียว | ✓ | | |
