<?php

namespace app\tests\Support;

use app\components\ai\ChatClientInterface;
use app\components\ai\ChatResult;

/** ตัวเรียก AI ปลอมสำหรับ test: คืนคำตอบที่กำหนด หรือโยน exception */
class FakeChatClient implements ChatClientInterface
{
    public array $calls = [];

    public function __construct(private $reply, private int $in = 1000, private int $out = 500, private ?float $cost = null)
    {
    }

    public function chat(string $model, array $messages, int $maxTokens): ChatResult
    {
        $this->calls[] = compact('model', 'messages', 'maxTokens');
        if ($this->reply instanceof \Throwable) {
            throw $this->reply;
        }
        // array = คำตอบตามลำดับการเรียก (ครั้งที่ 1, 2, ...) ครั้งเกินจากนั้นใช้ตัวสุดท้าย
        $reply = is_array($this->reply) ? ($this->reply[count($this->calls) - 1] ?? end($this->reply)) : $this->reply;
        if ($reply instanceof \Throwable) {
            throw $reply;
        }
        $content = is_callable($reply) ? $reply($messages) : $reply;
        return new ChatResult($content, $this->in, $this->out, $this->cost);
    }
}
