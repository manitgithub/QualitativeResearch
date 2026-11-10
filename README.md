# ระบบวิเคราะห์ข้อมูลเชิงคุณภาพ (Yii2)

เขียนใหม่จากระบบเดิม (Yii 1.1 ในโฟลเดอร์ `old/`) — PHP 8.1+, Yii 2.0.54, Bootstrap 5, MySQL 8+

## ติดตั้ง

```bash
composer install
cp config/db-local.example.php config/db-local.php          # แก้ค่าฐานข้อมูล
cp config/params-local.example.php config/params-local.php  # ใส่ cookieValidationKey, Google/LINE
mysql -uroot -e "CREATE DATABASE qualitative CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php yii migrate
php yii user/admin you@gmail.com [username]  # ตั้งผู้ดูแลระบบด้วย Gmail (ใส่ username = ใช้บัญชีเดิม)
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
- ไม่นำเข้ารหัสผ่านเดิม (ระบบใหม่เข้าได้เฉพาะ Google / LINE)
- ข้อความที่ระบบเดิมเก็บเป็น HTML entity (`&#3612;`) จะถูกถอดกลับเป็นภาษาไทย
- ชื่อผู้ใช้ที่ซ้ำกันในระบบเดิม (สมัครซ้ำ, ต่างกันแค่ตัวพิมพ์/วรรณยุกต์) จะรวมเป็นบัญชีเดียว โดยใช้แถวที่เข้าระบบบ่อยที่สุดเป็นหลัก
  ข้อมูลโครงการ/ทีม/ประวัติของทุกแถวจะย้ายมาอยู่ที่บัญชีนั้น และชื่อผู้ใช้ภาษาไทยใช้ต่อได้ตามเดิม
- ถ้าผิดพลาด จะแสดงเฉพาะรหัส error ของฐานข้อมูล ไม่แสดงค่าที่ insert (ข้อมูลส่วนบุคคล)
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

## เข้าระบบด้วย Gmail / LINE (ไม่มีการเข้าด้วยรหัสผ่าน)

ใส่ค่าใน `config/params-local.php` (ปุ่มจะแสดงในหน้า login เมื่อมีค่า)

- **Google**: Google Cloud Console → Credentials → OAuth client ID (Web) → Redirect URI `https://<host>/site/auth?authclient=google`
- **LINE**: LINE Developers → LINE Login channel → Callback URL `https://<host>/site/auth?authclient=line`
  ถ้าต้องการให้จับคู่บัญชีด้วยอีเมล ให้ขอสิทธิ์ *Email address permission* ใน channel

เริ่มใช้งานครั้งแรก (ยังไม่มีใครเข้าระบบได้จนกว่าจะมีผู้ดูแล):

```bash
php yii user/admin manit@gmail.com manit     # ใส่ Gmail ให้บัญชีเดิม "manit" และตั้งเป็นผู้ดูแล
php yii user/email somsri somsri@gmail.com   # (ถ้ารู้ Gmail ของผู้ใช้เดิม) ใส่ไว้ล่วงหน้า
```

การจับคู่บัญชีเมื่อเข้าระบบ:
1. เคยผูกไว้แล้ว → เข้าระบบ
2. อีเมล (ที่ยืนยันแล้ว) ตรงกับบัญชีที่มีอยู่ → ผูกให้อัตโนมัติและเข้าได้ทันที (ผู้ดูแลใส่อีเมลไว้ล่วงหน้าแล้ว)
3. ไม่พบ → สร้างคำขอสถานะ "รออนุมัติ" — ผู้ดูแลเปิดเมนู "ผู้ใช้" แล้วเลือก
   - **รวมกับบัญชีเดิม** (ระบบเสนอชื่อที่ใกล้เคียง) → คนนั้นได้โครงการเดิมของตัวเองคืน
   - **อนุมัติเป็นผู้ใช้ใหม่** หรือ **ปฏิเสธ**
4. ผู้ใช้ผูก LINE/Google เพิ่มได้ที่ "ข้อมูลส่วนตัว"; ผู้ดูแลยกเลิกการผูกที่ผิดคนได้ที่หน้าผู้ใช้
5. อีเมลแก้ได้เฉพาะผู้ดูแลระบบ (ใช้จับคู่บัญชีอัตโนมัติ)

## การลบข้อมูล (flagDel)

ทุกตารางมีคอลัมน์ `flagDel` (0 = ใช้งาน, 1 = ลบแล้ว) ปุ่มลบทุกจุดในระบบแค่ตั้ง `flagDel = 1` แถวยังอยู่ในฐานข้อมูล

- โค้ด: model ทุกตัวใช้ `SoftDeleteTrait` → `delete()` / `deleteAll()` ตั้ง flag แทนการลบ และทุก query ซ่อนแถวที่ลบแล้วอัตโนมัติ
  ต้องการเห็นแถวที่ลบแล้ว: `Model::find()->withDeleted()`
- ตารางที่ `joinWith()` เข้ามาไม่ถูกกรองอัตโนมัติ — ใส่ `alias.flagDel = 0` เอง
- อีเมล ชื่อผู้ใช้ และการผูก Google/LINE ไม่ซ้ำเฉพาะแถวที่ยังไม่ลบ (บัญชีที่ถูกปฏิเสธขอเข้าใหม่ได้)
- ข้อยกเว้นเดียว: `./yii legacy/import --fresh=1` ลบข้อมูลที่นำเข้าจากระบบเดิมจริง เพื่อนำเข้าชุดเดิมซ้ำ
- กู้คืนข้อมูลที่ลบ: แก้ `flagDel` เป็น 0 ในฐานข้อมูล (ยังไม่มีหน้ากู้คืนในระบบ)

## สิทธิ์

| บทบาท | ดู | บันทึก/ตีความ | ตั้งค่าโครงการ/ทีม |
|---|---|---|---|
| ผู้ดูแลระบบ | ทุกโครงการ | ✓ | ✓ |
| หัวหน้าโครงการ | ✓ | ✓ | ✓ |
| ทีมงาน | ✓ | ✓ | |
| ดูอย่างเดียว | ✓ | | |
