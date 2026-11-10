<?php

namespace app\components\ai;

use app\models\AiJob;
use app\models\Concept;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use Yii;

/**
 * AI ช่วยสร้าง Conceptual map ของทั้งโครงการ
 *
 * ข้อมูลที่ใช้: ประเด็นสังเคราะห์ (IPO) ของทุกคำถามการวิจัย — ถ้าคำถามไหนยังไม่สังเคราะห์ ใช้ประเด็นรอบ 1–3 แทน
 * ผล: แนวคิดพร้อมช่อง Input / Process / Output / ปัญหา / ปัจจัยความสำเร็จ เก็บเป็น "รอรับ" (ai_pending = 1)
 * ทุกแนวคิดต้องอ้างอิงประเด็นที่ให้ไป (ตรวจด้วยโค้ด) — แนวคิดที่ไม่มีที่มาถูกทิ้ง
 */
class ConceptBuilder extends AiTask
{
    public const MAX_ITEMS = 300;
    public const MAX_CONCEPTS = 15;
    /** ประเด็นรอบ 1–3 สูงสุดต่อคำถาม เมื่อคำถามนั้นยังไม่ได้สังเคราะห์ */
    public const RAW_PER_QUESTION = 40;

    public static function create(): static
    {
        return new static(self::defaultClient(), Yii::$app->params['ai.model']);
    }

    /**
     * @return array<int, array{text: string, ipo: ?string, question: string}> (เลขลำดับเริ่ม 0)
     */
    public function items(Project $project): array
    {
        $questions = Question::find()
            ->where(['project_id' => $project->id, 'category' => Question::CATEGORY_RESEARCH, 'flagDel' => 0])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $synth = [];
        foreach (Interpretation::find()->where([
            'question_id' => array_map(fn($q) => $q->id, $questions), 'round' => 4, 'response_id' => null, 'flagDel' => 0,
        ])->orderBy('id')->all() as $it) {
            $synth[$it->question_id][] = $it;
        }
        $suggester = new InterpretationSuggester($this->client, $this->model);
        $items = [];
        foreach ($questions as $q) {
            if (isset($synth[$q->id])) {
                foreach ($synth[$q->id] as $it) {
                    $items[] = ['text' => $this->clean($it->text), 'ipo' => $it->ipo, 'question' => $this->clean($q->title)];
                }
            } else {
                foreach (array_slice($suggester->synthesisItems($q), 0, self::RAW_PER_QUESTION) as $it) {
                    $items[] = ['text' => $it['text'], 'ipo' => null, 'question' => $this->clean($q->title)];
                }
            }
        }
        return array_slice($items, 0, self::MAX_ITEMS);
    }

    /**
     * @return array{job: AiJob, created: int}
     * @throws AiException
     */
    public function run(Project $project, int $userId): array
    {
        if (Concept::find()->where(['project_id' => $project->id, 'ai_pending' => 1])->exists()) {
            throw new AiException('มีแนวคิดที่ AI เสนอค้างอยู่ กดรับหรือปฏิเสธก่อนแล้วค่อยสั่งใหม่');
        }
        $items = $this->items($project);
        if (count($items) < 2) {
            throw new AiException('ยังมีประเด็นไม่พอ — สรุปประเด็นหรือสังเคราะห์ IPO ก่อน แล้วค่อยให้ AI ช่วยทำ Conceptual map');
        }
        $existing = Concept::find()->select('name')->where(['project_id' => $project->id, 'ai_pending' => 0])->column();
        $messages = $this->buildMessages($project, $items, $existing);
        $existingKeys = array_flip(array_map(fn($n) => $this->key($n), $existing));

        [$job, $created] = $this->execute($project, null, AiJob::FEATURE_CONCEPT_MAP, null, $userId, count($items), $messages,
            6000, 1500, function (string $content, AiJob $job) use ($project, $items, $existingKeys): int {
                $created = 0;
                foreach ($this->parse($content, count($items)) as $c) {
                    if (isset($existingKeys[$this->key($c['name'])])) {
                        continue; // มีแนวคิดนี้อยู่แล้ว
                    }
                    $existingKeys[$this->key($c['name'])] = true;
                    $concept = new Concept([
                        'project_id' => $project->id,
                        'name' => $c['name'],
                        'source' => Interpretation::SOURCE_AI,
                        'ai_pending' => 1,
                        'members' => json_encode(array_map(fn($i) => $items[$i - 1]['text'], $c['members']), JSON_UNESCAPED_UNICODE),
                        'ai_job_id' => $job->id,
                    ] + $c['flags']);
                    $concept->save(false);
                    $created++;
                }
                if (!$created) {
                    throw new AiException('AI ไม่ได้เสนอแนวคิดใหม่ (หรือซ้ำกับที่มีอยู่ทั้งหมด) ไม่ได้หักเครดิต');
                }
                return $created;
            });
        return ['job' => $job, 'created' => $created];
    }

    public function buildMessages(Project $project, array $items, array $existing): array
    {
        $system = <<<TXT
คุณเป็นผู้ช่วยนักวิจัยเชิงคุณภาพ ภาษาไทย ทำหน้าที่จัดทำ Conceptual map (กรอบแนวคิดจากผลการวิจัย) ของทั้งโครงการ
งาน: รวมประเด็นที่ให้มาจากทุกคำถามการวิจัยเป็น "แนวคิด/องค์ประกอบ" ระดับโครงการ ให้มีจำนวนน้อยที่สุดที่ยังครอบคลุม (ไม่เกิน 12 แนวคิด)
แต่ละแนวคิดระบุช่องที่เกี่ยวข้อง (เลือกได้หลายช่องเฉพาะเมื่อประเด็นสมาชิกรองรับ):
- input = ปัจจัยนำเข้า/สภาพเดิม/ทรัพยากร
- process = วิธีการ/กิจกรรม/การจัดการ
- output = ผลลัพธ์/ผลที่เกิดขึ้น
- problem = ปัญหา/อุปสรรค (เฉพาะเมื่อประเด็นสมาชิกบอกว่าเป็นปัญหาหรืออุปสรรค)
- success = ปัจจัยความสำเร็จ (เฉพาะเมื่อประเด็นสมาชิกบอกว่าช่วยให้ดีขึ้นหรือสำเร็จ)
หลักการ:
- ชื่อแนวคิดเป็นวลีสั้น (ไม่เกินราว 10 คำ) ที่สรุปประเด็นสมาชิกได้ตรง ไม่กว้างหรือแคบเกินสมาชิก
- ทุกแนวคิดต้องมีสมาชิกอย่างน้อย 1 ข้อ อ้างอิงด้วยเลขในวงเล็บเหลี่ยม ห้ามสร้างแนวคิดที่ไม่มีสมาชิก
- ถ้ามีแนวคิดที่นักวิจัยมีอยู่แล้วความหมายตรงกัน ไม่ต้องเสนอซ้ำ
- ตอบเป็น JSON อย่างเดียว ตามรูปแบบ:
{"concepts":[{"name":"...","input":true,"process":false,"output":false,"problem":true,"success":false,"members":[1,4]}]}
TXT;
        $system .= "\n" . self::GROUNDING_RULES;

        $ipoLabel = ['input' => 'Input', 'process' => 'Process', 'output' => 'Output'];
        $lines = ['โครงการวิจัย: ' . $project->title];
        if ($existing) {
            $lines[] = "\nแนวคิดที่นักวิจัยมีอยู่แล้ว (ห้ามเสนอซ้ำ):\n- " . implode("\n- ", array_map(fn($n) => $this->clean($n), $existing));
        }
        $lines[] = "\nประเด็นจากการวิเคราะห์:";
        $lastQ = null;
        foreach ($items as $i => $it) {
            if ($it['question'] !== $lastQ) {
                $lines[] = 'คำถาม: ' . $it['question'];
                $lastQ = $it['question'];
            }
            $lines[] = '[' . ($i + 1) . '] ' . $it['text'] . ($it['ipo'] ? ' [' . ($ipoLabel[$it['ipo']] ?? $it['ipo']) . ']' : '');
        }
        $lines[] = "\nตอบ JSON";
        return [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => implode("\n", $lines)]];
    }

    /**
     * @return array<int, array{name: string, flags: array<string, int>, members: int[]}>
     * @throws AiException
     */
    public function parse(string $content, int $count): array
    {
        $data = $this->decodeJson($content);
        if (!isset($data['concepts']) || !is_array($data['concepts'])) {
            throw new AiException('AI ตอบกลับไม่อยู่ในรูปแบบที่อ่านได้ ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
        }
        $out = [];
        foreach (array_slice($data['concepts'], 0, self::MAX_CONCEPTS) as $c) {
            $name = is_array($c) ? trim((string)($c['name'] ?? '')) : '';
            if ($name === '') {
                continue;
            }
            $members = [];
            foreach ((array)($c['members'] ?? []) as $m) {
                $m = (int)$m;
                if ($m >= 1 && $m <= $count && !in_array($m, $members, true)) {
                    $members[] = $m;
                }
            }
            if (!$members) {
                continue; // ไม่มีที่มาจากข้อมูล — ทิ้ง
            }
            $flags = [];
            foreach (['input' => 'is_input', 'process' => 'is_process', 'output' => 'is_output', 'problem' => 'is_problem', 'success' => 'is_success'] as $k => $col) {
                $flags[$col] = !empty($c[$k]) && $c[$k] !== 'false' ? 1 : 0;
            }
            $out[] = ['name' => mb_substr($name, 0, 500), 'flags' => $flags, 'members' => $members];
        }
        return $out;
    }

    private function key(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', $name));
    }
}
