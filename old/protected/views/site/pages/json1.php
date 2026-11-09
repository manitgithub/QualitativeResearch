<?php
session_start();
$UserID =$_SESSION['UserID'];
$this->layout = "nothemes";
header('Access-Control-Allow-Origin:*');
header('Content-Type: application/json');

//$where = isset($_GET['id']) ? " and  AD.albID = ".$_GET['id']." ":"";

$connection=Yii::app()->db;
$dataReader=$connection->createCommand("SELECT
dt_qution.`name` AS text1,
dt_people.`name` AS text2,
dt_interview.`value`,
dt_interpret.`name`
FROM
dt_interview
INNER JOIN dt_qution ON dt_interview.qid = dt_qution.id
INNER JOIN dt_people ON dt_people.id = dt_interview.peopleid
INNER JOIN dt_main ON dt_qution.main_id = dt_main.id
LEFT JOIN dt_interpret ON dt_interpret.anwer_id = dt_interview.id
WHERE
dt_qution.type_id = 1 AND dt_main.id = '$_GET[id]'")->query();
        $dataReader->bindColumn(1,$text);
        $dataReader->bindColumn(2,$text2);
        $dataReader->bindColumn(3,$value);
        $dataReader->bindColumn(4,$name);
		
echo '{"success":true,"drivers":[';
while($dataReader->read()!== false) {
$r++;
if($r!=1){echo ',';}
echo '{"text":'.CJSON::encode($text).',"text2":"'.$text2.'","value":'.CJSON::encode($value).',"name":'.CJSON::encode($name).'}';
}
echo ']}';


?>
