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

    // ---------- เพิ่ม/แก้/ลบประเด็น (ใช้ฟอร์มกลางชุดเดียวต่อหน้า) ----------
    function interpForm(cell, action, values) {
        var tpl = document.getElementById('interp-form-tpl');
        if (!tpl) return null;
        var open = cell.querySelector('.interp-form');
        if (open) { open.remove(); }
        var frag = tpl.content.cloneNode(true);
        var form = frag.querySelector('form');
        form.action = action;
        Object.keys(values).forEach(function (k) {
            var el = form.querySelector('[name="' + k + '"]');
            if (el) el.value = values[k] == null ? '' : values[k];
        });
        return form;
    }
    function returnUrlFor(el) {
        var row = el.closest('tr[id]');
        return location.pathname + location.search + (row ? '#' + row.id : '');
    }
    document.addEventListener('click', function (e) {
        var add = e.target.closest('[data-interp-add]');
        var edit = e.target.closest('[data-interp-edit]');
        var del = e.target.closest('[data-interp-delete]');
        var cancel = e.target.closest('[data-interp-cancel]');
        if (cancel) { e.preventDefault(); var f = cancel.closest('.interp-form'); if (f) { if (f.dataset.hid) { var h = document.getElementById(f.dataset.hid); if (h) h.classList.remove('d-none'); } f.remove(); } return; }
        if (!add && !edit && !del) return;
        e.preventDefault();
        var urls = window.interpUrls || {};
        if (add) {
            var form = interpForm(add.parentElement, urls.create, {
                'Interpretation[response_id]': add.getAttribute('data-interp-add'),
                'Interpretation[round]': add.getAttribute('data-round'),
                'returnUrl': returnUrlFor(add)
            });
            if (!form) return;
            add.insertAdjacentElement('afterend', form);
            form.querySelector('textarea').focus();
        } else if (edit) {
            var item = edit.closest('.interp-item');
            if (item.nextElementSibling && item.nextElementSibling.classList.contains('interp-form')) return;
            var id = edit.getAttribute('data-interp-edit');
            var f2 = interpForm(item.parentElement, urls.update.replace('__id__', id), {
                'Interpretation[text]': edit.getAttribute('data-text'),
                'Interpretation[ipo]': edit.getAttribute('data-ipo'),
                'returnUrl': returnUrlFor(edit)
            });
            if (!f2) return;
            ['Interpretation[response_id]', 'Interpretation[round]'].forEach(function (n) { var x = f2.querySelector('[name="' + n + '"]'); if (x) x.remove(); });
            if (!item.id) item.id = 'interp-' + id;
            f2.dataset.hid = item.id;
            item.classList.add('d-none');
            item.insertAdjacentElement('afterend', f2);
            var ta = f2.querySelector('textarea'); ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length);
        } else if (del) {
            if (!confirm('ลบประเด็นนี้?')) return;
            var df = document.getElementById('interp-delete-form');
            df.action = urls.remove.replace('__id__', del.getAttribute('data-interp-delete'));
            df.querySelector('[name=returnUrl]').value = returnUrlFor(del);
            df.submit();
        }
    });
})();

