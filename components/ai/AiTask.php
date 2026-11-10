<?php

namespace app\components\ai;

use app\models\AiJob;
use app\models\Project;
use app\models\Question;
use Yii;

/**
 * พื้นฐานของงาน AI ทุกชนิด: ตัวเรียกโมเดล + การกันเครดิต/หักตามจริง/คืนเมื่อล้มเหลว
 */
abstract class AiTask
{
    /**
     * กฎบังคับทุก prompt: ห้ามตีความเกินข้อมูลเด็ดขาด
     */
    public const GROUNDING_RULES = <<<TXT
กฎเด็ดขาด — ห้ามตีความเกินข้อมูล:
- ใช้เฉพาะข้อมูลที่ให้มาเท่านั้น ทุกข้อความต้องชี้กลับไปที่ข้อมูลที่ให้มาได้โดยตรง
- ห้ามสันนิษฐานสาเหตุ ผลกระทบ ความรู้สึก แรงจูงใจ ความตั้งใจ หรือความหมายแฝง ที่ผู้ให้ข้อมูลไม่ได้พูดไว้ตรง ๆ
- ห้ามสรุปเกินจำนวนที่มี: ห้ามใช้คำบอกปริมาณ/ความถี่ เช่น "ส่วนใหญ่" "หลายคน" "มักจะ" "ทั่วไป" เว้นแต่มีตัวเลขในข้อมูลรองรับ และให้ระบุตัวเลขนั้น
- ห้ามใช้ความรู้ภายนอก ทฤษฎี บริบททางสังคม/เศรษฐกิจ ข้อเสนอแนะ หรืองานวิจัยอื่น
- ห้ามใช้คำขยายเกินจริง (เช่น อย่างยิ่ง อย่างมาก หนักอึ้ง อย่างรุนแรง) ถ้าผู้ให้ข้อมูลไม่ได้ใช้คำนั้น
- ถ้าไม่แน่ใจว่าข้อมูลรองรับหรือไม่ ให้ตัดทิ้ง — เขียนสั้นแต่ถูกต้องดีกว่ายาวแต่เกินข้อมูล
TXT;

    /** @var ChatResult[] การเรียก AI เพิ่มเติมในงานเดียวกัน (เช่น รอบตรวจทาน) — นับรวมค่าใช้จ่าย */
    private array $extraCalls = [];

    public function __construct(
        protected readonly ChatClientInterface $client,
        protected readonly string $model,
    ) {
    }

    public static function isConfigured(): bool
    {
        return (string)(Yii::$app->params['openrouter.apiKey'] ?? '') !== '';
    }

    protected static function defaultClient(): ChatClientInterface
    {
        $p = Yii::$app->params;
        if (!self::isConfigured()) {
            throw new AiException('ยังไม่ได้ตั้งค่า OpenRouter API key');
        }
        // null = ไม่ส่งพารามิเตอร์ reasoning (ไม่ใช้ ?? เพราะจะเปลี่ยน null เป็นค่าอื่น)
        return new OpenRouterClient($p['openrouter.apiKey'], array_key_exists('ai.reasoningEffort', $p) ? $p['ai.reasoningEffort'] : 'low');
    }

    /**
     * เรียก AI พร้อมกัน/หักเครดิต: กันยอดประมาณ → เรียก → บันทึกผล → หักตามจริง (ล้มเหลว = คืนทั้งหมด)
     *
     * @param callable(string $content, AiJob $job): int $save แปลงและบันทึกผล คืนจำนวนข้อเสนอ (ทำใน transaction)
     * @param callable(string $content): string|null $refine ขั้นตรวจ/ปรับผลก่อนบันทึก (เรียก AI เพิ่มได้ผ่าน chatMore — ทำนอก transaction)
     * @return array{0: AiJob, 1: int}
     */
    protected function execute(Project $project, ?Question $question, string $feature, ?int $round, int $userId, int $itemCount,
                               array $messages, int $maxTokens, int $expectedOut, callable $save, ?callable $refine = null): array
    {
        $this->extraCalls = [];
        $held = Pricing::estimateCredits($this->model, $messages, $expectedOut);
        $job = new AiJob([
            'user_id' => $userId,
            'project_id' => $project->id,
            'question_id' => $question?->id,
            'feature' => $feature,
            'round' => $round,
            'model' => $this->model,
            'status' => AiJob::STATUS_RUNNING,
            'item_count' => $itemCount,
            'credits_held' => $held,
            'created_at' => time(),
        ]);
        $job->save(false);

        try {
            Credits::hold($userId, $held, $job->id);
        } catch (AiException $e) {
            $job->updateAttributes(['status' => AiJob::STATUS_FAILED, 'credits_held' => 0, 'error' => $e->getMessage(), 'finished_at' => time()]);
            throw $e;
        }

        $tx = null;
        try {
            $result = $this->client->chat($this->model, $messages, $maxTokens);
            $content = $refine ? $refine($result->content) : $result->content;
            $tx = Yii::$app->db->beginTransaction();
            $created = $save($content, $job);
            $tx->commit();
        } catch (\Throwable $e) {
            if ($tx && $tx->isActive) {
                $tx->rollBack();
            }
            Credits::settle($userId, $held, 0, $job->id);
            $job->updateAttributes(['status' => AiJob::STATUS_FAILED, 'error' => mb_substr($e->getMessage(), 0, 2000), 'finished_at' => time()]);
            if ($e instanceof AiException) {
                throw $e;
            }
            Yii::error($e, __METHOD__);
            throw new AiException('AI ทำงานไม่สำเร็จ ไม่ได้หักเครดิต');
        }

        $cost = 0.0;
        $in = $out = 0;
        foreach (array_merge([$result], $this->extraCalls) as $r) {
            $cost += $r->costUsd ?? Pricing::costUsd($this->model, $r->inputTokens, $r->outputTokens);
            $in += $r->inputTokens;
            $out += $r->outputTokens;
        }
        $charged = Credits::settle($userId, $held, Pricing::creditsForUsd($cost), $job->id);
        $job->updateAttributes([
            'status' => AiJob::STATUS_DONE,
            'input_tokens' => $in,
            'output_tokens' => $out,
            'cost_usd' => round($cost, 6),
            'credits_charged' => $charged,
            'finished_at' => time(),
        ]);
        return [$job, $created];
    }

    /** เรียก AI เพิ่มในงานเดียวกัน (ค่าใช้จ่ายนับรวมกับงานหลัก) */
    protected function chatMore(array $messages, int $maxTokens): ChatResult
    {
        $r = $this->client->chat($this->model, $messages, $maxTokens);
        $this->extraCalls[] = $r;
        return $r;
    }

    protected function decodeJson(string $content): ?array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        $data = $start !== false && $end > $start ? json_decode(substr($content, $start, $end - $start + 1), true) : null;
        if (!is_array($data)) {
            Yii::warning('AI returned non-JSON: ' . mb_substr($content, 0, 500), __METHOD__);
        }
        return is_array($data) ? $data : null;
    }


    protected function clean(?string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$s));
    }
}
