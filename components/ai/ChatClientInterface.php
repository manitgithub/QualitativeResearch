<?php

namespace app\components\ai;

/**
 * ตัวเรียกโมเดลภาษา (แยกเป็น interface เพื่อให้ test ใส่ตัวปลอมได้)
 */
interface ChatClientInterface
{
    /**
     * @param array $messages [['role' => 'system'|'user', 'content' => string], ...]
     * @throws AiException เมื่อเรียกไม่สำเร็จ
     */
    public function chat(string $model, array $messages, int $maxTokens): ChatResult;
}
