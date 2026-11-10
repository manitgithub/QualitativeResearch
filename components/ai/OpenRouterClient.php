<?php

namespace app\components\ai;

use Yii;
use yii\httpclient\Client;

/**
 * เรียกโมเดลผ่าน OpenRouter (https://openrouter.ai) — API key อยู่ใน config/params-local.php ('openrouter.apiKey')
 */
class OpenRouterClient implements ChatClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $reasoningEffort = 'low',
        private readonly int $timeout = 150,
    ) {
    }

    public function chat(string $model, array $messages, int $maxTokens): ChatResult
    {
        $body = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            // ให้ OpenRouter แจ้งค่าใช้จ่ายจริง (USD) กลับมาใน usage.cost
            'usage' => ['include' => true],
        ];
        if ($this->reasoningEffort) {
            $body['reasoning'] = ['effort' => $this->reasoningEffort];
        }

        $client = new Client(['baseUrl' => 'https://openrouter.ai/api/v1', 'transport' => \yii\httpclient\CurlTransport::class]);
        try {
            $res = $client->post('chat/completions', $body, [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-Title' => Yii::$app->name,
            ])->setFormat(Client::FORMAT_JSON)
                ->setOptions(['timeout' => $this->timeout, 'connectTimeout' => 15])
                ->send();
        } catch (\yii\httpclient\Exception $e) {
            Yii::error('OpenRouter: ' . $e->getMessage(), __METHOD__);
            throw new AiException('เชื่อมต่อบริการ AI ไม่สำเร็จ ลองใหม่อีกครั้ง');
        }

        $data = $res->data;
        if (!$res->isOk || !is_array($data) || empty($data['choices'][0])) {
            $msg = is_array($data) ? ($data['error']['message'] ?? '') : '';
            Yii::error("OpenRouter HTTP {$res->statusCode}: " . mb_substr($res->content, 0, 1000), __METHOD__);
            throw new AiException('บริการ AI ตอบกลับผิดพลาด' . ($msg ? ": $msg" : " (HTTP {$res->statusCode})"));
        }

        $choice = $data['choices'][0];
        if (($choice['finish_reason'] ?? '') === 'length') {
            throw new AiException('คำตอบจาก AI ยาวเกินกำหนด ลองลดจำนวนข้อต่อครั้ง');
        }
        $usage = $data['usage'] ?? [];
        return new ChatResult(
            (string)($choice['message']['content'] ?? ''),
            (int)($usage['prompt_tokens'] ?? 0),
            (int)($usage['completion_tokens'] ?? 0),
            isset($usage['cost']) ? (float)$usage['cost'] : null,
        );
    }
}
