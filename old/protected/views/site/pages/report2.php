<?php // $this->layout = 'noTheme';?>
<?=$_GET['export']!=''?$this->layout = $_GET['export']:'';?>

<?php error_reporting( error_reporting() & ~E_NOTICE );?>

<div style="padding: 10px 30px 10px 30px">
      <h2 class="content-heading" align="center">สถิติ<?php echo Main::model()->findByPk($_GET['id'])->name;?><br>
จำนวน <?php  
$count = People::Model()->count("main_id=:field", array("field" => $_GET['id']));
echo $count;?> ข้อมูล
      </h2>
      <!--
						<div class="card">
							<div class="card-main">
								<div class="card-inner">
								<h4 align='center'>กลุ่มอายุ</h4>
									
                                </div>
 						</div>
  						</div>
-->
<a href="index.php?r=site/page&view=report1&id=<?=$_GET['id']?>&menu=submenu&export=excel" class="btn btn-info">export to excel</a>

  <?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$_GET[id]' and types ='2'"));
foreach($Qution as $Qutions) {
$r++;?>
					           <div class="box box-success">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-hdd-o" aria-hidden="true"></i> <?php echo $Qutions->name;?></h3>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">

								<div class="col-md-6 col-sm-12">
								<table class="table">
								<tr><th>ค่า</th><th>จำนวน</th></tr>
<?php 
$connection=Yii::app()->db;
$dataReader=$connection->createCommand("SELECT Count(dt_interview.peopleid),dt_interview.`value` FROM dt_interview WHERE dt_interview.qid = '$Qutions->id' GROUP BY dt_interview.`value`")->query();
		$dataReader->bindColumn(1,$value);
		$dataReader->bindColumn(2,$name);
		while($dataReader->read()!== false) { ?>
<tr><td><?php if($Qutions->type_id=='1'){echo  $name;}?>
	<?php if($Qutions->type_id=='2'){echo Anwer::model()->findByPk($name)->name;};?>
	<?php if($Qutions->type_id=='3'){if($name=='on'){echo 'ใช่';}else{echo 'ไม่ใช่';}};?>

</td><td><?php echo $value;?> (<?php echo $value/$count*100;?> %)</td></tr>
									<?php }?>
</table>

								</div>
<div class="col-md-6 col-sm-12">
	<iframe src="index.php?r=site/page&view=chartpie&qid=<?php echo $Qutions->id;?>" width="100%" height="400" frameBorder="0" scrolling="no">Browser not compatible.</iframe>


								</div>
                                </div>
 						</div>

<?php
}
?>



