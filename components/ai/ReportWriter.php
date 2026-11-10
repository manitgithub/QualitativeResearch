<?php

namespace app\components\ai;

use app\models\AiJob;
use app\models\AiSuggestion;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\ReportSection;
use app\models\Response;
use Yii;

/**
 * AI เขียนบทวิเคราะห์ในเล่มรายงาน
 *
 * - writeSection(): บทวิเคราะห์ของคำถามการวิจัย 1 ข้อ จากประเด็นสังเคราะห์ (IPO) + คำพูดของผู้ให้ข้อมูล
 * - writeSummary(): บทสรุปภาพรวม จากบทวิเคราะห์ทุกข้อ
 *
 * ผู้ให้ข้อมูลถูกส่งเป็นเลขอ้างอิงชั่วคราว (#1, #2, ...) แล้วระบบแปลงกลับเป็นรหัสผู้ให้ข้อมูลหลัง AI ตอบ
 * เนื้อหาเดิมไม่ถูกลบ — ถูกซ่อน (flagDel) แล้วบันทึกฉบับใหม่แทน
 */
class ReportWriter extends AiTask
{
    public const MAX_QUOTES = 30;
    public const QUOTES_PER_THEME = 3;

    public static function create(): static
    {
        return new static(self::defaultClient(), Yii::$app->params['ai.model']);
    }

    /**
     * ประเด็นสำหรับเขียน: ประเด็นสังเคราะห์รอบ 4 (ถ้ามี) ไม่เช่นนั้นใช้ประเด็นที่พบบ่อยจากรอบ 1–3
     * @return array{synthesized: bool, themes: array<int, array{text: string, ipo: ?string, members: string[], count: int}>}
     */
    public function themes(Question $question): array
    {
        $synth = Interpretation::find()
            ->where(['question_id' => $question->id, 'round' => 4, 'response_id' => null, 'flagDel' => 0])
            ->orderBy(['id' => SORT_ASC])->all();
        if ($synth) {
            $members = [];
            foreach (AiSuggestion::find()->where(['interpretation_id' => array_map(fn($i) => $i->id, $synth)])->all() as $sg) {
                $members[$sg->interpretation_id] = $sg->getMemberList();
            }
            // จำนวนผู้ให้ข้อมูลจริงของแต่ละกลุ่ม = คำตอบที่มีประเด็น (รอบ 1–3) ตรงกับสมาชิกกลุ่ม
            $byText = [];
            foreach (Interpretation::find()->select(['text', 'response_id'])
                ->where(['question_id' => $question->id, 'round' => [1, 2, 3], 'flagDel' => 0])
                ->andWhere(['not', ['response_id' => null]])->asArray()->all() as $row) {
                $byText[$this->clean($row['text'])][(int)$row['response_id']] = true;
            }
            return ['synthesized' => true, 'themes' => array_map(function ($i) use ($members, $byText) {
                $resp = [];
                foreach ($members[$i->id] ?? [] as $m) {
                    $resp += $byText[$this->clean($m)] ?? [];
                }
                return [
                    'text' => $this->clean($i->text),
                    'ipo' => $i->ipo,
                    'members' => $members[$i->id] ?? [],
                    'count' => count($resp),
                ];
            }, $synth)];
        }
        $items = (new InterpretationSuggester($this->client, $this->model))->synthesisItems($question);
        return ['synthesized' => false, 'themes' => array_map(fn($it) => [
            'text' => $it['text'], 'ipo' => null, 'members' => [], 'count' => $it['count'],
        ], array_slice($items, 0, 40))];
    }

    /**
     * คำพูดของผู้ให้ข้อมูลที่ใช้อ้างอิง: เลือกคำตอบที่มีประเด็นตรงกับสมาชิกของแต่ละกลุ่มก่อน แล้วเติมด้วยคำตอบอื่น
     * @return array<int, array{ref: int, code: string, text: string, theme: ?int}>
     */
    public function quotes(Question $question, array $themes): array
    {
        $responses = Response::find()->alias('r')->innerJoinWith(['informant inf'])->with(['interpretations'])
            ->where(['r.question_id' => $question->id, 'r.flagDel' => 0, 'inf.flagDel' => 0])
            ->andWhere(['<>', 'r.value_text', ''])->andWhere(['not', ['r.value_text' => null]])
            ->orderBy(['inf.code' => SORT_ASC, 'r.id' => SORT_ASC])->all();

        $picked = [];   // response id => theme index
        foreach ($themes as $ti => $t) {
            if (!$t['members']) {
                continue;
            }
            $want = array_flip(array_map(fn($m) => $this->clean($m), $t['members']));
            $n = 0;
            foreach ($responses as $r) {
                if ($n >= self::QUOTES_PER_THEME || count($picked) >= self::MAX_QUOTES) {
                    break;
                }
                if (isset($picked[$r->id])) {
                    continue;
                }
                foreach ($r->interpretations as $it) {
                    if (!$it->flagDel && (int)$it->round <= 3 && isset($want[$this->clean($it->text)])) {
                        $picked[$r->id] = $ti;
                        $n++;
                        break;
                    }
                }
            }
        }
        foreach ($responses as $r) {   // เติมให้ครบจำนวนด้วยคำตอบที่ยังไม่ถูกเลือก
            if (count($picked) >= min(15, self::MAX_QUOTES) && $picked) {
                break;
            }
            $picked[$r->id] ??= null;
        }

        $out = [];
        $ref = 0;
        foreach ($responses as $r) {
            if (!array_key_exists($r->id, $picked)) {
                continue;
            }
            $out[] = [
                'ref' => ++$ref,
                'code' => $r->informant->code,
                'text' => mb_substr($this->clean($r->value_text), 0, 400),
                'theme' => $picked[$r->id],
            ];
        }
        return $out;
    }

    /**
     * @return array{job: AiJob, section: ReportSection}
     * @throws AiException
     */
    public function writeSection(Project $project, Question $question, int $userId): array
    {
        $t = $this->themes($question);
        if (!$t['themes']) {
            throw new AiException('คำถามนี้ยังไม่มีประเด็น — สรุปประเด็น/สังเคราะห์ก่อน แล้วค่อยให้ AI เขียนบทวิเคราะห์');
        }
        $quotes = $this->quotes($question, $t['themes']);
        $informants = (int)Response::find()->alias('r')->innerJoinWith(['informant inf'])
            ->where(['r.question_id' => $question->id, 'r.flagDel' => 0, 'inf.flagDel' => 0])
            ->andWhere(['<>', 'r.value_text', ''])->count();
        $messages = $this->sectionMessages($project, $question, $t, $quotes, $informants);
        $codes = array_column($quotes, 'code', 'ref');
        $answers = $this->answersByCode($question);
        $check = ['removed' => 0, 'badQuotes' => 0];

        [$job] = $this->execute($project, $question, AiJob::FEATURE_WRITE_SECTION, null, $userId, count($t['themes']), $messages,
            6000, 4500, function (string $content, AiJob $job) use ($project, $question, &$section): int {
                $section = $this->saveSection($project, $question->id, $content, $job);
                return 1;
            }, function (string $draft) use ($messages, $codes, $answers, &$check): string {
                $verified = $this->verify($messages[1]['content'], $draft, $check);
                return $this->checkQuotes($this->finish($verified, $codes), $answers, $check);
            });
        return ['job' => $job, 'section' => $section, 'check' => $check];
    }

    /**
     * @return array{job: AiJob, section: ReportSection}
     * @throws AiException
     */
    public function writeSummary(Project $project, int $userId): array
    {
        $sections = ReportSection::find()->alias('s')->innerJoin('{{%question}} q', 'q.id = s.question_id AND q.flagDel = 0')
            ->where(['s.project_id' => $project->id, 's.flagDel' => 0])
            ->orderBy(['q.sort_order' => SORT_ASC, 'q.id' => SORT_ASC])->all();
        if (!$sections) {
            throw new AiException('ยังไม่มีบทวิเคราะห์รายคำถาม — เขียนบทวิเคราะห์อย่างน้อย 1 ข้อก่อนสรุปภาพรวม');
        }
        $questions = Question::find()->where(['id' => array_map(fn($s) => $s->question_id, $sections)])->indexBy('id')->all();

        $system = <<<TXT
คุณเป็นผู้ช่วยนักวิจัยเชิงคุณภาพ เขียนรายงานวิจัยภาษาไทยแบบวิชาการ
งาน: เขียน "สรุปผลการวิจัย" ของทั้งโครงการ จากบทวิเคราะห์รายคำถามที่ให้มา
หลักการ:
- สรุปข้อค้นพบหลักของแต่ละคำถามอย่างกระชับ แล้วเชื่อมโยงภาพรวมตามกรอบ Input-Process-Output
- ใช้เฉพาะข้อมูลในบทวิเคราะห์ ห้ามเพิ่มข้อค้นพบหรือตัวเลขที่ไม่มี ไม่อ้างอิงงานวิจัยอื่น
- เขียนเป็นย่อหน้าภาษาไทยทางการ ยาวประมาณ 300–600 คำ ไม่ใช้หัวข้อแบบ markdown ไม่ใช้สัญลักษณ์ ** หรือ #
- ไม่ต้องขึ้นต้นด้วยชื่อบทหรือหัวข้อ (ระบบใส่ให้เอง) เริ่มที่เนื้อหาเลย
TXT;
        $system .= "\n" . self::GROUNDING_RULES;
        $lines = ['โครงการวิจัย: ' . $project->title];
        if ($project->description) {
            $lines[] = 'รายละเอียดโครงการ: ' . mb_substr($this->clean($project->description), 0, 1500);
        }
        foreach ($sections as $i => $s) {
            $lines[] = "\nคำถามที่ " . ($i + 1) . ': ' . ($questions[$s->question_id]->title ?? '');
            $lines[] = mb_substr(trim($s->content), 0, 4000);
        }
        $messages = [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => implode("\n", $lines)]];

        $check = ['removed' => 0, 'badQuotes' => 0];
        [$job] = $this->execute($project, null, AiJob::FEATURE_WRITE_SUMMARY, null, $userId, count($sections), $messages,
            5000, 3500, function (string $content, AiJob $job) use ($project, &$section): int {
                $section = $this->saveSection($project, null, $content, $job);
                return 1;
            }, function (string $draft) use ($messages, &$check): string {
                return $this->finish($this->verify($messages[1]['content'], $draft, $check), []);
            });
        return ['job' => $job, 'section' => $section, 'check' => $check];
    }

    public function sectionMessages(Project $project, Question $question, array $t, array $quotes, int $informants): array
    {
        $system = <<<TXT
คุณเป็นผู้ช่วยนักวิจัยเชิงคุณภาพ เขียนรายงานวิจัยภาษาไทยแบบวิชาการ (บทที่ 4 ผลการวิเคราะห์ข้อมูล)
งาน: เขียนบทวิเคราะห์ของคำถามการวิจัย 1 ข้อ จากประเด็นที่นักวิจัยสังเคราะห์ไว้และคำพูดของผู้ให้ข้อมูล
หลักการ:
- เปิดด้วยย่อหน้าสั้น ระบุจำนวนผู้ให้ข้อมูลและประเด็นที่พบ ห้ามบรรยายวัตถุประสงค์ ระเบียบวิธี หรือบริบทที่ไม่มีในข้อมูล
- จากนั้นเขียนทีละประเด็น เรียงตาม Input → Process → Output (ถ้ามี): บอกว่าผู้ให้ข้อมูลกล่าวถึงอะไรบ้างตามประเด็นย่อยที่ให้มา ระบุจำนวนคนเฉพาะที่ให้ไว้
- ยกคำพูดของผู้ให้ข้อมูลสนับสนุน 1–2 ตัวอย่างต่อประเด็น ใส่ในเครื่องหมาย "..." ตามด้วยเลขอ้างอิงในวงเล็บ เช่น "ค่าเดินทางแพง" (#3) — ใช้เฉพาะเลขของคำตอบที่มีถ้อยคำนั้นจริง
- คำพูดต้องคัดลอกจากคำตอบที่ให้มาเท่านั้น ตัดให้สั้นได้แต่ห้ามแก้ถ้อยคำ ห้ามแต่งคำพูดหรือเลขอ้างอิงที่ไม่มี
- ใช้เฉพาะข้อมูลที่ให้มา ห้ามเพิ่มข้อค้นพบ ตัวเลข หรืออ้างอิงงานวิจัยอื่น
- เขียนเป็นย่อหน้าภาษาไทยทางการ คั่นย่อหน้าด้วยบรรทัดว่าง ยาวประมาณ 400–900 คำ ไม่ใช้หัวข้อแบบ markdown ไม่ใช้สัญลักษณ์ ** หรือ #
- ไม่ต้องขึ้นต้นด้วยชื่อบทหรือหัวข้อ (ระบบใส่ให้เอง) เริ่มที่เนื้อหาเลย
TXT;
        $system .= "\n" . self::GROUNDING_RULES;
        $ipoLabel = ['input' => 'Input (ปัจจัยนำเข้า)', 'process' => 'Process (กระบวนการ)', 'output' => 'Output (ผลลัพธ์)'];
        $lines = [];
        $lines[] = 'โครงการวิจัย: ' . $project->title;
        $lines[] = 'คำถามการวิจัย: ' . $question->title;
        $lines[] = "จำนวนผู้ให้ข้อมูลที่ตอบคำถามนี้: {$informants} คน";
        $lines[] = "\n" . ($t['synthesized'] ? 'ประเด็นที่สังเคราะห์แล้ว:' : 'ประเด็นที่พบ (ยังไม่ได้สังเคราะห์):');
        foreach ($t['themes'] as $i => $th) {
            $line = '(' . ($i + 1) . ') ' . $th['text'];
            if ($th['ipo']) {
                $line .= ' [' . ($ipoLabel[$th['ipo']] ?? $th['ipo']) . ']';
            }
            if ($th['count']) {
                $line .= " (พบในผู้ให้ข้อมูล {$th['count']} คน)";
            }
            if ($th['members']) {
                $line .= "\n    ประเด็นย่อย: " . implode(' · ', array_map(fn($m) => $this->clean($m), $th['members']));
            }
            $lines[] = $line;
        }
        if ($quotes) {
            $lines[] = "\nคำตอบของผู้ให้ข้อมูล (ใช้ยกคำพูด อ้างอิงด้วยเลข #):";
            foreach ($quotes as $q) {
                $lines[] = "#{$q['ref']}" . ($q['theme'] !== null ? ' [ประเด็น (' . ($q['theme'] + 1) . ')]' : '') . ': ' . $q['text'];
            }
        }
        return [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => implode("\n", $lines)]];
    }

    /**
     * จัดข้อความที่ AI เขียน: แปลง #n เป็นรหัสผู้ให้ข้อมูล, ตัด markdown, จัดย่อหน้า
     * @param array<int, string> $codes ref => รหัสผู้ให้ข้อมูล
     */
    public function finish(string $content, array $codes): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($content));
        // (#3) / (#3, #5) / #3 → (ผู้ให้ข้อมูล P03) — เลขที่ไม่มีอยู่จริงถูกตัดทิ้ง
        $text = preg_replace_callback('/\(?\s*#\d+(?:\s*[,،、]\s*#\d+)*\s*\)?/u', function ($m) use ($codes) {
            preg_match_all('/#(\d+)/', $m[0], $nums);
            $found = array_values(array_unique(array_filter(array_map(fn($n) => $codes[(int)$n] ?? null, $nums[1]))));
            return $found ? '(ผู้ให้ข้อมูล ' . implode(', ', $found) . ')' : '';
        }, $text);
        $text = preg_replace('/^[ \t]{0,3}#{1,6}[ \t]*/mu', '', $text);      // หัวข้อ markdown
        $text = preg_replace('/(\*\*|__)(.+?)\1/u', '$2', $text);       // ตัวหนา
        // ระบบใส่ชื่อบท/หัวข้อเอง — ตัดบรรทัดแรกที่เป็นชื่อบท เช่น "บทที่ 4 ผลการวิเคราะห์ข้อมูล"
        $text = preg_replace('/^\s*(บทที่\s*\d+[^\n]*|สรุปผลการวิจัย|ผลการวิเคราะห์ข้อมูล)\s*\n/u', '', $text);
        $text = preg_replace('/^[ \t]*[-*•][ \t]+/mu', '', $text);           // bullet
        $text = preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);
        $text = trim($text);
        if (mb_strlen($text) < 50) {
            throw new AiException('AI เขียนเนื้อหาสั้นผิดปกติ ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
        }
        return $text;
    }

    /**
     * รอบตรวจทาน: ให้ AI อีกรอบ "ลบ" ข้อความที่ข้อมูลต้นฉบับไม่รองรับ แล้วยืนยันด้วยโค้ดว่าทำได้แค่ลบ
     * (ทุกตัวอักษรของฉบับตรวจต้องมาจากร่างเดิมตามลำดับ — ไม่มีทางเพิ่มเนื้อหาใหม่)
     *
     * @throws AiException ผู้ตรวจแก้ไขเกินกว่าการลบ หรือตัดจนแทบไม่เหลือ
     */
    public function verify(string $sourceData, string $draft, array &$check): string
    {
        $system = <<<TXT
คุณเป็นผู้ตรวจความถูกต้องของรายงานวิจัยเชิงคุณภาพ ภาษาไทย
งาน: ตรวจร่างทีละประโยคเทียบกับ "ข้อมูลต้นฉบับ" แล้วลบส่วนที่ข้อมูลต้นฉบับไม่รองรับโดยตรง
ต้องลบ:
- ข้อความที่สันนิษฐานสาเหตุ ผลกระทบ ความรู้สึก แรงจูงใจ หรือความหมายแฝง ที่ไม่มีในข้อมูล
- คำบอกปริมาณ/ความถี่ (ส่วนใหญ่ หลายคน มักจะ ฯลฯ) ที่ไม่มีตัวเลขรองรับ
- ความรู้ภายนอก บริบท ทฤษฎี ข้อเสนอแนะ วัตถุประสงค์หรือระเบียบวิธีวิจัยที่ไม่มีในข้อมูล
- คำขยายเกินจริงที่ผู้ให้ข้อมูลไม่ได้ใช้
- คำพูดในเครื่องหมาย "..." ที่ไม่ตรงกับคำตอบของเลขอ้างอิงนั้น
ข้อห้ามของผู้ตรวจ: ทำได้ "ลบ" อย่างเดียว ห้ามเพิ่ม ห้ามแก้คำ ห้ามเรียบเรียงใหม่ ห้ามสลับลำดับ ห้ามเปลี่ยนเครื่องหมาย
ตอบเป็นข้อความร่างฉบับที่ลบแล้วเท่านั้น ไม่มีคำอธิบายอื่น (ถ้าไม่มีอะไรต้องลบ ให้ตอบร่างเดิมทั้งหมด)
TXT;
        $r = $this->chatMore([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "ข้อมูลต้นฉบับ:\n{$sourceData}\n\n----- ร่างที่ต้องตรวจ -----\n{$draft}"],
        ], 6000);
        $out = trim($r->content);
        if (!self::isDeletionOnly($draft, $out)) {
            Yii::warning('Verifier changed text beyond deletion', __METHOD__);
            throw new AiException('รอบตรวจทานของ AI แก้ข้อความเกินกว่าการตัดออก จึงไม่บันทึก ไม่ได้หักเครดิต ลองใหม่อีกครั้ง');
        }
        $removed = mb_strlen(preg_replace('/\s+/u', '', $draft)) - mb_strlen(preg_replace('/\s+/u', '', $out));
        $check['removed'] += max(0, $removed);
        return $out;
    }

    /**
     * true เมื่อ $after ได้มาจากการลบตัวอักษรบางส่วนของ $before เท่านั้น (ไม่นับช่องว่าง/ขึ้นบรรทัด)
     */
    public static function isDeletionOnly(string $before, string $after): bool
    {
        $a = preg_split('//u', preg_replace('/\s+/u', '', $before), -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', preg_replace('/\s+/u', '', $after), -1, PREG_SPLIT_NO_EMPTY);
        if (!$b) {
            return false;
        }
        $i = 0;
        $n = count($a);
        foreach ($b as $ch) {
            while ($i < $n && $a[$i] !== $ch) {
                $i++;
            }
            if ($i >= $n) {
                return false;
            }
            $i++;
        }
        return true;
    }

    /**
     * ตรวจคำพูดทุกจุด: "..." (ผู้ให้ข้อมูล X) ต้องเป็นถ้อยคำที่อยู่ในคำตอบของ X จริง ไม่เช่นนั้นตัดทิ้งทั้งคำพูดและการอ้างอิง
     * @param array<string, string> $answers รหัสผู้ให้ข้อมูล => คำตอบเต็ม
     */
    public function checkQuotes(string $text, array $answers, array &$check): string
    {
        $norm = fn(string $s) => preg_replace('/[\s"“”\'‘’.,…]+/u', '', $s);
        $text = preg_replace_callback('/["“]([^"”]+)["”]\s*(\(ผู้ให้ข้อมูล ([^)]+)\))?/u', function ($m) use ($answers, $norm, &$check) {
            $quote = $norm($m[1]);
            $codes = isset($m[3]) ? array_map('trim', explode(',', $m[3])) : array_keys($answers);
            foreach ($codes as $c) {
                if ($quote !== '' && isset($answers[$c]) && str_contains($norm($answers[$c]), $quote)) {
                    return $m[0];
                }
            }
            $check['badQuotes']++;
            return '';
        }, $text);
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);
        $text = preg_replace('/\s+([,.])/u', '$1', $text);
        return trim($text);
    }

    /** @return array<string, string> */
    private function answersByCode(Question $question): array
    {
        $out = [];
        foreach (Response::find()->alias('r')->innerJoinWith(['informant inf'])
            ->where(['r.question_id' => $question->id, 'r.flagDel' => 0, 'inf.flagDel' => 0])->all() as $r) {
            $out[$r->informant->code] = trim(($out[$r->informant->code] ?? '') . ' ' . $r->value_text);
        }
        return $out;
    }

    private function saveSection(Project $project, ?int $questionId, string $content, AiJob $job): ReportSection
    {
        // ฉบับเดิมไม่ลบทิ้ง — ซ่อนไว้ (flagDel) เผื่อต้องย้อนดู
        ReportSection::updateAll(['flagDel' => 1], ['project_id' => $project->id, 'question_id' => $questionId, 'flagDel' => 0]);
        $s = new ReportSection([
            'project_id' => $project->id,
            'question_id' => $questionId,
            'content' => $content,
            'source' => Interpretation::SOURCE_AI,
            'ai_job_id' => $job->id,
        ]);
        if (!$s->save()) {
            throw new AiException(implode(' ', $s->getFirstErrors()));
        }
        return $s;
    }
}
