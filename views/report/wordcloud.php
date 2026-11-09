<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\Question[] $questions */
/** @var int|string|null $questionId */
/** @var int $round */
/** @var string[] $texts */

use app\models\Interpretation;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Word cloud';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('https://cdn.jsdelivr.net/npm/wordcloud@1.2.2/src/wordcloud2.min.js');
$textsJson = Json::htmlEncode(array_values($texts));
$this->registerJs(<<<JS
(function () {
  var texts = $textsJson;
  var canvas = document.getElementById('cloud');
  var table = document.getElementById('freq');
  var stop = new Set(['และ','ที่','ใน','ของ','การ','ความ','เป็น','มี','ได้','ให้','ไม่','จะ','ก็','กับ','แต่','หรือ','ว่า','นี้','นั้น','แล้ว','ซึ่ง','โดย','จาก','เพื่อ','อยู่','คือ','ไป','มา','ๆ','ด้วย','เรา','เขา','ค่ะ','ครับ','คะ','นะ']);

  function count(mode) {
    var freq = new Map();
    if (mode === 'phrase') {
      texts.forEach(function (t) { t = t.replace(/\s+/g, ' ').trim(); if (t) freq.set(t, (freq.get(t) || 0) + 1); });
    } else {
      var seg = (window.Intl && Intl.Segmenter) ? new Intl.Segmenter('th', {granularity: 'word'}) : null;
      texts.forEach(function (t) {
        var words = seg ? Array.from(seg.segment(t)).filter(function (s) { return s.isWordLike; }).map(function (s) { return s.segment; })
                        : t.split(/[\s,\.]+/);
        words.forEach(function (w) {
          w = w.trim().toLowerCase();
          if (w.length < 2 || stop.has(w) || /^\d+$/.test(w)) return;
          freq.set(w, (freq.get(w) || 0) + 1);
        });
      });
    }
    return Array.from(freq.entries()).sort(function (a, b) { return b[1] - a[1]; }).slice(0, 150);
  }

  function draw() {
    var mode = document.querySelector('input[name=wc-mode]:checked').value;
    var list = count(mode);
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = Math.max(380, Math.round(canvas.width * 0.5));
    if (!list.length) { canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height); table.innerHTML = '<tr><td class="text-muted">ไม่มีข้อมูล</td></tr>'; return; }
    var max = list[0][1];
    var base = mode === 'phrase' ? 30 : 60;
    WordCloud(canvas, {
      list: list,
      fontFamily: 'Sarabun, sans-serif',
      weightFactor: function (n) { return Math.max(12, base * Math.sqrt(n / max)) * canvas.width / 1000 + 6; },
      color: function () { var c = ['#0f766e','#155e75','#2563eb','#7c3aed','#be185d','#b45309']; return c[Math.floor(Math.random() * c.length)]; },
      rotateRatio: 0, gridSize: 8, shrinkToFit: true, drawOutOfBound: false
    });
    table.innerHTML = list.slice(0, 40).map(function (e) {
      var d = document.createElement('td'); d.textContent = e[0];
      return '<tr>' + d.outerHTML + '<td class="text-end">' + e[1] + '</td></tr>';
    }).join('');
  }
  document.querySelectorAll('input[name=wc-mode]').forEach(function (r) { r.addEventListener('change', draw); });
  draw();
  window.addEventListener('resize', function () { clearTimeout(window.__wcT); window.__wcT = setTimeout(draw, 300); });
})();
JS);
?>
<div class="page-header"><h1><?= $this->title ?></h1></div>

<form method="get" class="row g-2 mb-3" action="<?= Url::to(['wordcloud', 'projectId' => $project->id]) ?>">
    <div class="col-md-6">
        <select name="questionId" class="form-select" onchange="this.form.submit()">
            <option value="">— ทุกคำถาม —</option>
            <?php foreach ($questions as $i => $q): ?>
                <option value="<?= $q->id ?>" <?= (string)$questionId === (string)$q->id ? 'selected' : '' ?>><?= $i + 1 ?>. <?= Html::encode(mb_strimwidth($q->title, 0, 100, '…')) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="round" class="form-select" onchange="this.form.submit()">
            <?php foreach (Interpretation::roundOptions() as $r => $label): ?>
                <option value="<?= $r ?>" <?= $r === $round ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-center gap-3">
        <div class="form-check"><input class="form-check-input" type="radio" name="wc-mode" id="m-word" value="word" checked><label class="form-check-label" for="m-word">ตัดคำ</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="wc-mode" id="m-phrase" value="phrase"><label class="form-check-label" for="m-phrase">ทั้งประเด็น</label></div>
    </div>
</form>

<div class="row g-3">
    <div class="col-xl-9"><div class="card"><div class="card-body"><canvas id="cloud"></canvas></div></div></div>
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header">ความถี่สูงสุด <span class="text-muted small">(<?= count($texts) ?> ประเด็น)</span></div>
            <div style="max-height: 480px; overflow: auto"><table class="table table-sm mb-0" id="freq"></table></div>
        </div>
    </div>
</div>
