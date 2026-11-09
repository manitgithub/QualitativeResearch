<?php $this->layout = 'noTheme';?>
<?php error_reporting( error_reporting() & ~E_NOTICE );?>
    <meta charset="UTF-8">

<div style="padding: 10px 10px 10px 10px">
    <link href="../../research/theme/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />

    <!-- Ionicons -->
    <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="../../research/theme/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <!-- AdminLTE Skins. Choose a skin from the css/skins 
         folder instead of downloading all of them to reduce the load. -->
    <link href="../../research/theme/dist/css/skins/_all-skins.min.css" rel="stylesheet" type="text/css" />

    	<link href="https://fonts.googleapis.com/css?family=Kodchasan" rel="stylesheet">
<style type="text/css">
body{
font-family: 'Kodchasan', sans-serif;
}
</style>
<?php 
if($_POST["hdnCmd"] == "Add" and $_POST["txtname"]!='')
{
	$idmain = $_GET['idm'];
	$date = date('Y-m-d');
	$Reader=Yii::app()->db->createCommand("INSERT INTO `dt_people` (`date`, `name`, `information_id`, `group_id`, `main_id`) VALUES ('$date', '".$_POST["txtname"]."', '".$_POST["txtinfo"]."', '1', '$idmain');")->query();

$pid = People::model()->findBySql("SELECT * FROM dt_people ORDER BY id DESC")->id; 

//echo $pid;
for($i=1;$i<=$_POST["hdnLine"];$i++)
	{
	if($_POST["q$i"]!='' and $_POST["value$i"]!=''){
	$Reader=Yii::app()->db->createCommand("INSERT INTO `dt_interview` (`peopleid`, `qid`, `value`) VALUES ('$pid', '".$_POST["q$i"]."', '".$_POST["value$i"]."')")->query();
	$anwer_id = Interview::model()->findBySql("SELECT * FROM dt_interview ORDER BY id DESC")->id; 


		if($_POST["interpret_1_$i"]!=''){
$Reader2=Yii::app()->db->createCommand("INSERT INTO `dt_interpret` (`name`, `types`, `anwer_id`, `qid`, `channel`) VALUES ('".$_POST["interpret_1_$i"]."', '".$_POST["interpret_types_1_$i"]."', '$anwer_id','".$_POST["q$i"]."','1')")->query();
			}
		if($_POST["interpret_2_$i"]!=''){
$Reader2=Yii::app()->db->createCommand("INSERT INTO `dt_interpret` (`name`, `types`, `anwer_id`, `qid`, `channel`) VALUES ('".$_POST["interpret_2_$i"]."', '".$_POST["interpret_types_2_$i"]."', '$anwer_id','".$_POST["q$i"]."','2')")->query();
			}
			if($_POST["interpret_3_$i"]!=''){
$Reader2=Yii::app()->db->createCommand("INSERT INTO `dt_interpret` (`name`, `types`, `anwer_id`, `qid`, `channel`) VALUES ('".$_POST["interpret_3_$i"]."', '".$_POST["interpret_types_3_$i"]."', '$anwer_id','".$_POST["q$i"]."','3')")->query();
			}			
	}
	}
				$this->redirect(array('main/viewm','id'=>$idmain,'menu'=>'submenu'));

	?>		
	
	<script type="text/javascript">
	    alert("บันทึกสำเร็จ!");
		window.opener.location.reload();
		window.close();
 		 </script>
<?php
}
?>





<form name="frmMain" method="post" action="<?php echo $_SERVER["PHP_SELF"];?>?r=site/page&view=addinterviewm&idm=<?php echo $_GET['idm']?>">
<input type="hidden" name="hdnCmd" value='Add' class="form-control">
<h1 class="content-heading" align="center"><?php echo Main::model()->findByPk($_GET['idm'])->name;?></h1>
                        						<h2 class="content-sub-heading"> ผู้ให้ข้อมูล</h2>
                                <div class="form-group form-group-label">
    								<label class="floating-label">รหัสผู้ให้ข้อมูล</label>
    								<input class="form-control" name='txtname' type="text">
								</div>
                                  <div class="form-group form-group-label">
    								<label class="floating-label">วิธีการเก็บข้อมูล</label>
									<select name="txtinfo" class="form-control">
                                    <option value=""></option>
  
            <?php 
			$DtInformation = DtInformation::model()->findAll();
foreach($DtInformation as $DtInformations) {
			?>                            
                                            <option value="<?php echo $DtInformations->id;?>"><?php echo $DtInformations->name;?></option>
                                                        <?php 
}			?>          
                                    </select>
								</div>                              
								<!--
                                <div class="form-group form-group-label">
    								<label class="floating-label">อายุ</label>
    								<input class="form-control" name='txtage' type="number">
								</div>
                                -->

<h2 class="content-sub-heading">คำถามการวิจัย</h2>
  <?php 
$Qution = Qution::model()->findAll(array("condition"=>"id = '$_GET[idq]' and types ='1'"));
foreach($Qution as $Qutions) {
$r++;?>
<input class="form-control" name="q<?php echo $r;?>" type="hidden" value='<?php echo $Qutions->id?>'>
                                <?php if($Qutions->type_id=='1'){;?>
                                <div class="form-group form-group-label">
    								<label class="floating-label"><?php echo $Qutions->name;?>
</label>
<textarea class="form-control textarea-autosize" class="form-control" name="value<?php echo $r;?>" rows="3"></textarea>
								</div>
<?php };?>
<?php if($Qutions->type_id=='3'){;?>
<div class="checkbox switch">
<label for="ui_switch_example_<?php echo $r;?>">
<input class="access-hide" id="ui_switch_example_<?php echo $r;?>" name="value<?php echo $r;?>" type="checkbox"><span class="switch-toggle"></span><?php echo $r.'). '.$Qutions->name;?>
</label>
</div>


<?php };?>
<?php if($Qutions->type_id=='2'){;?>

<h4> <?php echo $r.'). '.$Qutions->name;?></h4>
                                            <?php 
$Anwer = Anwer::model()->findAll(array("condition"=>"qution_id = '$Qutions->id'"));
foreach($Anwer as $Anwers) {

?>

												<div class="radiobtn radiobtn-adv">
													<label for="input_radio_example_<?php echo $Anwers->id;?>">
														<input class="access-hide" id="input_radio_example_<?php echo $Anwers->id;?>" name="value<?php echo $r;?>" type="radio" value="<?php echo $Anwers->id;?>"><?php echo $Anwers->name;?>														<span class="radiobtn-circle"></span><span class="radiobtn-circle-check"></span>
													</label>
												</div>
                                                		<?php };?>

<br>
                                		<?php };?>


								
<?php } ?>        
<input type="hidden" name="hdnLine" value="<?php echo $r;?>">

<button class="btn btn-green waves-attach waves-light" type="submit"> บันทึก </button>
</form>
 </div>          
