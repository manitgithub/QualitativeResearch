<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var string $mode */
/** @var int $round */

use app\models\Interpretation;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'แผนผังความคิด';
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => $project->id]];
$this->params['breadcrumbs'][] = $this->title;

$modes = [
    'themes' => 'Typology (ประเด็น → ผู้ให้ข้อมูล)',
    'interpretations' => 'ประเด็นรายคำถาม',
    'answers' => 'คำตอบรายคำถาม',
];
$dataUrl = Json::htmlEncode(Url::to(['tree-data', 'projectId' => $project->id, 'mode' => $mode, 'round' => $round]));

$this->registerJsFile('https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js');
$this->registerJs(<<<JS
(function () {
  var container = document.getElementById('mindmap');
  var dx = 26, dy = 260, margin = {top: 20, right: 260, bottom: 20, left: 40};
  var trunc = function (s, n) { s = String(s || ''); return s.length > n ? s.slice(0, n) + '…' : s; };

  fetch($dataUrl).then(function (r) { return r.json(); }).then(function (data) {
    var root = d3.hierarchy(data);
    var tree = d3.tree().nodeSize([dx, dy]);
    var diagonal = d3.linkHorizontal().x(function (d) { return d.y; }).y(function (d) { return d.x; });

    root.x0 = 0; root.y0 = 0;
    root.descendants().forEach(function (d, i) {
      d.id = i; d._children = d.children;
      if (d.depth >= 2) d.children = null;   // เริ่มต้นแสดง 2 ระดับ
    });

    var svg = d3.select(container).append('svg').attr('font-size', 13);
    var gLink = svg.append('g').attr('class', 'links');
    var gNode = svg.append('g').attr('class', 'nodes');

    function update(source) {
      var nodes = root.descendants().reverse();
      var links = root.links();
      tree(root);
      var left = root, right = root;
      root.eachBefore(function (n) { if (n.x < left.x) left = n; if (n.x > right.x) right = n; });
      var height = right.x - left.x + margin.top + margin.bottom;
      var width = (d3.max(root.descendants(), function (d) { return d.y; }) || 0) + margin.left + margin.right;
      svg.attr('width', Math.max(width, container.clientWidth)).attr('height', Math.max(height, 200));
      var t = svg.transition().duration(document.hidden ? 0 : 300);
      gLink.attr('transform', 'translate(' + margin.left + ',' + (margin.top - left.x) + ')');
      gNode.attr('transform', 'translate(' + margin.left + ',' + (margin.top - left.x) + ')');

      var node = gNode.selectAll('g.node').data(nodes, function (d) { return d.id; });
      var nodeEnter = node.enter().append('g').attr('class', 'node')
        .attr('transform', function () { return 'translate(' + source.y0 + ',' + source.x0 + ')'; })
        .on('click', function (event, d) { d.children = d.children ? null : d._children; update(d); });
      nodeEnter.append('circle').attr('r', 5);
      nodeEnter.append('text').attr('dy', '0.32em')
        .attr('x', function (d) { return d._children ? -9 : 9; })
        .attr('text-anchor', function (d) { return d._children ? 'end' : 'start'; })
        .text(function (d) {
          var n = d.depth === 0 ? 60 : 38;
          return trunc(d.data.name, n) + (d.data.count > 1 ? ' [' + d.data.count + ']' : '');
        })
        .clone(true).lower().attr('stroke', 'white').attr('stroke-width', 4);
      nodeEnter.append('title').text(function (d) { return d.data.name; });

      var merged = node.merge(nodeEnter);
      merged.transition(t).attr('transform', function (d) { return 'translate(' + d.y + ',' + d.x + ')'; });
      merged.select('circle').attr('class', function (d) { return d._children && !d.children ? 'collapsed' : ''; });
      node.exit().transition(t).remove().attr('transform', function () { return 'translate(' + source.y + ',' + source.x + ')'; });

      var link = gLink.selectAll('path.link').data(links, function (d) { return d.target.id; });
      var linkEnter = link.enter().append('path').attr('class', 'link')
        .attr('d', function () { var o = {x: source.x0, y: source.y0}; return diagonal({source: o, target: o}); });
      link.merge(linkEnter).transition(t).attr('d', diagonal);
      link.exit().transition(t).remove()
        .attr('d', function () { var o = {x: source.x, y: source.y}; return diagonal({source: o, target: o}); });

      root.eachBefore(function (d) { d.x0 = d.x; d.y0 = d.y; });
    }
    update(root);

    document.getElementById('expand-all').addEventListener('click', function () {
      root.descendants().forEach(function (d) { d.children = d._children; }); update(root);
    });
    document.getElementById('collapse-all').addEventListener('click', function () {
      root.descendants().forEach(function (d) { if (d.depth >= 1) d.children = null; }); update(root);
    });
  });
})();
JS);
?>
<div class="page-header">
    <h1><?= $this->title ?></h1>
    <div class="d-flex gap-2 flex-wrap">
        <button id="expand-all" class="btn btn-outline-secondary btn-sm">ขยายทั้งหมด</button>
        <button id="collapse-all" class="btn btn-outline-secondary btn-sm">ย่อทั้งหมด</button>
    </div>
</div>

<form method="get" class="row g-2 mb-3" action="<?= Url::to(['mindmap', 'projectId' => $project->id]) ?>">
    <div class="col-md-5">
        <select name="mode" class="form-select" onchange="this.form.submit()">
            <?php foreach ($modes as $k => $label): ?>
                <option value="<?= $k ?>" <?= $k === $mode ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <?php if ($mode !== 'answers'): ?>
        <div class="col-md-3">
            <select name="round" class="form-select" onchange="this.form.submit()">
                <?php foreach (Interpretation::roundOptions() as $r => $label): ?>
                    <option value="<?= $r ?>" <?= $r === $round ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
        </div>
    <?php endif ?>
</form>

<div class="card"><div class="card-body p-0"><div id="mindmap" style="min-height: 400px"></div></div></div>
<p class="small text-muted mt-2">คลิกที่วงกลมเพื่อขยาย/ย่อ · ตัวเลขในวงเล็บคือจำนวนครั้งที่พบ</p>
