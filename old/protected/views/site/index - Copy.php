				<div class="ui-card-wrap">
					<div class="row">
                    <?php 
$r = 0;
$Main = Main::model()->findAll(array("condition"=>" userkey = '1'"));
foreach($Main as $Mains) {
$r++;
?>

						<div class="col-md-4 col-sm-6">
							<div class="card">
								<div class="card-main">
									<div class="card-inner">
										<p class="card-heading"><?php echo $Mains->name;?></p>
										<p>
											<?php echo $Mains->detail;?>										</p>
									</div>
									<div class="card-action">
										<div class="card-action-btn pull-left">
											<a class="btn btn-flat waves-attach" href="index.php?r=main/view&id=<?php echo $Mains->id;?>"><span class="icon">check</span>&nbsp;เพิ่มคำถามงานวิจัย</a>
										</div>
									</div>
								</div>
							</div>
						</div>
         <?php } ?>               
                        
                        						<div class="col-md-4 col-sm-6">
							<div class="card">
								<div class="card-main">
									<div class="card-inner">
                                    <br>
										<p class="card-heading">
                                        
<a class="fbtn fbtn-lg waves-attach waves-circle" data-toggle="modal" href="index.php?r=main/create" data-target="#qution"><span class="icon">add</span></a>
                                        
                                        </p><br>
									</div>
								</div>
							</div>
						</div>

                        
					</div>
				</div>
                
	<div aria-hidden="true" class="modal fade" id="qution" role="dialog" tabindex="-1">
		<div class="modal-dialog modal-full">
			<div class="modal-content">
			</div>
		</div>
	</div>
