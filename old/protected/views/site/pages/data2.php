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
$dataReader=Yii::app()->db->createCommand("SELECT name FROM dt_interpret WHERE dt_interpret.qid = '$Qutions->id' and channel='1'  and status !='D'")->query();
        $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {
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