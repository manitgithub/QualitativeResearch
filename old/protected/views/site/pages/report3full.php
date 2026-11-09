<?php //$this->layout = 'noTheme'; ?>
<?= $_GET['export'] != '' ? $this->layout = $_GET['export'] : ''; ?>

<?php error_reporting(error_reporting() & ~E_NOTICE); ?>


<?php if ($_GET['delinterpret'] != '') {
	//$Reader=Yii::app()->db->createCommand("DELETE FROM `dt_interpret` WHERE (`id`='$_GET[delinterpret]') ")->query();
	$Reader = Yii::app()->db->createCommand("UPDATE `dt_interpret` SET `status` = 'D' WHERE `dt_interpret`.`id` = '$_GET[delinterpret]'; ")->query();
}
?>
<?php if ($_GET['delanwer'] != '') {
	//$Reader=Yii::app()->db->createCommand("DELETE FROM `dt_interview` WHERE (`id`='$_GET[delanwer]') ")->query();
	$Reader = Yii::app()->db->createCommand("UPDATE `dt_interview` SET `status` = 'D' WHERE `dt_interview`.`id` = '$_GET[delanwer]'; ")->query();

}
?>

<div style="padding: 10px 30px 10px 30px">
	<h2 class="content-heading" align="center">
		รายงานข้อมูลตามคำถามการวิจัย<br><?php echo Main::model()->findByPk($_GET['id'])->name; ?><br>
		จำนวน <?php
		$count = People::Model()->count("main_id=:field", array("field" => $_GET['id']));
		echo $count; ?> ข้อมูล
	</h2>

	<!--
						<div class="card">
							<div class="card-main">
								<div class="card-inner">
								<h4 align='center'>กลุ่มอายุ</h4>
									
								</div>
						 </div>
						  </div>
-->
	<a href="index.php?r=site/page&view=report3full&id=<?= $_GET['id'] ?>&menu=submenu&qid=<?= $_GET['qid'] ?>&export=word"
		class="btn btn-info">export to Word</a><br>
	เลือกคำถาม
	<select class="form-control"
		onchange="location.replace('https://nurseapp.hccrg.org/index.php?r=site/page&view=report3full&id=<?= $_GET['id'] ?>&menu=submenu&qid='+this.value, '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=600,top=50 ' )">
		<option value="">----เลือกคำถาม------------</option>

		<?php
		$Qution = Qution::model()->findAll(array("condition" => "main_id = '$_GET[id]' and status !='D'"));
		foreach ($Qution as $Qutions) {
			$h++; ?>
			<option value="<?= $Qutions->id ?>" <?= $Qutions->id == $_GET['qid'] ? "selected" : ""; ?>><?= $Qutions->name; ?></option>
		<?php } ?>
	</select>

	<div class="box box-success">
		<div class="box-body">

			<h4 align='center'><?= Qution::model()->findBypk($_GET['qid'])->name; ?> </h4>
			<div class="col-md-12 col-sm-12">



				<table width="100%" class="table">
					<tr>
						<th width="50%">ยกประเด็น 2 </th>
						<th width="50%">ยกประเด็น 1 </th>
					</tr>
					<?php
					$connection = Yii::app()->db;
					//$dataReader=$connection->createCommand("SELECT dt_interview.peopleid,dt_interview.`value`,id FROM dt_interview WHERE dt_interview.qid = '$Qutions->id' order by dt_interview.`value` desc ")->query();
					$dataReader = $connection->createCommand("SELECT
dt_interpret.id,
dt_interpret.`name`,
count(*),anwer_id
FROM
dt_interpret
WHERE qid = '$_GET[qid]' and dt_interpret.channel = '3'  and dt_interpret.status !='D'
GROUP BY dt_interpret.`name`
")->query();

					$dataReader->bindColumn(1, $Aid);
					$dataReader->bindColumn(2, $name);
					$dataReader->bindColumn(3, $count);
					$dataReader->bindColumn(4, $anwer_id);
					//if($pname=='$peopleid'){echo $peopleid = $pname;} else {$peopleid = $pname; }
					while ($dataReader->read() !== false) { ?>
						<tr>
							<td><?php echo $name; ?> [<?php echo $count; ?>] <?= $anwer_id ?></td>
							<td>
								<?php $qid = $_GET['qid'];
								$dataReader5 = Yii::app()->db->createCommand("
	   SELECT
dt_interpret.anwer_id
FROM
dt_interpret
WHERE qid = '$_GET[qid]' and dt_interpret.channel = '3' and  dt_interpret.name = '$name'  and dt_interpret.status !='D'
GROUP BY dt_interpret.`anwer_id`
	   ")->query();
								$dataReader5->bindColumn(1, $anwer_id);
								while ($dataReader5->read() !== false) {
									$k++;
									?>
									<table class="table">
										<?php $A2 = Interpret::model()->findBypk($anwer_id); ?>		<?= $A2->name ?>[<?php echo Interpret::model()->count(array("condition" => "name = '$A2->name' and channel = '2' and qid = '$qid' and status !='D'")); ?>]<br>

										<?php
										$dataReader3 = Yii::app()->db->createCommand("SELECT dt_interpret.anwer_id FROM dt_interpret WHERE dt_interpret.qid = '$qid' and  dt_interpret.name = '$A2->name' and channel='2' and status !='D'")->query();
										$dataReader3->bindColumn(1, $anwer_id);
										while ($dataReader3->read() !== false) {
											?>
											<strong>สรุป</strong>>>>>>>>>>>>>>
											<?php $A3 = Interpret::model()->findBypk($anwer_id); ?>			<?= $A3->name ?>[<?php echo Interpret::model()->count(array("condition" => "name = '$A3->name' and channel = '1' and qid = '$qid' and status !='D' ")); ?>]<br>
											<?php
											$dataReader4 = Yii::app()->db->createCommand("SELECT dt_interpret.anwer_id FROM dt_interpret WHERE dt_interpret.qid = '$qid' and  dt_interpret.name = '$A3->name' and channel='1' and status !='D'")->query();
											$dataReader4->bindColumn(1, $anwer_id);
											while ($dataReader4->read() !== false) {
												?>
												<strong>ข้อมูลดิบ</strong>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
												<?php $A4 = Interview::model()->findBypk($anwer_id); ?>				<?= $A4->value ?>[<?php echo People::model()->findBypk($A4->peopleid)->name; ?>]<br>

												<?php
											}
											?>
											<?php
										}
										?>
									<?php } ?>
								</table>
							</td>

						</tr>
						<br>
					<?php } ?>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
				</table>

			</div>

		</div>

	</div>