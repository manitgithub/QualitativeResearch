<form enctype="multipart/form-data" method="post" role="form">
    <div class="form-group">
        <label for="exampleInputFile">File Upload</label>
        <input type="file" name="file" id="file" size="150">
        <p class="help-block">Only Excel/CSV File Import.</p>
    </div>
    <button type="submit" class="btn btn-default" name="Import" value="Import">Upload</button>
</form>


<?php 


$date = date('Y-m-d');
if(isset($_POST["Import"]))
{
    //First we need to make a connection with the database
    echo $filename=$_FILES["file"]["tmp_name"];
    if($_FILES["file"]["size"] > 0)
    {
        $file = fopen($filename, "r");
        //$sql_data = "SELECT * FROM prod_list_1 ";
$count = 0;                                         // add this line
while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE)
{
    //exit();
    $count++;                                      // add this line

    if($count>1){                                  // add this line
    //print_r($emapData);
$dataReader=Yii::app()->db->createCommand("INSERT INTO `dt_people` ( `date`, `name`, `main_id`) VALUES ( '$date', '$emapData[0]', '$_GET[mid]');")->query();
$max = Yii::app()->db->createCommand("SELECT MAX(id) FROM `dt_people`")->queryScalar();
$dataReader2=Yii::app()->db->createCommand("INSERT INTO `dt_interview` ( `peopleid`, `qid`, `value`) VALUES ( '$max', '$_GET[qid]', '$emapData[1]');")->query();

   			 }                                              // add this line
		}
     fclose($file);
	 				 echo '<script type="text/javascript">';
	    echo 'alert("บันทึกสำเร็จ!");';
		echo 'window.opener.location.reload();';	 
				echo 'window.close();';	 
  echo '</script>';
      echo 'CSV File has been successfully Imported';
    }
    else
        echo 'Invalid File:Please Upload CSV File';
}
?>
