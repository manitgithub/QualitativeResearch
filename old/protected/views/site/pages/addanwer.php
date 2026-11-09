<?php $this->layout = 'noTheme';?>
<?php error_reporting( error_reporting() & ~E_NOTICE );
?>
<div style="padding: 50px 30px 50px 80px">

<?php 
if($_POST["hdnCmd"] == "Add")
{
for($i=1;$i<=$_POST["hdnLine"];$i++)
	{
	if($_POST["txtName$i"]!=''){
	$qution_id = $_GET['idq'];
	$Reader=Yii::app()->db->createCommand("INSERT INTO dt_anwer ( name, qution_id, value) VALUES ('".$_POST["txtName$i"]."', '$qution_id', '".$_POST["txtValue$i"]."');")->query();
	}
	}
	echo "<script>";
    echo "window.history.back();";
	echo "</script>";
}
?>

<form name="frmMain" method="post" action="<?php echo $_SERVER["PHP_SELF"];?>?r=site/page&view=addanwer&idq=<?php echo $_GET['idq']?>">
<input type="hidden" name="hdnCmd" value='Add' class="form-control">
<table width="600" border="1" class="table">
  <tr>
    <th> <div align="center">ตัวเลือก </div></th>
    <th > <div align="center">ค่า</div></th>
  </tr>
  <?php
$line = 5 ;
  for($i=1;$i<=$line;$i++)
  {
  ?>
  <tr>
    <td><input type="text" name="txtName<?php echo $i;?>" size="20" class="form-control"></td>
    <td><div align="center"><input type="text" name="txtValue<?php echo $i;?>" size="5" class="form-control"></div></td>

  </tr>
  <?php
  }
  ?>
  </table>
  <input type="submit" name="submit" value="submit" class='btn btn-brand waves-attach waves-light')>
  <input type="hidden" name="hdnLine" value="<?php echo $i;?>">
  </form>
