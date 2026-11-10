<?php

/** ย่อหน้าจากข้อความธรรมดา (คั่นด้วยบรรทัดว่าง) @var string $text */

use yii\helpers\Html;

foreach (preg_split("/\n\s*\n/u", trim($text)) as $para) {
    if (trim($para) !== '') {
        echo '<p class="book-p">' . nl2br(Html::encode(trim($para))) . "</p>\n";
    }
}
