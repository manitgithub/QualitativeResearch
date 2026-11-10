<?php

namespace app\components\ai;

final class ChatResult
{
    /**
     * @param float|null $costUsd ค่าใช้จ่ายจริงที่ผู้ให้บริการแจ้ง (null = ไม่แจ้ง ให้คำนวณจาก token)
     */
    public function __construct(
        public readonly string $content,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly ?float $costUsd = null,
    ) {
    }
}
