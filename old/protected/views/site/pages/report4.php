<?php // $this->layout = 'noTheme';?>
<?php error_reporting( error_reporting() & ~E_NOTICE );?>
           <div class="box box-success">
            <div class="box-body">
      <h2 class="content-heading" align="center">
ConceptualMap
      </h2>
<table class="table table-bordered" >
	<tr align="center">
		<th align="center" width="20%"><div align="center">Input</div></th>
		<th align="center" width="20%"><div align="center">Process</div></th>
		<th align="center" width="20%"><div align="center">Output</div></th>        
   	    <th  align="center" width="20%"><div align="center">Problem</div></th>
   	    <th  align="center" width="20%"><div align="center">Success</div></th>
                
    </tr>
    	<tr>
		<th>
        <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_conceptual.`name` FROM dt_conceptual  Where dt_conceptual.main_id='$_GET[id]' and dt_conceptual.input='1'" )->query();
 $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {?>		
- <?php echo $name;?><br>
<?php  } ?>        </th>
		<th>     
         <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_conceptual.`name` FROM dt_conceptual  Where dt_conceptual.main_id='$_GET[id]' and dt_conceptual.process='1'" )->query();
 $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {?>		
- <?php echo $name;?><br>
<?php  } ?>           
        
         </th>
		<th>  
  <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_conceptual.`name` FROM dt_conceptual  Where dt_conceptual.main_id='$_GET[id]' and dt_conceptual.output='1'" )->query();
 $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {?>		
- <?php echo $name;?><br>
<?php  } ?>             
            </th> 
		<th>  
  <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_conceptual.`name` FROM dt_conceptual  Where dt_conceptual.main_id='$_GET[id]' and dt_conceptual.problem='1'" )->query();
 $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {?>		
- <?php echo $name;?><br>
<?php  } ?>             
            </th> 
   		<th>  
  <?php 
$dataReader=Yii::app()->db->createCommand("SELECT dt_conceptual.`name` FROM dt_conceptual  Where dt_conceptual.main_id='$_GET[id]' and dt_conceptual.success='1'" )->query();
 $dataReader->bindColumn(1,$name);
        while($dataReader->read()!== false) {?>		
- <?php echo $name;?><br>
<?php  } ?>             
            </th>          
                        
            
                           
    	</tr>
</table>

</div></div>