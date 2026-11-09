<?php $this->layout = 'noThemes';?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/wordcloud.js"></script>
<div id="container" style="width:600px;height:300px;"></div>
<script type="application/javascript">
var text = '<?php $Interpret = Interpret::model()->findAll(array("condition"=>"qid = '$_GET[qid]' and channel = '1'  and status !='D'")); foreach($Interpret as $Interprets) { echo trim(preg_replace('/\s+/', ' ', $Interprets->name))." ";}?>';
var lines = text.split(/[,\. ]+/g),
    data = Highcharts.reduce(lines, function (arr, word) {
        var obj = Highcharts.find(arr, function (obj) {
            return obj.name === word;
        });
        if (obj) {
            obj.weight += 1;
        } else {
            obj = {
                name: word,
                weight: 1
            };
            arr.push(obj);
        }
        return arr;
    }, []);

Highcharts.chart('container', {
    series: [{
        type: 'wordcloud',
        data: data,
        name: 'ความถี่'
    }],
    title: {
        text: '<?=Qution::model()->findBypk($_GET['qid'])->name;?>'
    }
});
</script>