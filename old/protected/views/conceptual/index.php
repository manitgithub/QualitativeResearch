<?php ini_set('memory_limit', '-1');

/* @var $this ConceptualController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Conceptuals',
);

?>

	<?php 
        if(isset($_POST["SAVE"]))
		{
							$dataReader=Yii::app()->db->createCommand("DELETE FROM `dt_conceptual` WHERE `dt_conceptual`.`main_id` = $_GET[id]")->query();
				foreach($_POST['i'] as $i) 
				{
					$name = $_POST['name'][$i];
					$c1 = $_POST['c1'][$i];
					$c2 = $_POST['c2'][$i];
					$c3 = $_POST['c3'][$i];
					$c4 = $_POST['c4'][$i];
					$c5 = $_POST['c5'][$i];

					$date = date('Y-m-d H:s:n');
					//echo $_GET[id]."=>".$name."<br>";
					
					$dataReader=Yii::app()->db->createCommand("INSERT INTO `dt_conceptual` (`name`, `main_id`, `input`, `process`, `output`, `problem`, `success`, `datekey`) VALUES ('$name', '$_GET[id]', '$c1', '$c2', '$c3', '$c4', '$c5', '$date')")->query();
				}
        }
		
        ?>

           <div class="box box-success">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-keyboard-o" aria-hidden="true"></i> Conceptuals</h3>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">
            <form action="#" method="post">
<table width="100%" class="table" >
  <tr class="bg-info">
    <td><div align="center">&#3626;&#3619;&#3640;&#3611;&#3611;&#3619;&#3632;&#3648;&#3604;&#3655;&#3609;</div></td>
    <td><div align="center">Input</div></td>
    <td><div align="center">Process</div></td>
    <td><div align="center">Output</div></td>
    <td><div align="center">Problem</div></td>
    <td><div align="center">Success</div></td>
  </tr>
 <?php 
$i = 0;
$dataReader=Yii::app()->db->createCommand("SELECT
dt_interpret.`name`,
Count(*) AS num,
dt_interpret.`id`,dt_interpret.qid
FROM
dt_interpret
INNER JOIN dt_qution ON dt_qution.id = dt_interpret.qid
WHERE
dt_interpret.channel = '1' AND
dt_qution.main_id = '$_GET[id]'
GROUP BY dt_interpret.`name` LIMIT 198
")->query();
        $dataReader->bindColumn(1,$name);
        $dataReader->bindColumn(2,$num);
		$dataReader->bindColumn(3,$Iid);
		$dataReader->bindColumn(4,$qid);

        while($dataReader->read()!== false) {
$i++;
?>
  <tr>
    <td><?=$name;?>[<?=$num?>] <input type="hidden"  name="name[<?=$i?>]" value="<?=strtolower(preg_replace('~[^a-z0-9ก-๙\.\-\_]~iu','',$name));?>"/> <a href="#" class="btn btn-warning btn-xs" onClick="javascript:window.open('index.php?r=Interpret/update&id=<?=$Iid?>&channel=1&qid=<?=$qid?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=800,height=500,top=50 ' )"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></td>
    <td><div align="center">
      <input type="checkbox" class="flat-red" name="c1[<?=$i?>]" value="1">
    </div></td>
    <td><div align="center">
      <input type="checkbox" class="flat-red" name="c2[<?=$i?>]" value="1">
    </div></td>
    <td><div align="center">
      <input type="checkbox" class="flat-red" name="c3[<?=$i?>]" value="1">
    </div></td>
    <td><div align="center">
      <input type="checkbox" class="flat-red" name="c4[<?=$i?>]" value="1">
    </div></td>
    <td><div align="center">
      <input type="checkbox" class="flat-red" name="c5[<?=$i?>]" value="1">
    </div></td>
  </tr>
        <input type="hidden" name="i[]" value="<?=$i?>">
  <?php } ?>
</table>
  <input type="hidden" name="id" value="<?=$_GET['id']?>">
  <input type="submit" value="SAVE" name="SAVE"/> 
</form>
</div></div>