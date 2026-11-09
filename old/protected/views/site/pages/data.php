<?php $this->layout = 'noThemes';?>

{
 "name": "<?php echo ereg_replace('[[:space:]]+', '', trim(Main::model()->findByPk($_GET['idm'])->name));?>",
 "children": [
<?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$_GET[idm]' and types = '1' and status !='D'"));
foreach($Qution as $Qutions) {
$r++;
if($r==1){echo '';} else {echo ',';}
?>  
    {
     "name": "<?php echo ereg_replace('[[:space:]]+', '', trim($Qutions->name));?>",
     "children": [
<?php 
$qid = $Qutions->id;
$connection=Yii::app()->db;
$i = 0;
$dataReader=$connection->createCommand("SELECT Count(dt_interview.peopleid),dt_interview.`value` FROM dt_interview WHERE dt_interview.qid = '$qid' GROUP BY dt_interview.`value` and dt_interview.status !='D'")->query();
        $dataReader->bindColumn(1,$value);
        $dataReader->bindColumn(2,$name);

        while($dataReader->read()!== false) {
if(Qution::model()->findByPk($qid)->type_id == '1'){ $name = $name;}
if(Qution::model()->findByPk($qid)->type_id == '2'){ $name = Anwer::model()->findByPk($name)->name;}
if(Qution::model()->findByPk($qid)->type_id == '3'){if($name=='on'){$name= 'ใช่';}else{$name= 'ไม่ใช่';}}
$i++;
if($i==1){echo '';} else {echo ',';}
?>
      {"name": "<?php echo ereg_replace('[[:space:]]+', '', trim($name));?>"}
<?php } ?>   
     ]
    }
<?php } ?>    

 ]
}