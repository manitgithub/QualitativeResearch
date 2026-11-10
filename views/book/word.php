<?php

/** ไฟล์ .doc (HTML ที่ Microsoft Word เปิดได้) @var app\models\Project $project @var string $body */

use yii\helpers\Html;
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title><?= Html::encode($project->title) ?></title>
    <!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->
    <?= $this->render('_book_css') ?>
</head>
<body class="book">
<?= $body ?>
</body>
</html>
