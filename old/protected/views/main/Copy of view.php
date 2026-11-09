			<script type="text/javascript">
      $('body').on('hidden.bs.modal', '.modal', function () {
        $(this).removeData('bs.modal');
      });
            </script>
            	<div class="ui-card-wrap">
					<div class="row">
						<div class="col-md-8 col-sm-6">
							<div class="card ">
								<div class="card-main">
									<div class="card-inner">
										<p class="card-heading"><?php echo $model->name;?></p>
										<p>
											<?php // echo $model->detail;?>										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-4 col-sm-3">
							<div class="card">
								<div class="card-main">
									<div class="card-action">
										<div class="card-action-btn pull-left">
											<div class="row">									
</div>
							
<a class="btn btn-green waves-green " data-toggle="modal" href="index.php?r=qution/create&idmain=<?php echo $model->id;?>" data-target="#myModal">
    <span class="fbtn-text fbtn-text-left">เพิ่มคำถาม</span>
 เพิ่มคำถาม <span class="icon icon-2x">assignment</span></a>

<!--
 <div class="col-md-3">
<a class="fbtn fbtn-lg fbtn-green waves-circle" data-toggle="modal" href="index.php?r=group/create&idmain=<?php echo $model->id;?>" data-target="#myModal">
<span class="icon icon-2x">face</span></a>
</div>
-->
<br>
<br>
<a class="btn btn-red " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=interview&idm=<?php echo $model->id;?>">
     <span class="fbtn-text">สัมภาษณ์</span>
  บันทึกผู้ให้ข้อมูล <span class="icon icon-2x">question_answer</span></a>
 
 </div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-6 col-sm-6">				
                        <h2 class="content-sub-heading"> <span class="icon icon-2x">assignment</span>คำถามข้อมูลพื้นฐาน</h2>
						<div class="tile-wrap" id="ui_tile_example_parent">

                                            <?php 
$r = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$model->id' and types='2'" ));
foreach($Qution as $Qutions) {
$r++;
?>							<div class="tile tile-collapse">
								<div data-parent="#ui_tile_example_parent" data-target="#ui_tile_exampl_<?php echo $r;?>" data-toggle="tile" >
									<div  class="tile-side pull-left" data-ignore="tile">
										<div class="avatar avatar-sm">
                                        <span class="waves-circle waves-brand-accent "><?php echo $r;?>.</span>
										</div>
									</div>
									<div class="tile-inner">
										<div class="text-overflow"><?php echo $Qutions->name;?></div>
									</div>
								</div>
								<div class="tile-active-show collapse"  id="ui_tile_exampl_<?php echo $r;?>">
									<div class="tile-sub">
										<p><?php echo $Qutions->name;?><small><?php if($Qutions->type_id=='2'){;?>
<a class="btn btn fbtn-green" data-toggle="modal" href="index.php?r=site/page&view=addanwer&idq=<?php echo $Qutions->id;?>" data-target="#myModal">
<span class="icon">add</span></a><br>
<?php 
$Anwer = Anwer::model()->findAll(array("condition"=>"qution_id = '$Qutions->id'"));
foreach($Anwer as $Anwers) {

?>
- <?php echo $Anwers->name;?> = <?php echo $Anwers->value;?>	<br>

								<?php };?>		<?php };?></small></p>
									</div>
								</div>
							</div>
<?php } ?>                 
 </div>          
						


						                        <h2 class="content-sub-heading"> <span class="icon icon-2x">assignment</span>คำถามการวิจัย</h2>
						<div class="tile-wrap" id="ui_tile_example_parent">

                                            <?php 
$r2 = 0;
$Qution = Qution::model()->findAll(array("condition"=>"main_id = '$model->id' and types='1'" ));
foreach($Qution as $Qutions) {
$r2++;
?>							<div class="tile tile-collapse">
								<div data-parent="#ui_tile_example_parent" data-target="#ui_tile_exampls_<?php echo $r2;?>" data-toggle="tile" >
									<div  class="tile-side pull-left" data-ignore="tile">
										<div class="avatar avatar-sm">
                                        <span class="waves-circle waves-brand-accent "><?php echo $r2;?>.</span>
										</div>
									</div>
									<div class="tile-inner">
										<div class="text-overflow"><?php echo $Qutions->name;?></div>
									</div>
								</div>
								<div class="tile-active-show collapse"  id="ui_tile_exampls_<?php echo $r2;?>">
									<div class="tile-sub">
										<p><?php echo $Qutions->name;?><small><?php if($Qutions->type_id=='2'){;?>
<a class="btn btn fbtn-green" data-toggle="modal" href="index.php?r=site/page&view=addanwer&idq=<?php echo $Qutions->id;?>" data-target="#myModal">
<span class="icon">add</span></a><br>
<?php 
$Anwer = Anwer::model()->findAll(array("condition"=>"qution_id = '$Qutions->id'"));
foreach($Anwer as $Anwers) {

?>
- <?php echo $Anwers->name;?> = <?php echo $Anwers->value;?>	<br>

								<?php };?>		<?php };?></small></p>
									</div>
								</div>
							</div>
<?php } ?>                 
 </div>          
								
						</div>

						<div class="col-md-6 col-sm-6">
							<div class="card">
								<div class="card-main">
									<div class="card-action">
										<div class="card-action-btn pull-left">
							<p class="card-heading">    จำนวน <?php  
$count = People::Model()->count("main_id=:field", array("field" => $model->id));
echo $count;

										?> ข้อมูล</p>								
							
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report1&idm=<?php echo $model->id;?>">&nbsp;
 <span class="icon icon-2x">assignment</span> รายงานข้อมูลตามผู้ให้ข้อมูล </a>
<br><br>
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report3&idm=<?php echo $model->id;?>">&nbsp;
<span class="icon icon-2x">assignment</span> รายงานข้อมูลตามคำถามการวิจัย </a>
<br><br>
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report2&idm=<?php echo $model->id;?>">&nbsp;
 <span class="icon icon-2x">insert_chart</span> สถิต </a>
<br><br>
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=tree&idm=<?php echo $model->id;?>&tree=1">&nbsp;
<span class="icon icon-2x">share</span> แผนผังความคิด</a>
<br><br>
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=tree&idm=<?php echo $model->id;?>&tree=2">&nbsp;
<span class="icon icon-2x">assignment</span> Typolopy</a>
<br><br>
<a class="btn btn-green waves-green " data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report4&idm=<?php echo $model->id;?>">&nbsp;<span class="icon icon-2x">assignment</span>
 ConceptualMap</a>

 
 </div>
										</div>
									</div>
								</div>
<!--


							<div class="card card-orange">
								<div class="card-main">
									<div class="card-inner">

									</div>
									<div class="card-action">
										<div class="card-action-btn pull-left">
											<a class="btn btn-flat waves-attach"  data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report1&idm=<?php echo $model->id;?>"><span class="icon">question_answer</span>&nbsp;ข้อมูลดิบ</a>
											<a class="btn btn-flat waves-attach" data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=report2&idm=<?php echo $model->id;?>"><span class="icon">insert_chart</span>&nbsp;สถิติ</a>
											<a class="btn btn-flat waves-attach" data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=tree&idm=<?php echo $model->id;?>"><span class="icon">share</span>&nbsp;tree</a>
										   <!-- 	<a class="btn btn-flat waves-attach" href="javascript:void(0)"><span class="icon">check</span>&nbsp;report</a>


										</div>
									</div>
								</div>
							</div>
						</div>

-->

</div>
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
