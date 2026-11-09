<?php 
ini_set("memory_limit","2024M");
//คำสั่ง connect db เขียนเพิ่มเองนะ

$strExcelFileName="exportword.doc";
header("Content-Type: application/x-msexcel; name=\"$strExcelFileName\"");
header("Content-Disposition: inline; filename=\"$strExcelFileName\"");
header("Pragma:no-cache");?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"xmlns:x="urn:schemas-microsoft-com:office:Word"xmlns="http://www.w3.org/TR/REC-html40">

  <style type="text/css">
body {
    font-family: "THSarabun";
	line-height: 1.6;
	font-size:25px;

}

</style>
<body>
	<?php echo $content; ?>
</body> 
