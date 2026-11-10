<?php

/**
 * คู่มือการใช้งาน (หน้าเดียว มีสารบัญคลิกไปยังหัวข้อได้)
 *
 * @var yii\web\View $this
 * @var bool $isAdmin
 */

use yii\helpers\Html;

$this->title = 'คู่มือการใช้งาน';
$this->params['breadcrumbs'][] = $this->title;
$p = Yii::$app->params;
$bahtPerCredit = (float)($p['ai.bahtPerCredit'] ?? 0.10);
$welcome = (int)($p['ai.welcomeCredits'] ?? 0);
$batch = (int)($p['ai.batchSize'] ?? 10);

// สารบัญ: id => [หัวข้อ, [id ย่อย => หัวข้อย่อย]]
$toc = [
    'start' => ['1. เริ่มต้นใช้งาน', [
        'login' => 'เข้าสู่ระบบด้วย Google / LINE',
        'approval' => 'รอผู้ดูแลอนุมัติ',
        'profile' => 'ข้อมูลส่วนตัวและผูกบัญชี',
        'workflow' => 'ภาพรวมขั้นตอนการทำงาน',
    ]],
    'project' => ['2. โครงการวิจัย', [
        'project-create' => 'สร้างและค้นหาโครงการ',
        'project-team' => 'ทีมงานและสิทธิ์',
        'project-groups' => 'กลุ่มผู้ให้ข้อมูล',
        'project-archive' => 'เก็บเข้าคลัง',
    ]],
    'questions' => ['3. คำถาม', [
        'q-category' => 'ประเภทคำถาม',
        'q-type' => 'รูปแบบคำตอบ',
        'q-order' => 'เรียงลำดับและแก้ไข',
    ]],
    'data' => ['4. บันทึกข้อมูล', [
        'interview' => 'บันทึกการสัมภาษณ์',
        'import' => 'นำเข้าจาก Excel / CSV',
        'informants' => 'จัดการผู้ให้ข้อมูล',
    ]],
    'analysis' => ['5. วิเคราะห์ข้อมูล', [
        'rounds' => 'ขั้นตอน 4 รอบ',
        'analysis-how' => 'เพิ่ม แก้ไข ลบประเด็น',
        'analysis-export' => 'ส่งออก Excel และพิมพ์',
    ]],
    'reports' => ['6. รายงาน', [
        'r-themes' => 'ประเด็นที่ได้',
        'r-informant' => 'ตามผู้ให้ข้อมูล',
        'r-stats' => 'สถิติ',
        'r-mindmap' => 'แผนผังความคิด',
        'r-wordcloud' => 'Word cloud',
        'r-ipo' => 'Input-Process-Output',
        'r-concept' => 'Conceptual map',
    ]],
    'ai' => ['7. AI ช่วยวิเคราะห์', [
        'ai-enable' => 'เปิดใช้ AI ในโครงการ',
        'ai-principle' => 'หลักการ: AI เสนอ นักวิจัยตัดสิน',
        'ai-rounds' => 'AI ช่วยสรุป/ยกประเด็น',
        'ai-synth' => 'AI สังเคราะห์ IPO',
        'ai-concept' => 'AI ช่วย Conceptual map',
        'ai-grounding' => 'การป้องกันการตีความเกินข้อมูล',
        'ai-privacy' => 'ข้อมูลที่ส่งให้ AI',
    ]],
    'book' => ['8. ออกเล่มรายงาน', [
        'book-write' => 'เขียนบทวิเคราะห์',
        'book-summary' => 'บทสรุปผลการวิจัย',
        'book-export' => 'ดาวน์โหลด Word / พิมพ์ PDF',
    ]],
    'credits' => ['9. เครดิต AI', [
        'credits-what' => 'เครดิตคืออะไร',
        'credits-balance' => 'ดูยอดและประวัติ',
        'credits-topup' => 'เติมเครดิต',
    ]],
    'admin' => ['10. สำหรับผู้ดูแลระบบ', [
        'admin-approve' => 'อนุมัติและรวมบัญชี',
        'admin-users' => 'จัดการผู้ใช้',
        'admin-credits' => 'เติม/หักเครดิตให้ผู้ใช้',
    ]],
    'faq' => ['11. คำถามที่พบบ่อย', []],
];

$tocHtml = function (string $class) use ($toc) {
    $out = '<nav class="' . $class . '"><ul class="list-unstyled mb-0">';
    foreach ($toc as $id => [$title, $children]) {
        $out .= '<li><a class="toc-link toc-l1" href="#' . $id . '">' . Html::encode($title) . '</a>';
        if ($children) {
            $out .= '<ul class="list-unstyled">';
            foreach ($children as $cid => $ct) {
                $out .= '<li><a class="toc-link toc-l2" href="#' . $cid . '">' . Html::encode($ct) . '</a></li>';
            }
            $out .= '</ul>';
        }
        $out .= '</li>';
    }
    return $out . '</ul></nav>';
};
$h2 = fn(string $id) => '<h2 id="' . $id . '" class="doc-h2">' . Html::encode($toc[$id][0]) . '</h2>';
$h3 = function (string $parent, string $id) use ($toc) {
    return '<h3 id="' . $id . '" class="doc-h3">' . Html::encode($toc[$parent][1][$id]) . '</h3>';
};
?>
<div class="page-header"><h1><i class="bi bi-book"></i> <?= $this->title ?></h1></div>

<div class="row g-4 docs">
    <div class="col-lg-3 d-none d-lg-block">
        <div class="docs-toc card">
            <div class="card-header">สารบัญ</div>
            <div class="card-body py-2"><?= $tocHtml('docs-toc-nav') ?></div>
        </div>
    </div>

    <div class="col-lg-9">
        <details class="card mb-3 d-lg-none">
            <summary class="card-header">สารบัญ (แตะเพื่อเปิด)</summary>
            <div class="card-body py-2"><?= $tocHtml('') ?></div>
        </details>

        <div class="card"><div class="card-body docs-body">

<?= $h2('start') ?>
<p>ระบบวิเคราะห์ข้อมูลเชิงคุณภาพช่วยให้ทีมวิจัยบันทึกข้อมูลสัมภาษณ์ ตีความ/ยกประเด็นเป็นรอบ ๆ สังเคราะห์ตามกรอบ Input-Process-Output (IPO) และออกเล่มรายงาน
โดยมี AI เป็นผู้ช่วยเสนอแนะ (ไม่บังคับใช้)</p>

<?= $h3('start', 'login') ?>
<ol>
    <li>เปิดหน้าเว็บ ระบบจะพาไปหน้าเข้าสู่ระบบ</li>
    <li>กด <b>เข้าสู่ระบบด้วย Google</b> หรือ <b>เข้าสู่ระบบด้วย LINE</b> — ระบบไม่มีการตั้งรหัสผ่าน</li>
    <li>ถ้าผู้ดูแลระบบเคยใส่ Gmail ของคุณไว้ในบัญชีเดิม ระบบจะผูกให้อัตโนมัติและเข้าใช้งานได้ทันที</li>
</ol>

<?= $h3('start', 'approval') ?>
<p>ผู้ที่เข้าระบบครั้งแรกจะอยู่ในสถานะ <b>รออนุมัติ</b> จนกว่าผู้ดูแลระบบจะอนุมัติ
ผู้ดูแลอาจ <b>รวมเข้ากับบัญชีเดิม</b> (ถ้าคุณเคยใช้ระบบเดิม จะได้โครงการเดิมคืน) หรือเปิดเป็นผู้ใช้ใหม่
<?php if ($welcome > 0): ?>ผู้ใช้ใหม่ได้รับ <b><?= $welcome ?> เครดิต AI ฟรี</b> เมื่อได้รับอนุมัติ<?php endif ?></p>

<?= $h3('start', 'profile') ?>
<p>เมนูชื่อของคุณ (มุมขวาบน) → <b>ข้อมูลส่วนตัว / ผูกบัญชี</b></p>
<ul>
    <li>แก้คำนำหน้า ชื่อ นามสกุล (อีเมลแก้ได้เฉพาะผู้ดูแลระบบ)</li>
    <li>ผูกบัญชีได้ทั้ง Google และ LINE เพื่อเข้าระบบได้สองทาง</li>
    <li>ดูยอดเครดิต AI และประวัติการใช้</li>
</ul>

<?= $h3('start', 'workflow') ?>
<div class="doc-steps">
    <div><b>1</b> สร้างโครงการ</div><i class="bi bi-chevron-right"></i>
    <div><b>2</b> เพิ่มคำถาม</div><i class="bi bi-chevron-right"></i>
    <div><b>3</b> บันทึก/นำเข้าคำตอบ</div><i class="bi bi-chevron-right"></i>
    <div><b>4</b> สรุป/ยกประเด็น รอบ 1–3</div><i class="bi bi-chevron-right"></i>
    <div><b>5</b> สังเคราะห์ IPO</div><i class="bi bi-chevron-right"></i>
    <div><b>6</b> รายงาน / ออกเล่ม</div>
</div>
<p class="small text-muted">ขั้นที่ 4–6 ใช้ AI ช่วยได้ แต่ทำเองทั้งหมดก็ได้</p>

<?= $h2('project') ?>
<?= $h3('project', 'project-create') ?>
<ul>
    <li>หน้าแรก (<b>โครงการของฉัน</b>) แสดงโครงการที่คุณเป็นทีมงาน กด <b>สร้างโครงการวิจัย</b> แล้วใส่ชื่อ รายละเอียด และวันที่เริ่ม</li>
    <li>ผู้สร้างเป็น <b>หัวหน้าโครงการ</b> โดยอัตโนมัติ</li>
    <li>ช่องค้นหาด้านบนค้นได้จากชื่อโครงการ รายละเอียด หรือชื่อหัวหน้าโครงการ (พิมพ์หลายคำได้)</li>
    <li>เมื่อเข้าโครงการ เมนูด้านซ้ายแบ่งเป็น โครงการ / บันทึกข้อมูล / วิเคราะห์ / ออกเล่ม / ตั้งค่า (บนมือถือ กดปุ่ม <b>เมนูโครงการ</b> มุมขวาล่าง)</li>
</ul>

<?= $h3('project', 'project-team') ?>
<p>เมนู <b>ตั้งค่า → ทีมงานและกลุ่ม</b> (เฉพาะหัวหน้าโครงการ)</p>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>บทบาท</th><th>ดูข้อมูล/รายงาน</th><th>บันทึก/แก้ไข/ใช้ AI</th><th>แก้โครงการ/ทีม/เปิด AI</th></tr></thead>
        <tbody>
        <tr><td>หัวหน้า/ผู้จัดการ</td><td>✔</td><td>✔</td><td>✔</td></tr>
        <tr><td>ทีมงาน</td><td>✔</td><td>✔</td><td>—</td></tr>
        <tr><td>ดูอย่างเดียว</td><td>✔</td><td>—</td><td>—</td></tr>
        </tbody>
    </table>
</div>
<p>ผู้ดูแลระบบมีสิทธิ์เทียบเท่าหัวหน้าในทุกโครงการ</p>

<?= $h3('project', 'project-groups') ?>
<p>ในหน้าเดียวกันกำหนด <b>กลุ่มผู้ให้ข้อมูล</b> ได้ (เช่น ผู้ดูแล / ผู้ป่วย / เจ้าหน้าที่) เพื่อใช้จัดกลุ่มตอนบันทึกและดูรายงาน</p>

<?= $h3('project', 'project-archive') ?>
<p>เมนู <b>แก้ไขโครงการ</b> → <b>เก็บเข้าคลัง</b> — โครงการในคลังยังเปิดดูได้แต่แก้ไขไม่ได้ และไม่แสดงในหน้าหลัก
(ดูได้จากปุ่ม <b>คลัง</b> ในหน้าแรก) กด <b>นำออกจากคลัง</b> เพื่อแก้ไขต่อ</p>

<?= $h2('questions') ?>
<p>เมนู <b>โครงการ → คำถาม</b></p>
<?= $h3('questions', 'q-category') ?>
<ul>
    <li><b>คำถามการวิจัย</b> — คำถามหลักที่จะนำไปวิเคราะห์ประเด็น (ใช้ในหน้าวิเคราะห์ทุกหน้าและ AI)</li>
    <li><b>ข้อมูลพื้นฐาน</b> — เช่น เพศ อาชีพ ใช้ทำสถิติ ไม่นำไปตีความ</li>
</ul>
<?= $h3('questions', 'q-type') ?>
<ul>
    <li><b>ข้อความ</b> — ตอบอิสระ (AI ช่วยได้เฉพาะคำถามแบบนี้)</li>
    <li><b>เลือกตอบ</b> — ใส่ตัวเลือกบรรทัดละ 1 ตัวเลือก</li>
    <li><b>ใช่ / ไม่ใช่</b></li>
</ul>
<?= $h3('questions', 'q-order') ?>
<p>ลากเพื่อเรียงลำดับคำถาม กดไอคอนดินสอเพื่อแก้ไข หรือถังขยะเพื่อลบ (ข้อมูลที่ลบจะถูกซ่อน ไม่ถูกลบถาวร)</p>

<?= $h2('data') ?>
<?= $h3('data', 'interview') ?>
<p>เมนู <b>บันทึกข้อมูล → บันทึกการสัมภาษณ์</b></p>
<ol>
    <li>ใส่ <b>รหัสผู้ให้ข้อมูล</b> (เช่น P01 — ใช้รหัสแทนชื่อจริงเพื่อปกป้องผู้ให้ข้อมูล) อายุ วิธีเก็บข้อมูล วันที่ กลุ่ม และหมายเหตุ</li>
    <li>ตอบคำถามทุกข้อในหน้าเดียว แล้วกดบันทึก</li>
</ol>
<?= $h3('data', 'import') ?>
<p>เมนู <b>บันทึกข้อมูล → นำเข้า Excel/CSV</b> สำหรับข้อมูลจำนวนมาก</p>
<ol>
    <li>กด <b>ดาวน์โหลดแม่แบบ</b> จะได้ไฟล์ Excel ที่มีหัวคอลัมน์ตรงกับคำถามของโครงการ</li>
    <li>แถวแรกคือหัวคอลัมน์: รหัสผู้ให้ข้อมูล, อายุ, และคำถามแต่ละข้อ (ขึ้นต้นด้วย <code>#เลขคำถาม</code>) แถวถัดไปเป็นข้อมูล 1 คนต่อ 1 แถว</li>
    <li>อัปโหลดไฟล์ .xlsx, .xls หรือ .csv (รองรับ CSV ภาษาไทยที่บันทึกจาก Excel)</li>
</ol>
<div class="alert alert-warning small">ถ้ารหัสผู้ให้ข้อมูลซ้ำกับที่มีอยู่ ระบบจะ <b>เติม/แทนที่คำตอบของคนเดิม</b> ไม่สร้างคนใหม่</div>
<?= $h3('data', 'informants') ?>
<p>เมนู <b>โครงการ → ผู้ให้ข้อมูล</b> แสดงรายชื่อทั้งหมด กดรหัสเพื่อดูคำตอบทุกข้อของคนนั้น แก้ไขหรือลบได้</p>

<?= $h2('analysis') ?>
<p>เมนู <b>วิเคราะห์ → ตามคำถามการวิจัย</b> เป็นหน้าหลักของการวิเคราะห์ เลือกคำถามด้านบน (หรือ "ทุกข้อ") และเลือก <b>แสดงถึงรอบ</b></p>
<?= $h3('analysis', 'rounds') ?>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>รอบ</th><th>ทำอะไร</th><th>ผูกกับ</th></tr></thead>
        <tbody>
        <tr><td>1. สรุปประเด็น</td><td>สรุปสาระจากคำตอบดิบของแต่ละคนเป็นวลีสั้น</td><td>คำตอบรายคน</td></tr>
        <tr><td>2. ยกประเด็น รอบ 1</td><td>ยกประเด็นรอบ 1 ขึ้นเป็นหมวดที่กว้างขึ้น</td><td>คำตอบรายคน</td></tr>
        <tr><td>3. ยกประเด็น รอบ 2</td><td>ยกขึ้นอีกระดับเป็นแก่นเรื่อง</td><td>คำตอบรายคน</td></tr>
        <tr><td>4. สังเคราะห์ (IPO)</td><td>รวมประเด็นทั้งหมดของคำถามเป็นกลุ่ม Input / Process / Output</td><td>ทั้งคำถาม</td></tr>
        </tbody>
    </table>
</div>
<p><b>เคล็ดลับ:</b> ใช้ถ้อยคำเดียวกันกับประเด็นที่ความหมายเหมือนกัน หน้า <a href="#r-themes">ประเด็นที่ได้</a> จะนับรวมให้อัตโนมัติ</p>
<?= $h3('analysis', 'analysis-how') ?>
<ul>
    <li>กด <b>+ เพิ่ม</b> ในช่องของรอบที่ต้องการ พิมพ์ประเด็น เลือก IPO (ถ้ามี) แล้วบันทึก</li>
    <li>ชี้ที่ประเด็นแล้วกด <i class="bi bi-pencil"></i> เพื่อแก้ หรือ <i class="bi bi-trash"></i> เพื่อลบ</li>
    <li>รอบ 4 (สังเคราะห์) มีช่องเพิ่มประเด็นระดับคำถามด้านบนตาราง</li>
    <li>ประเด็นที่มาจาก AI มีไอคอน <i class="bi bi-stars text-primary"></i> กำกับ</li>
</ul>
<?= $h3('analysis', 'analysis-export') ?>
<p>ปุ่ม <b>Excel</b> ส่งออกทุกคำถามพร้อมประเด็นถึงรอบที่เลือก ปุ่ม <i class="bi bi-printer"></i> พิมพ์หน้าปัจจุบัน
(โหมด "ทุกข้อ" บนจอแสดงหน้าละ 3 คำถาม แต่ Excel ส่งออกครบ)</p>

<?= $h2('reports') ?>
<p>เมนู <b>วิเคราะห์</b> ทุกหน้าคำนวณจากข้อมูลล่าสุดทันที</p>
<?= $h3('reports', 'r-themes') ?>
<p>รวมประเด็นที่ข้อความเหมือนกัน แสดงจำนวนผู้ให้ข้อมูลและรหัสของคนที่กล่าวถึง เลือกได้ทีละรอบ ส่งออก Excel ได้</p>
<?= $h3('reports', 'r-informant') ?>
<p>ตารางคำตอบและประเด็นแยกรายคน เหมาะสำหรับตรวจข้อมูลทั้งชุด</p>
<?= $h3('reports', 'r-stats') ?>
<p>แผนภูมิจากคำถามข้อมูลพื้นฐาน (เลือกตอบ / ใช่-ไม่ใช่) และกลุ่มอายุ</p>
<?= $h3('reports', 'r-mindmap') ?>
<p>แผนผังต้นไม้: โครงการ → คำถาม → ประเด็น คลิกที่จุดเพื่อยุบ/ขยาย</p>
<?= $h3('reports', 'r-wordcloud') ?>
<p>คำที่พบบ่อยในประเด็นของคำถามที่เลือก (ขนาดตัวอักษร = ความถี่)</p>
<?= $h3('reports', 'r-ipo') ?>
<p>รวมประเด็นที่ติดป้าย Input / Process / Output ของทุกคำถาม พร้อมแนวคิดจาก Conceptual map ที่รับไว้แล้ว</p>
<?= $h3('reports', 'r-concept') ?>
<p>รายการแนวคิด/องค์ประกอบระดับโครงการ แต่ละรายการติ๊กได้ 5 ช่อง: Input, Process, Output, ปัญหา/อุปสรรค, ปัจจัยความสำเร็จ
— เพิ่มเองด้วยช่องด้านบน หรือให้ <a href="#ai-concept">AI ช่วย</a> เสนอจากประเด็นสังเคราะห์</p>

<?= $h2('ai') ?>
<?= $h3('ai', 'ai-enable') ?>
<p>AI ปิดอยู่เป็นค่าเริ่มต้นทุกโครงการ <b>หัวหน้าโครงการ</b> เปิดได้ที่ <b>ตั้งค่า → แก้ไขโครงการ</b> → ติ๊ก <b>อนุญาตให้ใช้ AI ช่วยวิเคราะห์</b></p>
<div class="alert alert-warning small">ควรเปิดเฉพาะเมื่อใบยินยอมของผู้ให้ข้อมูลและการรับรองจริยธรรมการวิจัยครอบคลุมการประมวลผลด้วยบริการภายนอก</div>

<?= $h3('ai', 'ai-principle') ?>
<ul>
    <li><b>AI เสนอเท่านั้น</b> — ผลทุกอย่างแสดงเป็นกรอบเส้นประ <span class="ai-suggestion d-inline-block py-0">ข้อเสนอ</span> จนกว่านักวิจัยกด <i class="bi bi-check-lg text-success"></i> รับ หรือ <i class="bi bi-x-lg text-danger"></i> ปฏิเสธ</li>
    <li>ข้อเสนอที่ยังไม่รับ <b>ไม่ขึ้นในรายงานใด ๆ</b></li>
    <li>ระบบบันทึกที่มาของทุกประเด็น (นักวิจัย / AI / AI แล้วนักวิจัยแก้) เพื่อใช้เปิดเผยระเบียบวิธีในรายงาน</li>
    <li>ถ้ามีข้อเสนอค้างอยู่ ปุ่มสั่ง AI ซ้ำจะถูกซ่อน เพื่อไม่ให้เสียเครดิตซ้ำ</li>
    <li>ถ้า AI ทำงานไม่สำเร็จหรือตอบผิดรูปแบบ <b>ระบบคืนเครดิตทั้งหมด</b></li>
</ul>

<?= $h3('ai', 'ai-rounds') ?>
<p>หน้า <b>ตามคำถามการวิจัย</b> → ปุ่ม <b><i class="bi bi-stars"></i> AI ช่วย</b> ที่หัวคอลัมน์ของรอบ 1–3</p>
<ul>
    <li>รอบ 1: AI สรุปประเด็นจากคำตอบที่ยังไม่มีประเด็น</li>
    <li>รอบ 2–3: AI ยกประเด็นจากรอบก่อนหน้า (ต้องมีประเด็นรอบก่อนแล้ว)</li>
    <li>ทำครั้งละไม่เกิน <?= $batch ?> คำตอบ ถ้ายังเหลือระบบจะแจ้งให้กดอีกครั้ง</li>
    <li>ใช้ <b>รับทั้งหมด</b> / <b>ปฏิเสธทั้งหมด</b> ที่หัวการ์ดคำถามได้</li>
</ul>

<?= $h3('ai', 'ai-synth') ?>
<p>เลือก <b>แสดงถึงรอบ: สังเคราะห์ (IPO)</b> → ปุ่ม <b><i class="bi bi-stars"></i> AI สังเคราะห์ IPO</b>
AI รวมประเด็นรอบ 1–3 ของคำถามนั้นให้เหลือกลุ่มน้อยที่สุดและจัดลง Input / Process / Output
แต่ละกลุ่มแสดง <b>"รวมจาก ... ประเด็น"</b> ให้ตรวจว่าสมาชิกถูกต้องก่อนรับ</p>

<?= $h3('ai', 'ai-concept') ?>
<p>หน้า <b>Conceptual map</b> → ปุ่ม <b><i class="bi bi-stars"></i> AI ช่วย</b> (มุมขวาบน)
AI เสนอแนวคิดระดับโครงการจากประเด็นสังเคราะห์ของทุกคำถาม พร้อมติ๊กช่องที่เกี่ยวข้องและบอก "ที่มา" ทุกแนวคิด</p>

<?= $h3('ai', 'ai-grounding') ?>
<p>ทุกงานของ AI อยู่ภายใต้กฎ <b>ห้ามตีความเกินข้อมูล</b>: ห้ามเดาสาเหตุ ความรู้สึก หรือความหมายแฝง, ห้ามใช้คำบอกปริมาณที่ไม่มีตัวเลขรองรับ, ห้ามใช้ความรู้ภายนอก</p>
<p>สำหรับการเขียนบทวิเคราะห์ มีการตรวจเพิ่ม 2 ชั้น:</p>
<ol>
    <li><b>รอบตรวจทาน</b> — AI ตัวที่สองอ่านร่างเทียบข้อมูลต้นฉบับแล้ว <i>ลบ</i> ข้อความที่ไม่มีหลักฐาน ระบบตรวจด้วยโปรแกรมว่าทำได้แค่ลบ (ห้ามเพิ่ม/แก้คำ)</li>
    <li><b>ตรวจคำพูด</b> — ทุกข้อความในเครื่องหมาย "..." ต้องเป็นถ้อยคำที่อยู่ในคำตอบของผู้ให้ข้อมูลรหัสนั้นจริง ไม่ตรงจะถูกตัดทิ้ง</li>
</ol>
<div class="alert alert-danger small mb-2"><b>ข้อจำกัด:</b> ข้อความที่ AI เรียบเรียงเอง (ไม่ได้ยกคำพูด) ยังอาจตีความเกินหรืออ้างผิดคนได้ <b>นักวิจัยต้องตรวจทานทุกครั้ง</b> ก่อนนำไปใช้</div>

<?= $h3('ai', 'ai-privacy') ?>
<ul>
    <li>ส่ง: ชื่อโครงการ คำถาม ข้อความคำตอบ และประเด็นที่วิเคราะห์แล้ว</li>
    <li><b>ไม่ส่ง</b>: ชื่อผู้ใช้ รหัสผู้ให้ข้อมูล อายุ หมายเหตุ — ระบบใช้เลขอ้างอิงชั่วคราว (#1, #2) แล้วแปลงกลับเป็นรหัสเองหลัง AI ตอบ</li>
</ul>

<?= $h2('book') ?>
<p>เมนู <b>ออกเล่ม → เล่มรายงาน / บทวิเคราะห์</b></p>
<?= $h3('book', 'book-write') ?>
<ul>
    <li>แต่ละคำถามการวิจัยแสดงตาราง IPO (จากประเด็นสังเคราะห์ที่รับแล้ว) และช่องเขียนบทวิเคราะห์</li>
    <li>เขียนเองแล้วกด <b>บันทึก</b> หรือกด <b><i class="bi bi-stars"></i> AI เขียนบทวิเคราะห์</b> ให้ AI ร่างพร้อมยกคำพูดและรหัสผู้ให้ข้อมูล แล้วแก้ไขต่อ</li>
    <li>ควร <a href="#ai-synth">สังเคราะห์ IPO</a> ก่อน บทวิเคราะห์จะเป็นระบบกว่า</li>
    <li>ป้ายด้านขวาบอกที่มา: <span class="badge text-bg-primary">AI เขียน (ยังไม่แก้ไข)</span> <span class="badge text-bg-info">AI เขียน · นักวิจัยแก้ไขแล้ว</span> <span class="badge text-bg-secondary">นักวิจัยเขียน</span></li>
    <li>กด <b>AI เขียนใหม่</b> แล้ว ฉบับเดิมถูกเก็บไว้ในระบบ ไม่หาย · ลบข้อความทั้งหมดแล้วบันทึก = เอาหัวข้อนั้นออกจากเล่ม</li>
</ul>
<?= $h3('book', 'book-summary') ?>
<p>ส่วนท้ายหน้าเป็น <b>บทที่ 5 สรุปผลการวิจัย</b> — AI สรุปจากบทวิเคราะห์รายคำถามที่บันทึกไว้ทั้งหมด (ควรทำบทวิเคราะห์ให้ครบก่อน)</p>
<?= $h3('book', 'book-export') ?>
<ul>
    <li><b>ดาวน์โหลด Word</b> — ไฟล์ .doc มีหน้าปก (ชื่อโครงการ คณะผู้วิจัย วันที่ พ.ศ.) บทที่ 4 พร้อมตาราง 4.1, 4.2 … และบทที่ 5 ฟอนต์ TH Sarabun 16pt</li>
    <li><b>พิมพ์ / PDF</b> — เปิดหน้าพร้อมพิมพ์ขนาด A4 กดปุ่มด้านบนแล้วเลือก "บันทึกเป็น PDF"</li>
</ul>

<?= $h2('credits') ?>
<?= $h3('credits', 'credits-what') ?>
<ul>
    <li>ทุกครั้งที่สั่ง AI จะหักเครดิตของผู้กด <b>1 เครดิต = <?= Html::encode(rtrim(rtrim(number_format($bahtPerCredit, 2), '0'), '.')) ?> บาท</b></li>
    <li>ก่อนเรียก AI ระบบ <b>กันเครดิตตามยอดประมาณ</b> แล้วหักตามที่ใช้จริงหลังทำเสร็จ (คืนส่วนเกินอัตโนมัติ) — จำนวนที่ใช้จริงแสดงในข้อความหลังทำงานทุกครั้ง</li>
    <li>ใช้มากน้อยตามความยาวข้อมูล: งานสรุปประเด็นต่อครั้งใช้น้อย ส่วนเขียนบทวิเคราะห์ใช้มากกว่า (มีรอบตรวจทานเพิ่ม)</li>
    <li>เครดิตไม่พอ ระบบจะแจ้งและไม่เรียก AI · AI ทำงานไม่สำเร็จ <b>คืนเครดิตทั้งหมด</b></li>
</ul>
<?= $h3('credits', 'credits-balance') ?>
<p>ยอดคงเหลือแสดงที่แถบด้านบน (<i class="bi bi-stars"></i> … เครดิต) กดเพื่อดู <b>ประวัติเครดิต</b> ในหน้าข้อมูลส่วนตัว</p>
<?= $h3('credits', 'credits-topup') ?>
<ol>
    <li>กดเมนู <b><i class="bi bi-cup-hot"></i> เลี้ยงกาแฟคนเขียนโปรแกรม</b> แล้วสแกน QR พร้อมเพย์</li>
    <li>ส่งสลิปให้ผู้ดูแลระบบ ผู้ดูแลจะเติมเครดิตให้ (เช่น 50 บาท = <?= (int)round(50 / max($bahtPerCredit, 0.0001)) ?> เครดิต)</li>
</ol>

<?= $h2('admin') ?>
<p class="small text-muted">เฉพาะผู้ดูแลระบบ — เมนู <b>ผู้ใช้</b> ที่แถบด้านบน (ตัวเลขสีเหลือง = จำนวนคนรออนุมัติ)</p>
<?= $h3('admin', 'admin-approve') ?>
<ul>
    <li><b>อนุมัติเป็นผู้ใช้ใหม่</b> — เปิดบัญชีใหม่<?php if ($welcome > 0): ?> และได้เครดิตต้อนรับ <?= $welcome ?> เครดิต<?php endif ?></li>
    <li><b>รวมกับบัญชีเดิม</b> — เลือกบัญชีระบบเดิมของคนนั้น เพื่อให้ได้โครงการเดิมคืน</li>
    <li><b>ปฏิเสธ</b> — ซ่อนคำขอ (คนเดิมขอใหม่ได้)</li>
</ul>
<?= $h3('admin', 'admin-users') ?>
<p>เพิ่มผู้ใช้ล่วงหน้าด้วย Gmail (เข้าครั้งแรกจะผูกให้อัตโนมัติ), เปลี่ยนสิทธิ์/สถานะ, ยกเลิกการผูก Google/LINE ที่ผูกผิดคน, ดูประวัติการเข้าระบบ</p>
<?= $h3('admin', 'admin-credits') ?>
<p>หน้าแก้ไขผู้ใช้ → การ์ด <b>เครดิต AI</b> ใส่จำนวน (บวก = เติม, ลบ = หัก) และหมายเหตุ เช่น "โอนพร้อมเพย์ 50 บาท" ทุกรายการถูกบันทึกในประวัติ</p>

<?= $h2('faq') ?>
<dl class="doc-faq">
    <dt>ไม่เห็นปุ่ม AI ช่วย</dt>
    <dd>ตรวจว่า (1) หัวหน้าโครงการเปิด "อนุญาตให้ใช้ AI" แล้ว (2) คุณมีสิทธิ์แก้ไข (ไม่ใช่ "ดูอย่างเดียว") (3) คำถามเป็นแบบข้อความ (4) ไม่มีข้อเสนอของ AI ค้างอยู่ (5) โครงการไม่ได้อยู่ในคลัง</dd>
    <dt>ลบข้อมูลผิด เอากลับได้ไหม</dt>
    <dd>ข้อมูลที่ลบในระบบถูกซ่อนไว้ ไม่ถูกลบถาวร ติดต่อผู้ดูแลระบบเพื่อกู้คืน</dd>
    <dt>นำเข้า Excel แล้วคำตอบไม่เข้า</dt>
    <dd>ใช้แม่แบบที่ดาวน์โหลดจากระบบ หัวคอลัมน์คำถามต้องขึ้นต้นด้วย <code>#เลขคำถาม</code> หรือตรงกับข้อความคำถามทุกตัวอักษร</dd>
    <dt>ประเด็นเดียวกันถูกนับแยกกันในหน้าประเด็นที่ได้</dt>
    <dd>ระบบนับตามข้อความที่ตรงกันทุกตัวอักษร ให้แก้ถ้อยคำให้เหมือนกัน</dd>
    <dt>AI เขียนบทวิเคราะห์แล้วนำไปใช้ได้เลยไหม</dt>
    <dd>ไม่ควร ต้องตรวจทานทุกครั้ง และควรระบุในระเบียบวิธีวิจัยว่าใช้ AI ช่วยในขั้นตอนใด</dd>
</dl>

        </div></div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.docs-toc { position: sticky; top: 5rem; max-height: calc(100vh - 6rem); overflow: auto; }
.docs-toc ul ul { padding-left: .75rem; margin-bottom: .25rem; }
.toc-link { display: block; padding: .15rem .5rem; border-radius: .25rem; color: var(--bs-body-color); text-decoration: none; font-size: .9rem; }
.toc-l1 { font-weight: 600; margin-top: .35rem; }
.toc-l2 { color: var(--bs-secondary-color); }
.toc-link:hover { background: #f0fdfa; color: var(--app-primary); }
.toc-link.active { background: #ccfbf1; color: var(--app-primary-dark); }
.docs-body { line-height: 1.75; }
.doc-h2 { font-size: 1.4rem; font-weight: 700; margin: 2rem 0 .75rem; padding-bottom: .35rem; border-bottom: 2px solid #ccfbf1; scroll-margin-top: 5rem; }
.doc-h2:first-child { margin-top: 0; }
.doc-h3 { font-size: 1.1rem; font-weight: 600; margin: 1.25rem 0 .5rem; color: var(--app-primary-dark); scroll-margin-top: 5rem; }
.doc-steps { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin: .5rem 0; }
.doc-steps div { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: .5rem; padding: .3rem .6rem; font-size: .9rem; }
.doc-steps b { color: var(--app-primary); }
.doc-faq dt { margin-top: .75rem; }
.doc-faq dd { margin-left: 1rem; color: var(--bs-secondary-color); }
details > summary { cursor: pointer; }
CSS);
// ไฮไลต์หัวข้อในสารบัญตามตำแหน่งที่อ่านอยู่
$this->registerJs(<<<JS
(function () {
    var links = document.querySelectorAll('.docs-toc-nav .toc-link');
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var heads = document.querySelectorAll('.doc-h2, .doc-h3');
    if (!('IntersectionObserver' in window)) return;
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting && map[e.target.id]) {
                links.forEach(function (a) { a.classList.remove('active'); });
                map[e.target.id].classList.add('active');
                map[e.target.id].scrollIntoView({block: 'nearest'});
            }
        });
    }, {rootMargin: '-80px 0px -70% 0px'});
    heads.forEach(function (h) { obs.observe(h); });
})();
JS);
