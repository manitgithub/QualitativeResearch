// ฟังก์ชันใช้ร่วมกันทั้งระบบ
(function () {
    'use strict';

    // ปุ่ม/ลิงก์ที่มี data-toggle-target จะสลับการแสดงผลของ element เป้าหมาย (ใช้กับฟอร์มแก้ไขประเด็นแบบ inline)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-toggle-target]');
        if (!btn) return;
        e.preventDefault();
        var el = document.querySelector(btn.getAttribute('data-toggle-target'));
        if (!el) return;
        el.classList.toggle('d-none');
        var input = el.querySelector('textarea, input[type=text]');
        if (!el.classList.contains('d-none') && input) input.focus();
    });

    // ขยาย textarea ตามเนื้อหา
    document.addEventListener('input', function (e) {
        if (e.target.matches('textarea[data-autosize]')) {
            e.target.style.height = 'auto';
            e.target.style.height = (e.target.scrollHeight + 2) + 'px';
        }
    });

    // Ctrl/Cmd + Enter เพื่อส่งฟอร์มจาก textarea
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && e.target.matches('textarea')) {
            var form = e.target.closest('form');
            if (form) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
        }
    });

    // เพิ่มช่องสรุปประเด็นในฟอร์มบันทึกการสัมภาษณ์
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-add-interp]');
        if (!btn) return;
        e.preventDefault();
        var list = document.getElementById(btn.getAttribute('data-add-interp'));
        var tpl = list.querySelector('template');
        var idx = list.querySelectorAll('.interp-row').length;
        var html = tpl.innerHTML.replace(/__i__/g, String(idx));
        tpl.insertAdjacentHTML('beforebegin', html);
        var rows = list.querySelectorAll('.interp-row');
        rows[rows.length - 1].querySelector('input').focus();
    });
})();
