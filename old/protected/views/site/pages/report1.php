<?=$_GET['export']!=''?$this->layout = $_GET['export']:'';?>
<?php error_reporting( error_reporting() & ~E_NOTICE );?>

           <div class="box box-success">
            <div class="box-body">

<div style="padding: 10px 30px 10px 30px">
      <h2 class="content-heading" align="center">รายงานข้อมูลตามผู้ให้ข้อมูล<br><?php echo Main::model()->findByPk($_GET['id'])->name;?><br>
      
จำนวน <?php  
$count = People::Model()->count("main_id=:field", array("field" => $_GET['id']));
echo $count;?> ข้อมูล
      </h2> <a href="index.php?r=site/page&view=report1&id=<?=$_GET['id']?>&menu=submenu&export=word" class="btn btn-info">export to word</a>
 <a href="index.php?r=site/page&view=report1&id=<?=$_GET['id']?>&menu=submenu&export=excel" class="btn btn-info">export to excel</a>     
            <table width="100%"class="table"   id="example1">
     				<thead>	 <tr> <th width="72%">รายการ</th>   </tr></thead><tbody>
  <?php 
  $id =$_GET['id'];
$r = 0;
//$People = People::model()->findAll(array("condition"=>"main_id = '$_GET[id]'"));
   // $People = People::model()->findAll(
     //                         array(
     //                         'condition'=>'main_id="$_GET[id]"',
      //                        'group'=>'name'
       //                      ));  
							 
$People = People::model()->findAllBySql("SELECT * FROM dt_people  where main_id='$id' GROUP BY dt_people.`name`"); //and picPublish='1' and picStatus ='1'
foreach($People as $Peoples) {
$r++;?>
  <tr>
    <td>
<h2>
<?php echo $r;?>).รหัสผู้ให้ข้อมูล : <?php echo $Peoples->name;?> วันที่บันทึก <?php echo $Peoples->date;?></h2>
									  <?php 
$i = 0;
//$Interview = Interview::model()->findAll(array("condition"=>"peopleid = '$Peoples->id'"));
$Interview = Interview::model()->findAllBySql(" SELECT
dt_people.`name`,
dt_interview.qid,
dt_interview.`value`,
dt_interview.id
FROM
dt_people
INNER JOIN dt_interview ON dt_people.id = dt_interview.peopleid where dt_people.`name`='$Peoples->name' and dt_people.main_id = '$id'");
foreach($Interview as $Interviews) { 
$i++;?>
			<br>
			 &nbsp&nbsp&nbsp <u>ถาม</u> <?php echo $i;?>). <?php echo Qution::model()->findByPk($Interviews->qid)->name;?>		
			<br>
			 &nbsp&nbsp&nbsp <u>ตอบ</u>	
			 <?php if(Qution::model()->findByPk($Interviews->qid)->type_id == '1'){echo $Interviews->value;}?>	
			 <?php if(Qution::model()->findByPk($Interviews->qid)->type_id == '2'){echo Anwer::model()->findByPk($Interviews->value)->name;}?>
			 <?php if(Qution::model()->findByPk($Interviews->qid)->type_id == '3'){if($Interviews->value=='on'){echo 'ใช่';}else{echo 'ไม่ใช่';}}?>
<?php }?>
	
						
<hr>

<?php
}
?>

 						</div>
  						</div>


   </tr>  </tbody>
</table>
