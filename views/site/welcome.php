<?php

/**
 * หน้าแนะนำระบบ
 *
 * @var yii\web\View $this
 * @var array{users: int, projects: int, informants: int, issues: int} $stats
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'เปลี่ยนงานวิจัยเชิงคุณภาพให้ง่ายขึ้น';
$guest = Yii::$app->user->isGuest;
$f = Yii::$app->formatter;
$welcome = (int)(Yii::$app->params['ai.welcomeCredits'] ?? 0);
$cta = $guest
    ? Html::a('<i class="bi bi-box-arrow-in-right"></i> เริ่มใช้งานฟรี', ['/site/login'], ['class' => 'btn btn-light btn-lg fw-semibold px-4'])
    : Html::a('<i class="bi bi-folder2-open"></i> ไปที่โครงการของฉัน', ['/site/index'], ['class' => 'btn btn-light btn-lg fw-semibold px-4']);

$features = [
    ['bi-mic', 'บันทึกสัมภาษณ์ได้ทันที', 'กรอกคำตอบทุกข้อในหน้าเดียว หรือนำเข้าทีละหลายร้อยคนจาก Excel / CSV ด้วยแม่แบบที่ระบบสร้างให้'],
    ['bi-layers', 'ตีความเป็นรอบอย่างเป็นระบบ', 'สรุปประเด็น → ยกประเด็นรอบ 1 → รอบ 2 วางคำตอบดิบและประเด็นทุกรอบเคียงกันในตารางเดียว'],
    ['bi-diagram-3', 'สังเคราะห์ Input-Process-Output', 'รวมประเด็นของแต่ละคำถามเป็นกลุ่ม I / P / O และทำ Conceptual map ระดับโครงการ'],
    ['bi-bar-chart', 'รายงานพร้อมใช้ 7 แบบ', 'ประเด็นที่ได้พร้อมจำนวนผู้ให้ข้อมูล แผนผังความคิด Word cloud สถิติ และตาราง IPO — ส่งออก Excel ได้'],
    ['bi-stars', 'AI ช่วยวิเคราะห์', 'AI ช่วยสรุปประเด็น สังเคราะห์ และร่างบทวิเคราะห์ โดยนักวิจัยเป็นผู้ตรวจและตัดสินทุกข้อ'],
    ['bi-journal-text', 'ออกเล่มรายงาน', 'บทที่ 4 ผลการวิเคราะห์ และบทที่ 5 สรุปผล ดาวน์โหลดเป็น Word หรือพิมพ์เป็น PDF ได้ทันที'],
    ['bi-people', 'ทำงานเป็นทีม', 'หัวหน้าโครงการเชิญทีมงาน กำหนดสิทธิ์ แก้ไขได้ / ดูอย่างเดียว ทุกคนเห็นข้อมูลชุดเดียวกัน'],
    ['bi-shield-check', 'ปกป้องผู้ให้ข้อมูล', 'ใช้รหัสแทนชื่อ AI ปิดไว้จนกว่าหัวหน้าโครงการจะอนุญาต และไม่ส่งรหัส/อายุ/หมายเหตุของผู้ให้ข้อมูลออกนอกระบบ'],
];
$compare = [
    ['คัดลอกคำตอบลงตารางใน Word / Excel ทีละช่อง', 'บันทึกหรือนำเข้าครั้งเดียว ทุกหน้าดึงข้อมูลชุดเดียวกัน'],
    ['นับเองว่าใครพูดถึงประเด็นไหน กี่คน', 'ระบบนับจำนวนและรหัสผู้ให้ข้อมูลของทุกประเด็นอัตโนมัติ'],
    ['ไฟล์แยกกันหลายเวอร์ชันระหว่างทีม', 'ทีมทำงานบนโครงการเดียวกัน ข้อมูลล่าสุดเสมอ'],
    ['เขียนบทที่ 4 จากศูนย์ ยกคำพูดเองทีละข้อ', 'AI ร่างพร้อมยกคำพูดและรหัส แล้วนักวิจัยตรวจแก้'],
    ['จัดรูปแบบเล่มเอง', 'ดาวน์โหลดเล่ม Word / PDF พร้อมตาราง IPO'],
];
$steps = [
    ['bi-folder-plus', 'สร้างโครงการ', 'ตั้งชื่องานวิจัยและเชิญทีม'],
    ['bi-list-ol', 'เพิ่มคำถาม', 'คำถามการวิจัยและข้อมูลพื้นฐาน'],
    ['bi-mic', 'บันทึกข้อมูล', 'กรอกเองหรือนำเข้า Excel'],
    ['bi-layers', 'ตีความ/ยกประเด็น', 'รอบ 1–3 ทำเองหรือให้ AI ช่วย'],
    ['bi-diagram-3', 'สังเคราะห์ IPO', 'รวมเป็นกลุ่ม Input-Process-Output'],
    ['bi-journal-text', 'ออกเล่ม', 'Word / PDF พร้อมใช้'],
];
?>
<section class="landing-hero text-white">
    <div class="container py-5">
        <div class="row align-items-center g-4 py-lg-4">
            <div class="col-lg-7">
                <div class="badge rounded-pill text-bg-light text-primary mb-3 px-3 py-2"><i class="bi bi-stars"></i> มี AI ช่วยวิเคราะห์ — นักวิจัยเป็นผู้ตัดสิน</div>
                <h1 class="display-5 fw-bold lh-sm mb-3">เปลี่ยนงานวิจัยเชิงคุณภาพ<br>ให้ <span class="hero-accent">ง่ายขึ้น</span></h1>
                <p class="lead mb-4 opacity-90">
                    จากบทสัมภาษณ์ สู่ประเด็น สู่ Input-Process-Output และเล่มรายงาน — ครบในระบบเดียว
                    ทำงานเป็นทีมได้ ไม่ต้องสลับไฟล์ไปมา
                </p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= $cta ?>
                    <a href="#features" class="btn btn-outline-light btn-lg px-4">ระบบทำอะไรได้บ้าง</a>
                </div>
                <?php if ($guest && $welcome > 0): ?>
                    <div class="small mt-3 opacity-75"><i class="bi bi-gift"></i> ผู้ใช้ใหม่รับ <?= $welcome ?> เครดิต AI ฟรี · เข้าระบบด้วย Gmail หรือ LINE</div>
                <?php endif ?>
            </div>
            <div class="col-lg-5">
                <div class="hero-card shadow-lg">
                    <div class="small text-muted mb-2">ตัวอย่าง: ประเด็นที่ได้จากการสัมภาษณ์</div>
                    <div class="hero-row"><span class="badge bg-input">Input</span> ข้อจำกัดด้านเวลาและค่าใช้จ่าย <b>14</b></div>
                    <div class="hero-row"><span class="badge bg-input">Input</span> ขาดการสนับสนุนจากครอบครัว <b>12</b></div>
                    <div class="hero-row"><span class="badge bg-process">Process</span> ขอคำแนะนำจาก อสม. <b>5</b></div>
                    <div class="hero-row"><span class="badge bg-output">Output</span> แผลกดทับไม่เกิดซ้ำ <b>2</b></div>
                    <div class="hero-quote mt-3">"พอ อสม. มาสอนก็ดีขึ้น" <span class="text-muted">(ผู้ให้ข้อมูล T03)</span></div>
                    <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i> ข้อมูลสมมติเพื่อสาธิต · ตัวเลข = จำนวนผู้ให้ข้อมูล</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-stats">
    <div class="container">
        <div class="row g-3 text-center">
            <?php foreach ([
                ['bi-person-check', $stats['users'], 'นักวิจัยที่ใช้งาน'],
                ['bi-folder2-open', $stats['projects'], 'โครงการวิจัย'],
                ['bi-people', $stats['informants'], 'ผู้ให้ข้อมูลที่บันทึกแล้ว'],
                ['bi-tags', $stats['issues'], 'ประเด็นที่วิเคราะห์แล้ว'],
            ] as [$icon, $n, $label]): ?>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <i class="bi <?= $icon ?>"></i>
                        <div class="stat-num"><?= $f->asInteger($n) ?></div>
                        <div class="stat-label"><?= $label ?></div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <div class="text-center small text-muted mt-2">ตัวเลขจริงจากระบบ อัปเดตทุก 10 นาที</div>
    </div>
</section>

<section id="features" class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">ระบบทำอะไรได้บ้าง</h2>
            <p class="text-muted mb-0">ครอบคลุมทุกขั้นตอนของการวิเคราะห์ข้อมูลเชิงคุณภาพ</p>
        </div>
        <div class="row g-3">
            <?php foreach ($features as [$icon, $title, $text]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi <?= $icon ?>"></i></div>
                        <h3 class="h6 fw-bold mt-3"><?= $title ?></h3>
                        <p class="small text-muted mb-0"><?= $text ?></p>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<section class="py-5 bg-body-tertiary">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">งานวิจัยง่ายขึ้นอย่างไร</h2>
            <p class="text-muted mb-0">เทียบการวิเคราะห์แบบเดิมกับการใช้ระบบ</p>
        </div>
        <div class="compare mx-auto">
            <div class="compare-head"><div><i class="bi bi-x-circle"></i> แบบเดิม</div><div><i class="bi bi-check-circle"></i> ใช้ระบบ</div></div>
            <?php foreach ($compare as [$old, $new]): ?>
                <div class="compare-row"><div class="compare-old"><?= $old ?></div><div class="compare-new"><?= $new ?></div></div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">ใช้งานใน 6 ขั้นตอน</h2>
        </div>
        <div class="row g-3 steps">
            <?php foreach ($steps as $i => [$icon, $title, $text]): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="step-card h-100 text-center">
                        <div class="step-num"><?= $i + 1 ?></div>
                        <i class="bi <?= $icon ?> fs-3 text-primary"></i>
                        <div class="fw-semibold mt-1"><?= $title ?></div>
                        <div class="small text-muted"><?= $text ?></div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<section class="py-5 bg-body-tertiary">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold"><i class="bi bi-stars text-primary"></i> AI เป็นผู้ช่วย ไม่ใช่ผู้ตัดสิน</h2>
                <ul class="list-unstyled ai-points mt-3">
                    <li><i class="bi bi-check2-circle"></i> AI <b>เสนอ</b> ประเด็น กลุ่ม IPO และร่างบทวิเคราะห์ — นักวิจัยกด <b>รับ</b> หรือ <b>ปฏิเสธ</b> ทุกข้อ</li>
                    <li><i class="bi bi-check2-circle"></i> ห้ามตีความเกินข้อมูล: คำพูดที่ยกมาถูกตรวจกับคำตอบจริงของผู้ให้ข้อมูลทุกครั้ง</li>
                    <li><i class="bi bi-check2-circle"></i> บันทึกที่มาของทุกประเด็น (นักวิจัย / AI / AI แล้วนักวิจัยแก้) เพื่อเปิดเผยระเบียบวิธีได้</li>
                    <li><i class="bi bi-check2-circle"></i> ปิดไว้จนกว่าหัวหน้าโครงการจะอนุญาต และไม่ส่งรหัส อายุ หรือหมายเหตุของผู้ให้ข้อมูล</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="ai-demo shadow-sm">
                    <div class="small text-muted mb-2">คำตอบ: "ค่าแพมเพิสกับนมผงแพงมาก รายได้ไม่พอ ต้องกู้เพื่อนบ้าน"</div>
                    <div class="ai-suggestion d-flex justify-content-between"><span><i class="bi bi-stars text-primary"></i> ค่าแพมเพิสกับนมผงแพง <span class="badge text-bg-secondary">input</span></span><span><i class="bi bi-check-lg text-success"></i> <i class="bi bi-x-lg text-danger"></i></span></div>
                    <div class="ai-suggestion d-flex justify-content-between"><span><i class="bi bi-stars text-primary"></i> รายได้ไม่พอ <span class="badge text-bg-secondary">input</span></span><span><i class="bi bi-check-lg text-success"></i> <i class="bi bi-x-lg text-danger"></i></span></div>
                    <div class="ai-suggestion d-flex justify-content-between"><span><i class="bi bi-stars text-primary"></i> ต้องกู้เพื่อนบ้าน <span class="badge text-bg-secondary">process</span></span><span><i class="bi bi-check-lg text-success"></i> <i class="bi bi-x-lg text-danger"></i></span></div>
                    <div class="small text-muted mt-2">ข้อเสนอจาก AI รอให้นักวิจัยตรวจ (ข้อมูลสมมติ)</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-cta text-white text-center py-5">
    <div class="container">
        <h2 class="fw-bold mb-2">เริ่มวิเคราะห์งานวิจัยของคุณวันนี้</h2>
        <p class="opacity-90 mb-4">เข้าระบบด้วย Gmail หรือ LINE — ไม่ต้องตั้งรหัสผ่าน<?= $guest && $welcome > 0 ? " · รับ {$welcome} เครดิต AI ฟรี" : '' ?></p>
        <?= $cta ?>
    </div>
</section>
