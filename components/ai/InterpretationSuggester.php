<?php

namespace app\components\ai;

use app\models\AiJob;
use app\models\AiSuggestion;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\Response;
use Yii;

/**
 * AI เสนอประเด็นการตีความรายคำตอบ (รอบ 1–3) แล้วเก็บเป็น AiSuggestion ให้นักวิจัยรับ/ปฏิเสธ
 *
 * ข้อมูลที่ส่งออก: ชื่อโครงการ, คำถาม, ข้อความคำตอบ และประเด็นรอบก่อนหน้า
 * ไม่ส่งรหัส/อายุ/หมายเหตุของผู้ให้ข้อมูล — คำตอบแต่ละข้อใช้เลขลำดับชั่วคราวแทน
 */
class InterpretationSuggester extends AiTask
{
    public const MAX_ROUND = 3;
    public const SYNTH_ROUND = 4;
    /** จำนวนประเด็นย่อยสูงสุดที่ส่งให้ AI สังเคราะห์ในครั้งเดียว */
    public const SYNTH_MAX_ITEMS = 300;

    public function __construct(ChatClientInterface $client, string $model, private readonly int $batchSize = 10)
    {
        parent::__construct($client, $model);
    }

    public static function create(): static
    {
        return new static(self::defaultClient(), Yii::$app->params['ai.model'], (int)(Yii::$app->params['ai.batchSize'] ?? 10));
    }

    /**
     * คำตอบที่ยังไม่มีประเด็นในรอบนี้และยังไม่มีข้อเสนอค้างอยู่
     * @return Response[]
     */
    public function candidates(Question $question, int $round, ?int $limit = null): array
    {
        $q = Response::find()->alias('r')
            ->innerJoinWith(['informant inf'])
            ->with(['interpretations'])
            ->where(['r.question_id' => $question->id, 'r.flagDel' => 0, 'inf.flagDel' => 0])
            ->andWhere(['not', ['r.value_text' => null]])
            ->andWhere(['<>', 'r.value_text', ''])
            ->andWhere(['not exists', Interpretation::find()
                ->where('{{%interpretation}}.response_id = r.id')
                ->andWhere(['round' => $round, 'flagDel' => 0])])
            ->andWhere(['not exists', AiSuggestion::find()
                ->where('{{%ai_suggestion}}.response_id = r.id')
                ->andWhere(['round' => $round, 'status' => AiSuggestion::STATUS_PENDING])])
            ->orderBy(['inf.code' => SORT_ASC, 'r.id' => SORT_ASC]);
        if ($round > 1) {
            // ยกประเด็นรอบถัดไปได้เมื่อมีประเด็นของรอบก่อนหน้าแล้ว
            $q->andWhere(['exists', Interpretation::find()
                ->where('{{%interpretation}}.response_id = r.id')
                ->andWhere(['round' => $round - 1, 'flagDel' => 0])]);
        }
        if ($limit) {
            $q->limit($limit);
        }
        return $q->all();
    }

    /**
     * @return array{job: AiJob, created: int, remaining: int}
     * @throws AiException
     */
    public function run(Project $project, Question $question, int $round, int $userId): array
    {
        if ($round < 1 || $round > self::MAX_ROUND) {
            throw new AiException('AI ช่วยได้เฉพาะรอบ 1–3');
        }
        if ($question->answer_type !== Question::TYPE_TEXT) {
            throw new AiException('AI ช่วยได้เฉพาะคำถามแบบข้อความ');
        }
        $responses = $this->candidates($question, $round, $this->batchSize);
        if (!$responses) {
            throw new AiException($round === 1
                ? 'ไม่มีคำตอบที่ต้องสรุปประเด็นแล้ว'
                : 'ไม่มีคำตอบที่ต้องยกประเด็นรอบนี้ (ต้องมีประเด็นรอบก่อนหน้าก่อน)');
        }

        $messages = $this->buildMessages($project, $question, $round, $responses);
        $n = count($responses);
        [$job, $created] = $this->execute($project, $question, AiJob::FEATURE_SUGGEST_INTERPRETATION, $round, $userId, $n, $messages,
            min(32000, 600 * $n + 4000), 300 * $n + 1500,
            function (string $content, AiJob $job) use ($question, $round, $responses, $n): int {
                $created = 0;
                foreach ($this->parse($content, $n) as $idx => $suggestions) {
                    foreach ($suggestions as $s) {
                        $this->saveSuggestion($job, $question, $responses[$idx - 1]->id, $round, $s['text'], $s['ipo']);
                        $created++;
                    }
                }
                return $created;
            });

        return ['job' => $job, 'created' => $created, 'remaining' => count($this->candidates($question, $round))];
    }

    /**
     * AI สังเคราะห์ (IPO) ระดับคำถาม: รวมประเด็นจากรอบ 1–3 ให้เหลือกลุ่มน้อยที่สุด
     * ผลเป็นข้อเสนอระดับคำถาม (response_id = NULL, round = 4) พร้อมรายการประเด็นที่ถูกรวม
     *
     * @return array{job: AiJob, created: int, remaining: int}
     * @throws AiException
     */
    public function runSynthesis(Project $project, Question $question, int $userId): array
    {
        if (AiSuggestion::find()->where(['question_id' => $question->id, 'round' => self::SYNTH_ROUND,
            'status' => AiSuggestion::STATUS_PENDING])->exists()) {
            throw new AiException('มีข้อเสนอสังเคราะห์ของ AI ค้างอยู่ กดรับหรือปฏิเสธก่อนแล้วค่อยสั่งใหม่');
        }
        $items = $this->synthesisItems($question);
        if (count($items) < 2) {
            throw new AiException('ต้องมีประเด็นจากรอบ 1–3 อย่างน้อย 2 ประเด็นก่อนสังเคราะห์');
        }
        $messages = $this->buildSynthesisMessages($project, $question, $items);

        [$job, $created] = $this->execute($project, $question, AiJob::FEATURE_SYNTHESIZE_IPO, self::SYNTH_ROUND, $userId, count($items), $messages,
            8000, 1500,
            function (string $content, AiJob $job) use ($question, $items): int {
                $created = 0;
                foreach ($this->parseSynthesis($content, count($items)) as $theme) {
                    $members = array_map(fn($i) => $items[$i - 1]['text'], $theme['members']);
                    $this->saveSuggestion($job, $question, null, self::SYNTH_ROUND, $theme['text'], $theme['ipo'], $members);
                    $created++;
                }
                if (!$created) {
                    throw new AiException('AI ไม่ได้เสนอกลุ่มประเด็น ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
                }
                return $created;
            });

        return ['job' => $job, 'created' => $created, 'remaining' => 0];
    }

    /**
     * ประเด็นไม่ซ้ำจากรอบ 1–3 ของคำถามนี้ เรียงตามจำนวนผู้ให้ข้อมูลที่พูดถึง (มากก่อน)
     * @return array<int, array{text: string, count: int, rounds: int[]}> (เลขลำดับเริ่ม 0)
     */
    public function synthesisItems(Question $question): array
    {
        $rows = Interpretation::find()->select(['text', 'round', 'response_id'])
            ->where(['question_id' => $question->id, 'round' => [1, 2, 3], 'flagDel' => 0])
            ->asArray()->all();
        $byText = [];
        foreach ($rows as $r) {
            $t = $this->clean($r['text']);
            if ($t === '') {
                continue;
            }
            $byText[$t]['responses'][(int)$r['response_id']] = true;
            $byText[$t]['rounds'][(int)$r['round']] = true;
        }
        $items = [];
        foreach ($byText as $t => $v) {
            $rounds = array_keys($v['rounds']);
            sort($rounds);
            $items[] = ['text' => $t, 'count' => count($v['responses']), 'rounds' => $rounds];
        }
        // ประเด็นที่พบบ่อยและรอบสูงกว่า (นามธรรมกว่า) มาก่อน แล้วตัดเหลือไม่เกิน SYNTH_MAX_ITEMS
        usort($items, fn($a, $b) => [$b['count'], max($b['rounds'])] <=> [$a['count'], max($a['rounds'])] ?: strcmp($a['text'], $b['text']));
        return array_slice($items, 0, self::SYNTH_MAX_ITEMS);
    }

    public function buildSynthesisMessages(Project $project, Question $question, array $items): array
    {
        $existing = Interpretation::find()->select('text')
            ->where(['question_id' => $question->id, 'round' => self::SYNTH_ROUND, 'response_id' => null, 'flagDel' => 0])
            ->orderBy('id')->column();

        $system = <<<TXT
คุณเป็นผู้ช่วยนักวิจัยเชิงคุณภาพ (qualitative research) ภาษาไทย ทำหน้าที่สังเคราะห์ประเด็นตามกรอบ Input-Process-Output (IPO)
งาน: รวมประเด็นย่อยทั้งหมดให้เหลือ "กลุ่มประเด็น" น้อยที่สุดเท่าที่ยังสื่อความหมายครบถ้วน
หลักการ:
- รวมประเด็นที่ความหมายใกล้กันหรืออยู่ใต้แนวคิดเดียวกันเข้าด้วยกันให้มากที่สุด โดยทั่วไปไม่เกิน 3 กลุ่มต่อหมวด และรวมทั้งหมดไม่เกิน 8 กลุ่ม
- ชื่อกลุ่มเป็นวลีสั้นเชิงแนวคิด (ไม่เกินราว 12 คำ) ที่ครอบคลุมสมาชิกทุกข้อ ไม่ยกคำพูดของสมาชิกข้อใดข้อหนึ่งมาเป็นชื่อกลุ่ม
- ทุกกลุ่มต้องระบุ ipo: "input" = ปัจจัยนำเข้า/สภาพเดิม/ทรัพยากร/ปัญหาตั้งต้น, "process" = วิธีการ/กิจกรรม/การจัดการ/การปรับตัว, "output" = ผลลัพธ์/ผลที่เกิดขึ้น
- ทุกกลุ่มต้องมีสมาชิกอย่างน้อย 1 ข้อ ห้ามสร้างกลุ่มว่าง ห้ามสร้างกลุ่มเพียงเพื่อให้ครบทุกหมวด (หมวดที่ไม่มีประเด็นไม่ต้องมีกลุ่ม)
- จัดหมวดตามความหมายของสมาชิก: สิ่งที่เป็นปัญหา/ข้อจำกัด/ความต้องการ = input, สิ่งที่ผู้ให้ข้อมูลทำเพื่อรับมือ = process, ผลที่ตามมา = output
- ประเด็นย่อยแต่ละข้อควรอยู่ในกลุ่มเดียว ใช้เลขในวงเล็บเหลี่ยมอ้างอิงสมาชิก ห้ามแต่งประเด็นที่ไม่มีในรายการ
- ตอบเป็น JSON อย่างเดียว ไม่มีข้อความอื่น ตามรูปแบบ:
{"themes":[{"text":"...","ipo":"input","members":[1,4,7]}]}
TXT;
        $system .= "\n" . self::GROUNDING_RULES;
        $lines = [];
        $lines[] = 'โครงการวิจัย: ' . $project->title;
        $lines[] = 'คำถามการวิจัย: ' . $question->title . ($question->detail ? "\nรายละเอียดคำถาม: " . $question->detail : '');
        if ($existing) {
            $lines[] = "\nประเด็นสังเคราะห์ที่นักวิจัยมีแล้ว (ถ้าความหมายตรงกันให้ใช้ถ้อยคำเดิม):\n- " . implode("\n- ", array_map(fn($t) => $this->clean($t), $existing));
        }
        $lines[] = "\nประเด็นย่อยจากรอบ 1–3 (ตัวเลขหลังประเด็น = จำนวนผู้ให้ข้อมูลที่พูดถึง):";
        foreach ($items as $i => $it) {
            $lines[] = '[' . ($i + 1) . '] ' . $it['text'] . ' (' . $it['count'] . ')';
        }
        $lines[] = "\nตอบ JSON กลุ่มประเด็นที่น้อยที่สุด";
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => implode("\n", $lines)],
        ];
    }

    /**
     * @return array<int, array{text: string, ipo: string|null, members: int[]}>
     * @throws AiException
     */
    public function parseSynthesis(string $content, int $count): array
    {
        $data = $this->decodeJson($content);
        if (!isset($data['themes']) || !is_array($data['themes'])) {
            throw new AiException('AI ตอบกลับไม่อยู่ในรูปแบบที่อ่านได้ ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
        }
        $ipos = array_keys(Interpretation::ipoOptions());
        $out = [];
        $used = [];
        foreach (array_slice($data['themes'], 0, 12) as $t) {
            $text = is_array($t) ? trim((string)($t['text'] ?? '')) : '';
            if ($text === '') {
                continue;
            }
            $members = [];
            foreach ((array)($t['members'] ?? []) as $m) {
                $m = (int)$m;
                if ($m >= 1 && $m <= $count && !isset($used[$m])) {
                    $members[] = $m;
                    $used[$m] = true; // ประเด็นย่อยอยู่ได้กลุ่มเดียว
                }
            }
            if (!$members) {
                continue; // กลุ่มที่ไม่มีสมาชิก (AI แต่งขึ้นเอง) — ทิ้ง
            }
            $out[] = [
                'text' => mb_substr($text, 0, 500),
                'ipo' => in_array($t['ipo'] ?? null, $ipos, true) ? $t['ipo'] : null,
                'members' => $members,
            ];
        }
        return $out;
    }

    private function saveSuggestion(AiJob $job, Question $question, ?int $responseId, int $round, string $text, ?string $ipo, ?array $members = null): void
    {
        (new AiSuggestion([
            'ai_job_id' => $job->id,
            'question_id' => $question->id,
            'response_id' => $responseId,
            'round' => $round,
            'text' => $text,
            'ipo' => $ipo,
            'members' => $members !== null ? json_encode(array_values($members), JSON_UNESCAPED_UNICODE) : null,
            'status' => AiSuggestion::STATUS_PENDING,
            'created_at' => time(),
        ]))->save(false);
    }

    /**
     * @param Response[] $responses
     */
    public function buildMessages(Project $project, Question $question, int $round, array $responses): array
    {
        $task = match ($round) {
            1 => 'สรุปประเด็นสำคัญจากคำตอบดิบของผู้ให้ข้อมูลแต่ละคน เป็นวลีสั้น กระชับ ยึดตามสิ่งที่ผู้ให้ข้อมูลพูดจริง',
            2 => 'ยกประเด็นขึ้นหนึ่งระดับ: จัดประเด็นรอบก่อนหน้าของแต่ละคำตอบให้เป็นหมวดหมู่ที่กว้างขึ้นและนามธรรมขึ้น เพื่อใช้จัดกลุ่มข้ามผู้ให้ข้อมูล',
            default => 'ยกประเด็นขึ้นอีกระดับ: สังเคราะห์ประเด็นรอบก่อนหน้าให้เป็นแก่นเรื่อง (theme) ระดับสูงที่ใช้เปรียบเทียบข้ามผู้ให้ข้อมูลได้',
        };

        // ประเด็นที่นักวิจัยใช้แล้วในคำถามนี้รอบเดียวกัน — ให้ AI ใช้ถ้อยคำเดิมเพื่อให้รายงานจัดกลุ่มได้
        $existing = Interpretation::find()->select('text')->distinct()
            ->where(['question_id' => $question->id, 'round' => $round, 'flagDel' => 0])
            ->andWhere(['not', ['response_id' => null]])
            ->orderBy('text')->limit(80)->column();

        $system = <<<TXT
คุณเป็นผู้ช่วยนักวิจัยเชิงคุณภาพ (qualitative research) ภาษาไทย ทำหน้าที่ช่วยตีความ/ลงรหัสข้อมูลสัมภาษณ์
หลักการ:
- ยึดข้อมูลจริงเท่านั้น ห้ามเติมสิ่งที่ผู้ให้ข้อมูลไม่ได้พูด ถ้าคำตอบไม่มีสาระให้คืนรายการว่าง
- แต่ละคำตอบเสนอ 1–3 ประเด็น แต่ละประเด็นยาวไม่เกิน 1 ประโยคสั้น (ไม่เกินราว 15 คำ) ไม่ใส่เลขข้อหรือเครื่องหมายนำหน้า
- ถ้ามีประเด็นเดิมที่ความหมายตรงกัน ให้ใช้ถ้อยคำเดิมทุกตัวอักษร เพื่อให้จัดกลุ่มได้
- ipo: "input" = ปัจจัยนำเข้า/สภาพเดิม/ทรัพยากร/เงื่อนไขตั้งต้น, "process" = วิธีการ/กิจกรรม/กระบวนการ, "output" = ผลลัพธ์/ผลที่เกิดขึ้น, null = ไม่ชัดเจน
- ตอบเป็น JSON อย่างเดียว ไม่มีข้อความอื่น ตามรูปแบบ:
{"items":[{"id":1,"suggestions":[{"text":"...","ipo":"process"}]}]}
TXT;
        $system .= "\n" . self::GROUNDING_RULES;

        $lines = [];
        $lines[] = 'โครงการวิจัย: ' . $project->title;
        $lines[] = 'คำถามการวิจัย: ' . $question->title . ($question->detail ? "\nรายละเอียดคำถาม: " . $question->detail : '');
        $lines[] = 'งาน: ' . $task;
        if ($existing) {
            $lines[] = "\nประเด็นเดิมที่ใช้แล้วในรอบนี้ (ใช้ซ้ำได้):\n- " . implode("\n- ", $existing);
        }
        $lines[] = "\nคำตอบ:";
        foreach (array_values($responses) as $i => $r) {
            $lines[] = '[' . ($i + 1) . '] คำตอบ: ' . $this->clean($r->value_text);
            if ($round > 1) {
                $prev = array_filter($r->interpretations, fn($it) => (int)$it->round === $round - 1 && !$it->flagDel);
                $lines[] = '    ประเด็นรอบก่อน: ' . implode(' | ', array_map(fn($it) => $this->clean($it->text), $prev));
            }
        }
        $lines[] = "\nตอบ JSON ให้ครบทุก id (1–" . count($responses) . ')';

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => implode("\n", $lines)],
        ];
    }

    /**
     * @return array<int, array<int, array{text: string, ipo: string|null}>> id (เริ่ม 1) => ประเด็น
     * @throws AiException
     */
    public function parse(string $content, int $count): array
    {
        $data = $this->decodeJson($content);
        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new AiException('AI ตอบกลับไม่อยู่ในรูปแบบที่อ่านได้ ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
        }
        $ipos = array_keys(Interpretation::ipoOptions());
        $out = [];
        foreach ($data['items'] as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id < 1 || $id > $count || !is_array($item['suggestions'] ?? null)) {
                continue; // id ที่ไม่ได้ขอ — ทิ้ง
            }
            foreach (array_slice($item['suggestions'], 0, 3) as $s) {
                $text = is_array($s) ? trim((string)($s['text'] ?? '')) : '';
                if ($text === '') {
                    continue;
                }
                $ipo = is_array($s) && in_array($s['ipo'] ?? null, $ipos, true) ? $s['ipo'] : null;
                $out[$id][] = ['text' => mb_substr($text, 0, 500), 'ipo' => $ipo];
            }
        }
        return $out;
    }
}
