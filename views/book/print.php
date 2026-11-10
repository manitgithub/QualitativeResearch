<?php

/** หน้าพร้อมพิมพ์ / บันทึกเป็น PDF @var app\models\Project $project @var string $body */

use yii\helpers\Html;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title><?= Html::encode($project->title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <?= $this->render('_book_css') ?>
    <style>
        body.book { max-width: 21cm; margin: 0 auto; padding: 1.5cm; }
        .book-cover { page-break-after: always; }
        .book-chapter { page-break-before: always; }
        .print-bar { position: sticky; top: 0; background: #f8fafc; border-bottom: 1px solid #ddd; padding: .5em; text-align: center; font-family: sans-serif; font-size: 11pt; }
        @media print { .print-bar { display: none; } body.book { padding: 0; } }
    </style>
</head>
<body class="book">
<div class="print-bar"><button onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button></div>
<?= $body ?>
</body>
</html>
