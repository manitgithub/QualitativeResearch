<?php $this->layout = 'noThemes';?>

{
 "name": "<?php echo ereg_replace('[[:space:]]+', '', trim(Main::model()->findByPk($_GET['idm'])->name));?>",
 "children": [
<?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$_GET[idm]' and types = '1'  and status !='D'"));
foreach($Qution as $Qutions) {
$r++;
if($r==1){echo '';} else {echo ',';}
?>  
    {
     "name": "<?php echo ereg_replace('[[:space:]]+', '', trim($Qutions->name));?>",
     "children": [
<?php 
$i = 0;
$dataReader=Yii::app()->db->createCommand("SELECT dt_interpret.`name`,Count(*) As num,dt_interpret.anwer_id
 FROM dt_interpret WHERE dt_interpret.qid = '$Qutions->id' and channel='2'  and status !='D' GROUP BY dt_interpret.`name`
")->query();
        $dataReader->bindColumn(1,$name);
        $dataReader->bindColumn(2,$num);
        $dataReader->bindColumn(3,$anwer_id);
        while($dataReader->read()!== false) {
$i++;
if($i==1){echo '';} else {echo ',';}
?>
      {"name": "<?php echo ereg_replace('[[:space:]]+', '', trim($name));?>,[<?php echo $num?>]",
 	   "children": [  <?php $k = 0;
	   $dataReader3=Yii::app()->db->createCommand("SELECT dt_interpret.anwer_id FROM dt_interpret WHERE dt_interpret.qid = '$Qutions->id' and  dt_interpret.name = '$name' and channel='2'  and status !='D'")->query();
        $dataReader3->bindColumn(1,$anwer_id);
        while($dataReader3->read()!== false) {
$k++;
if($k==1){echo '';} else {echo ',';}
?>{"name":"<?php echo ereg_replace('[[:space:]]+', '', trim(People::model()->findBypk(Interview::model()->findBypk($anwer_id)->peopleid)->name));?>"}<?php } ?>]}
<?php } ?>   
     ]
    }
<?php } ?>    

 ]
}