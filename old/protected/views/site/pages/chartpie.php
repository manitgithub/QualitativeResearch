<?php $this->layout = 'noTheme';?>

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<style type="text/css">
${demo.css}
		</style>
        <script type="text/javascript">
$(function () {

    $(document).ready(function () {
    Highcharts.setOptions({
        colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4','#6379AA','#E04141','#7E6EB0']
    });

        // Build the chart
        $('#container').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: ''
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: true
                }
            },
            credits: {
    enabled: false
  },
            series: [{
                name: 'ร้อยละ',
                colorByPoint: true,
                data: [
<?php 
$qid = $_GET['qid'];
$connection=Yii::app()->db;
$dataReader=$connection->createCommand("SELECT Count(dt_interview.peopleid),dt_interview.`value` FROM dt_interview WHERE dt_interview.qid = '$qid' GROUP BY dt_interview.`value`")->query();
        $dataReader->bindColumn(1,$value);
        $dataReader->bindColumn(2,$name);
        while($dataReader->read()!== false) { 
if(Qution::model()->findByPk($qid)->type_id == '1'){ $name = $name;}
if(Qution::model()->findByPk($qid)->type_id == '2'){ $name = Anwer::model()->findByPk($name)->name;}
if(Qution::model()->findByPk($qid)->type_id == '3'){if($name=='on'){$name= 'ใช่';}else{$name= 'ไม่ใช่';}}

            ?>
                {
                    name: '<?php echo $name;?>',
                    y: <?php echo $value;?>
                },
                <?php } ?>]
            }]
        });
    });
});
        </script>
	</head>
	<body>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<div id="container" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto"></div>
