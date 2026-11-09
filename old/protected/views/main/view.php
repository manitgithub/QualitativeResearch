			<script type="text/javascript">
      $('body').on('hidden.bs.modal', '.modal', function () {
        $(this).removeData('bs.modal');
      });
            </script>
            <?php if($_GET['del']!=''){
				//$Reader=Yii::app()->db->createCommand("DELETE FROM `dt_qution` WHERE (`id`='$_GET[del]') ")->query();
				$Reader=Yii::app()->db->createCommand("UPDATE `dt_qution` SET `status` = 'D' WHERE `dt_qution`.`id` = '$_GET[del]' ")->query();
			}
			?>
            	<div class="ui-card-wrap">
			<div class="row">
			<div class="col-md-12 col-sm-6">
          	<div class="box box-success">
            <div class="box-body"><h2><?php echo $model->name;?>    จำนวน <?php  
$count = People::Model()->count("main_id=:field", array("field" => $model->id));
echo $count;?> ข้อมูล            </h2></div>
            </div>
            </div>

							
<!--
 <div class="col-md-3">
<a class="fbtn fbtn-lg fbtn-green waves-circle" data-toggle="modal" href="index.php?r=group/create&idmain=<?php echo $model->id;?>" data-target="#myModal">
<span class="icon icon-2x">face</span></a>
</div>
-->
<br>
<br>
 
   						<div class="col-md-12 col-sm-6">
           <div class="box box-success">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-keyboard-o" aria-hidden="true"></i> คำถามการวิจัย
              <a data-toggle="modal" href="index.php?r=qution/create&idmain=<?php echo $model->id;?>" data-target="#myModal" class="btn btn-info">
                <i class="fa fa-plus"></i>
                <span>เพิ่มคำถาม</span>
              </a>
     <a href="#" class="btn btn-success" onClick="javascript:window.open('/index.php?r=site/page&view=interview&idm=<?php echo $model->id;?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )"><i class="fa fa-microphone" aria-hidden="true"></i><span> สัมภาษณ์ทุกคำถาม</span></a>

</h3>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">
            <table width="100%"class="table"   id="example1">
     				<thead>		
 <tr>
    <th width="72%">คำถาม</th>
    <th width="5%"><i class="fa fa-microphone" aria-hidden="true"></i></th>
    <th width="5%">แก้ไข</th>
    <th width="5%">ลบ</th>
  </tr>    				</thead>		<tbody>

     <?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$model->id' and types='1' and status !='D'" ));
foreach($Qution as $Qutions) {
$r++;
?>	
  <tr>
    <td><?php echo $Qutions->name;?></td>
    <td>
    <a href="#" class="btn btn-success" onClick="javascript:window.open('index.php?r=site/page&view=addinterview&idq=<?php echo $Qutions->id;?>&idm=<?php echo $model->id;?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )"><i class="fa fa-microphone" aria-hidden="true"></i></a>
    </td>
    <td><a href="#" class="btn btn-warning" onClick="javascript:window.open('index.php?r=Qution/update&id=<?php echo $Qutions->id;?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></td>
    <td><a href="index.php?r=main/view&id=<?php echo $model->id;?>&menu=submenu&del=<?php echo $Qutions->id;?>" class="btn btn-danger" onclick="return confirm('Del are you sure?')"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
  </tr>
	<?php } ?>  
     </tbody>
</table>

                                         

</div>
        
        
        
            <!-- /.box-body -->
          </div>                    
                        
                        				


 </div>


						<div class="col-md-12 col-sm-6">
           <div class="box box-success">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-hdd-o" aria-hidden="true"></i> คำถามข้อมูลพื้นฐาน</h3>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                                            <?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$model->id' and types='2'" ));
foreach($Qution as $Qutions) {
$r++;
?>	
<?php echo $Qutions->name;?>
<a href="index.php?r=main/view&id=<?php echo $model->id;?>&menu=submenu&del=<?php echo $Qutions->id;?>" class="btn btn-danger" onclick="return confirm('Del are you sure?')"><i class="fa fa-trash" aria-hidden="true"></i></a>
<?php if($Qutions->type_id=='2'){;?>
<a class="btn btn bg-green" data-toggle="modal" href="index.php?r=site/page&view=addanwer&idq=<?php echo $Qutions->id;?>" data-target="#myModal">+</a><br>
<?php 
$Anwer = Anwer::model()->findAll(array("condition"=>"qution_id = '$Qutions->id'"));
foreach($Anwer as $Anwers) {

?>
- <?php echo $Anwers->name;?> = <?php echo $Anwers->value;?>	<br>

								<?php };?><?php };?>	<br>	
	<?php } ?>  
</div>
            <!-- /.box-body -->
          </div>     






    <!-- Event Modal -->
<div id="myModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">

                <div class="modal-body">
                    <p>Loading...</p>
                </div>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc1/js/bootstrap.min.js"></script>


    <script type="text/javascript">
$('#myModal').on('hidden.bs.modal', function () {
  $(this).removeData('bs.modal');
});    </script>
