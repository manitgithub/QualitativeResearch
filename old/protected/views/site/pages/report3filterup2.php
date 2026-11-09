<?php //$this->layout = 'noTheme'; ?>
<?= $_GET['export'] != '' ? $this->layout = $_GET['export'] : ''; ?>
<?php
$qid = $_GET['qid'];
$Interpret = Interpret::model()->findAll(array("condition" => "qid = '$qid' and channel = '1'  and status !='D'"));
foreach ($Interpret as $Interprets) {
	$r++;
	$In[$Interprets->id] = $Interprets->name;
	$InID[$Interprets->id] = $Interprets->id;
	//$InA[$Interprets->id] = $Interprets->anwer_id;
	$InA[$Interprets->id][$Interprets->anwer_id] = $Interprets->anwer_id;

}
?>

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
	<a href="index.php?r=site/page&view=report3filterup2&id=<?= $_GET['id'] ?>&menu=submenu&export=excel&gid=<?= $_GET['gid'] ?>"
		class="btn btn-info">export to excel</a><br>
	เลือกคำถาม
	<select class="form-control"
		onchange="location.replace('https://nurseapp.hccrg.org/index.php?r=site/page&view=report3filterup2&id=<?= $_GET['id'] ?>&menu=submenu&qid='+this.value, '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=600,top=50 ' )">
		<option value="">----เลือกคำถาม------------</option>

		<?php
		$Qution = Qution::model()->findAll(array("condition" => "main_id = '$_GET[id]' and status !='D'"));
		foreach ($Qution as $Qutions) {
			$h++; ?>
			<option value="<?= $Qutions->id ?>" <?= $Qutions->id == $_GET['qid'] ? "selected" : ""; ?>><?= $Qutions->name; ?>
			</option>
		<?php } ?>
	</select>

	<div class="box box-success">
		<div class="box-body">

			<h4 align='center'><?= Qution::model()->findBypk($_GET['qid'])->name; ?> </h4>
			<div class="col-md-12 col-sm-12">
				<table class="table" id="example<?= $h ?>">
					<thead>
						<tr>
							<th width="30%">สรุปประเด็น 1</th>
							<th width="30%">สรุปประเด็น2</th>
							<th width="30%">สรุปประเด็น3</th>
						</tr>
					</thead>
					<tbody>
												<?php
												$connection = Yii::app()->db;
												//$dataReader=$connection->createCommand("SELECT dt_interview.peopleid,dt_interview.`value`,id FROM dt_interview WHERE dt_interview.qid = '$Qutions->id' order by dt_interview.`value` desc ")->query();
												$dataReader = $connection->createCommand("SELECT
dt_interpret.id,
dt_interpret.`name`,
count(*)
FROM
dt_interpret
WHERE qid = '$_GET[qid]' and dt_interpret.channel = '2'  and dt_interpret.status !='D'
GROUP BY dt_interpret.`name`
")->query();

												$dataReader->bindColumn(1, $Aid);
												$dataReader->bindColumn(2, $name);
												$dataReader->bindColumn(3, $count);

												//if($pname=='$peopleid'){echo $peopleid = $pname;} else {$peopleid = $pname; }
												while ($dataReader->read() !== false) { ?>
																<tr>
								<td>


										<?php
										$r = 0;
										$Interpret = Interpret::model()->findAll(array("condition" => "name ='$name' and channel = '2' and qid = '$_GET[qid]' and status !='D'"));
										foreach ($Interpret as $Interprets) {
											?>
																				<?php $kk = $In[$Interprets->anwer_id] ?>
																				<pre style="font-size: 14px; padding: 5px; max-height: 200px; width:400px;">
		<?= $kk ?>
		[<?= Interpret::model()->count(array("condition" => "name ='$kk' and channel = '1'  and qid = '$_GET[qid]' and status !='D'")) ?>]<br>
		<?php
		$Interpret = Interpret::model()->findAll(array("condition" => "name ='$kk' and channel = '1'  and qid = '$_GET[qid]' and status !='D'"));
		foreach ($Interpret as $Interprets) {
			echo People::model()->findBypk(Interview::model()->findBypk($Interprets->anwer_id)->peopleid)->name . "<br>";
		} ?>
				</pre>
												<br>
									<?php } ?>
									</td>

								<td><?php echo $name; ?> [<?php echo $count; ?>]</td>

									<td>
										<?php if ($_GET['export'] == '') { ?>
												<a href="#"
													onClick="javascript:window.open('index.php?r=interpret/create&Aid=<?php echo $Aid; ?>&qid=<?php echo $_GET['qid']; ?>&channel=3', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )">
													<i class="fa fa-circle-o text-yellow"></i> <span>เพิ่มสรุปประเด็น</span><br></a>
										<?php } ?>
										<?php
										$r = 0;
										$Interpret = Interpret::model()->findAll(array("condition" => "anwer_id = '$Aid' and channel = '3' and status !='D'"));
										foreach ($Interpret as $Interprets) {
											$r++;
											echo 'ครั้งที่ ' . $r . '::';
											echo $Interprets->name; ?>
												<a href="#" class="btn btn-warning btn-xs"
													onClick="javascript:window.open('index.php?r=Interpret/update&id=<?php echo $Interprets->id; ?>&channel=3', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )"><i
														class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
												<a href="index.php?r=site/page&view=report3filterup2&id=<?php echo $_GET[id]; ?>&menu=submenu&delinterpret=<?php echo $Interprets->id; ?>&qid=<?= $_GET[qid]; ?>"
													class="btn btn-danger btn-xs"><i class="fa fa-trash" aria-hidden="true"></i></a>

												<?php
												echo '<hr>';
										}
										?>
									</td>


								</tr>
						<?php } ?>
					</tbody>
				</table>

			</div>

		</div>

	</div>