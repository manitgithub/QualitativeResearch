<?php

namespace app\components\ai;

/**
 * ข้อผิดพลาดที่แสดงให้ผู้ใช้เห็นได้ (เช่น เครดิตไม่พอ, AI ตอบผิดรูปแบบ, เรียกบริการไม่สำเร็จ)
 */
class AiException extends \yii\base\UserException
{
}
