<?php // $this->layout = 'noTheme';?>
<?php error_reporting( error_reporting() & ~E_NOTICE );?>
           <div class="box box-success">
            <div class="box-body">
      <h2 class="content-heading" align="center">
ConceptualMap 1 
      </h2>
*ข้อมูลจากตีความ 3 
<table class="table " >
	<tr align="center">
		<th align="center" width="33%"><div align="center">Input</div></th>
		<th align="center" width="33%"><div align="center">Process</div></th>
		<th align="center" width="33%"><div align="center">Output</div></th>                
    </tr>
    	<tr>
		<th>
        <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_interpret.`name` FROM dt_qution INNER JOIN dt_interpret ON dt_interpret.qid = dt_qution.id Where dt_qution.main_id='$_GET[id]' and dt_interpret.types='1' and channel='3'" )->query();
        $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {
$i++;
if($i==1){echo '';} else {echo '<br>';}?>		
<?php echo $name;?>
<?php  } ?>        </th>
		<th>        <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_interpret.`name` FROM dt_qution INNER JOIN dt_interpret ON dt_interpret.qid = dt_qution.id Where dt_qution.main_id='$_GET[id]' and dt_interpret.types='2' and channel='3'")->query();
        $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {
$i++;
if($i==1){echo '';} else {echo '<br>';}?>		
<?php echo $name;?>
<?php  } ?></th>
		<th>        <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_interpret.`name` FROM dt_qution INNER JOIN dt_interpret ON dt_interpret.qid = dt_qution.id Where dt_qution.main_id='$_GET[id]' and dt_interpret.types='3' and channel='3'")->query();
        $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {
$i++;
if($i==1){echo '';} else {echo '<br>';}?>		
<?php echo $name;?>
<?php  } ?></th>                
    	</tr>
</table>

</div></div>