<?php //$this->layout = 'noTheme';?>
<?=$_GET['export']!=''?$this->layout = $_GET['export']:'';?>

<?php error_reporting( error_reporting() & ~E_NOTICE );?>

            <?php if($_GET['delinterpret']!=''){
				$Reader=Yii::app()->db->createCommand("DELETE FROM `dt_interpret` WHERE (`id`='$_GET[delinterpret]') ")->query();
			}
			?>
            <?php if($_GET['delanwer']!=''){
				$Reader=Yii::app()->db->createCommand("DELETE FROM `dt_interview` WHERE (`id`='$_GET[delanwer]') ")->query();
			}
			?>

<div style="padding: 10px 30px 10px 30px">
      <h2 class="content-heading" align="center">รายงานข้อมูลตามคำถามการวิจัย<br><?php echo Main::model()->findByPk($_GET['id'])->name;?><br>
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
-->            <a href="index.php?r=site/page&view=report3&id=<?=$_GET['id']?>&menu=submenu&export=excel" class="btn btn-info">export to excel</a>

  <?php 
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$_GET[id]'"));
foreach($Qution as $Qutions) {
$h++;?>
           <div class="box box-success">
            <div class="box-body">

								<h4 align='center'><?php echo $Qutions->name;?> </h4>
								<div class="col-md-12 col-sm-12">
								<table class="table" id="example<?=$h?>">
								<thead><tr><th width="10%">รหัสผู้ให้สัมภาษณ์</th><th width="20%">ข้อมูลที่ได้</th><th width="15%">สรุปประเด็น</th></tr></thead><tbody>
<?php 
$connection=Yii::app()->db;
//$dataReader=$connection->createCommand("SELECT dt_interview.peopleid,dt_interview.`value`,id FROM dt_interview WHERE dt_interview.qid = '$Qutions->id' order by dt_interview.`value` desc ")->query();
$dataReader=$connection->createCommand("SELECT
dt_people.`name`,
dt_interview.`value`,
dt_interview.id,
dt_interview.peopleid
FROM
dt_interview
INNER JOIN dt_people ON dt_people.id = dt_interview.peopleid
WHERE dt_interview.qid = '$Qutions->id'
order by dt_people.`name`")->query();

		$dataReader->bindColumn(1,$peopleid);
		$dataReader->bindColumn(2,$name);
		$dataReader->bindColumn(3,$id);
		$dataReader->bindColumn(4,$pid);

		//if($pname=='$peopleid'){echo $peopleid = $pname;} else {$peopleid = $pname; }
		while($dataReader->read()!== false) { ?>
<tr>
<td><?php echo $peopleid;
echo $pname;
?>
<?php if($_GET['export']==''){?>
<a href="#" class="btn btn-warning btn-xs" onClick="javascript:window.open('index.php?r=people/update&id=<?php echo $pid;?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a><?php } ?>
 </td>
<td><?php if($Qutions->type_id=='1'){echo  $name;}?>
	<?php if($Qutions->type_id=='2'){echo Anwer::model()->findByPk($name)->name;};?>
	<?php if($Qutions->type_id=='3'){if($name=='on'){echo 'ใช่';}else{echo 'ไม่ใช่';}};?>
<a href="#" class="btn btn-warning btn-xs" onClick="javascript:window.open('index.php?r=Interview/update&id=<?php echo $id;?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
<a href="index.php?r=site/page&view=report3&id=<?php echo $_GET[id];?>&menu=submenu&delanwer=<?php echo $id;?>" class="btn btn-danger btn-xs"><i class="fa fa-trash" aria-hidden="true"></i></a>


</td>
<td>
<?php if($_GET['export']==''){?>
<a href="#"onClick="javascript:window.open('index.php?r=interpret/create&id=<?php echo $id;?>&qid=<?php echo $Qutions->id;?>&channel=1', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>เพิ่มสรุปประเด็น</span><br></a>
<?php } ?>
<?php 
$r = 0;
$Interpret = Interpret::model()->findAll(array("condition"=>"anwer_id = '$id' and channel = '1'"));
foreach($Interpret as $Interprets) {
$r++;
echo 'ครั้งที่ '.$r.'::';
echo $Interprets->name;?>
<a href="#" class="btn btn-warning btn-xs" onClick="javascript:window.open('index.php?r=Interpret/update&id=<?php echo $Interprets->id;?>&channel=1', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
<a href="index.php?r=site/page&view=report3&id=<?php echo $_GET[id];?>&menu=submenu&delinterpret=<?php echo $Interprets->id;?>" class="btn btn-danger btn-xs"><i class="fa fa-trash" aria-hidden="true"></i></a>

<?php
echo '<hr>';
}
?>
</td>


</tr>
									<?php }?> </tbody>
</table>

								</div>

								</div>

								</div>
                                
             
<?php
}
?>


           
