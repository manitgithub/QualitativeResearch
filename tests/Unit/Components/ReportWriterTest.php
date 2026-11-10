<?php

namespace tests\unit\components;

use app\components\ai\AiException;
use app\components\ai\Credits;
use app\components\ai\ReportWriter;
use app\models\AiJob;
use app\models\AiSuggestion;
use app\models\Interpretation;
use app\models\ReportSection;
use app\tests\Support\Factory;
use app\tests\Support\FakeChatClient;
use Yii;

class ReportWriterTest extends \Codeception\Test\Unit
{
    private const MODEL = 'anthropic/claude-opus-5.5';
    private const ESSAY = "ผลการวิเคราะห์พบว่าผู้ดูแลเผชิญข้อจำกัดหลายด้าน ทั้งด้านเวลาและค่าใช้จ่าย ซึ่งส่งผลต่อการดูแลผู้สูงอายุโดยตรง";

    private $user;
    private $project;
    private $question;
    private array $r = [];

    protected function _before()
    {
        Factory::reset();
        Yii::$app->params['ai.usdToThb'] = 34.0;
        Yii::$app->params['ai.markup'] = 1.5;
        Yii::$app->params['ai.bahtPerCredit'] = 0.10;
        $this->user = Factory::user();
        $this->project = Factory::project($this->user, ['ai_enabled' => 1]);
        $this->question = Factory::question($this->project, ['title' => 'อุปสรรคในการดูแล']);
        foreach (['P01' => 'ต้องทำงานทั้งวันไม่มีเวลา', 'P02' => 'ค่ารถไปโรงพยาบาลแพง', 'P03' => 'ไปวัดแล้วสบายใจ'] as $code => $text) {
            $this->r[$code] = Factory::response(Factory::informant($this->project, $code, ['note' => 'ความลับ']), $this->question, $text);
        }
        Credits::grant($this->user->id, 1000, null, null);
    }

    private function interp(?string $code, int $round, string $text, ?string $ipo = null): Interpretation
    {
        $it = new Interpretation(['question_id' => $this->question->id, 'response_id' => $code ? $this->r[$code]->id : null,
            'round' => $round, 'text' => $text, 'ipo' => $ipo]);
        $it->save(false);
        return $it;
    }

    private function writer($reply): ReportWriter
    {
        return new ReportWriter(new FakeChatClient($reply, 2000, 1500, 0.001), self::MODEL);
    }

    public function testFinishReplacesRefsAndStripsMarkdown()
    {
        $w = $this->writer('');
        $out = $w->finish("## หัวข้อ\n\n**สำคัญ** ผู้ให้ข้อมูลกล่าวว่า \"ไม่มีเวลา\" (#1) และ \"แพง\" (#2, #9) ส่วน #7 ไม่มีจริง " . self::ESSAY . "\n\n\n\n- ข้อสอง", [1 => 'P01', 2 => 'P02']);
        verify($out)->stringContainsString('"ไม่มีเวลา" (ผู้ให้ข้อมูล P01)');
        verify($out)->stringContainsString('"แพง" (ผู้ให้ข้อมูล P02)');
        verify($out)->stringNotContainsString('#');
        verify($out)->stringNotContainsString('**');
        verify($out)->stringStartsWith('หัวข้อ');
        verify($out)->stringNotContainsString("\n\n\n");
        verify($out)->stringContainsString("\n\nข้อสอง");
    }

    public function testFinishDropsChapterTitle()
    {
        $out = $this->writer('')->finish("บทที่ 4 ผลการวิเคราะห์ข้อมูล\n\n" . self::ESSAY, []);
        verify($out)->equals(self::ESSAY);
    }

    public function testFinishRejectsEmptyText()
    {
        $this->expectException(AiException::class);
        $this->writer('')->finish('สั้น', []);
    }

    public function testThemesUseSynthesisAndQuotesFollowMembers()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ค่าเดินทางแพง');
        $this->interp('P03', 1, 'ใช้ศาสนาคลายเครียด');
        $theme = $this->interp(null, 4, 'ข้อจำกัดด้านเวลาและค่าใช้จ่าย', 'input');
        (new AiSuggestion(['ai_job_id' => $this->job(), 'question_id' => $this->question->id, 'round' => 4, 'text' => $theme->text,
            'members' => json_encode(['ไม่มีเวลา', 'ค่าเดินทางแพง'], JSON_UNESCAPED_UNICODE), 'status' => 'accepted',
            'interpretation_id' => $theme->id, 'created_at' => time()]))->save(false);

        $w = $this->writer('');
        $t = $w->themes($this->question);
        verify($t['synthesized'])->true();
        verify($t['themes'][0]['members'])->equals(['ไม่มีเวลา', 'ค่าเดินทางแพง']);
        $q = $w->quotes($this->question, $t['themes']);
        verify(array_column($q, 'code'))->equals(['P01', 'P02', 'P03']);
        verify(array_column($q, 'theme'))->equals([0, 0, null]);
        verify(array_column($q, 'ref'))->equals([1, 2, 3]);
    }

    private function job(): int
    {
        $j = new AiJob(['user_id' => $this->user->id, 'project_id' => $this->project->id, 'feature' => 'x', 'model' => 'm', 'created_at' => time()]);
        $j->save(false);
        return $j->id;
    }

    public function testWriteSectionSavesAndKeepsOldVersion()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp(null, 4, 'ข้อจำกัดด้านเวลา', 'input');
        $old = new ReportSection(['project_id' => $this->project->id, 'question_id' => $this->question->id, 'content' => 'ฉบับเดิม', 'source' => 'human']);
        $old->save(false);

        $client = new FakeChatClient(self::ESSAY . ' ดังที่ว่า "ทำงานทั้งวัน" (#1)', 2000, 1500, 0.001);
        $r = (new ReportWriter($client, self::MODEL))->writeSection($this->project, $this->question, $this->user->id);

        $sent = json_encode($client->calls[0]['messages'], JSON_UNESCAPED_UNICODE);
        verify($sent)->stringContainsString('ข้อจำกัดด้านเวลา');
        verify($sent)->stringContainsString('#1');
        verify($sent)->stringNotContainsString('P01');      // ไม่ส่งรหัสผู้ให้ข้อมูล
        verify($sent)->stringNotContainsString('ความลับ');

        verify($r['section']->content)->stringContainsString('"ทำงานทั้งวัน" (ผู้ให้ข้อมูล P01)');
        verify($r['section']->source)->equals('ai');
        verify($r['job']->feature)->equals(AiJob::FEATURE_WRITE_SECTION);
        verify(ReportSection::find()->where(['question_id' => $this->question->id])->count())->equals(1);
        verify(ReportSection::find()->withDeleted()->where(['id' => $old->id, 'flagDel' => 1])->exists())->true();
        verify(Credits::balance($this->user->id))->lessThan(1000);
    }

    public function testWriteSectionNeedsIssues()
    {
        $this->expectException(AiException::class);
        $this->writer(self::ESSAY)->writeSection($this->project, $this->question, $this->user->id);
    }

    public function testFailedWriteRefundsAndKeepsOldText()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $old = new ReportSection(['project_id' => $this->project->id, 'question_id' => $this->question->id, 'content' => 'ฉบับเดิม', 'source' => 'human']);
        $old->save(false);
        try {
            $this->writer('สั้น')->writeSection($this->project, $this->question, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException) {
        }
        verify(Credits::balance($this->user->id))->equals(1000);
        verify(ReportSection::findOne($old->id)->content)->equals('ฉบับเดิม');
    }

    public function testSummaryFromSections()
    {
        verify(fn() => $this->writer(self::ESSAY)->writeSummary($this->project, $this->user->id))
            ->callableThrows(AiException::class);
        (new ReportSection(['project_id' => $this->project->id, 'question_id' => $this->question->id, 'content' => 'บทวิเคราะห์ข้อหนึ่ง', 'source' => 'human']))->save(false);

        $client = new FakeChatClient(self::ESSAY, 1000, 800, 0.001);
        $r = (new ReportWriter($client, self::MODEL))->writeSummary($this->project, $this->user->id);
        verify($client->calls[0]['messages'][1]['content'])->stringContainsString('บทวิเคราะห์ข้อหนึ่ง');
        verify($r['section']->question_id)->null();
        verify(ReportSection::forProject($this->project->id))->arrayHasKey('summary');
    }

    public function testDeletionOnlyCheck()
    {
        $draft = "ผู้ดูแลไม่มีเวลา ซึ่งทำให้รู้สึกผิดอย่างมาก\n\nย่อหน้าสอง";
        verify(ReportWriter::isDeletionOnly($draft, $draft))->true();
        verify(ReportWriter::isDeletionOnly($draft, "ผู้ดูแลไม่มีเวลา\n\nย่อหน้าสอง"))->true();   // ลบได้
        verify(ReportWriter::isDeletionOnly($draft, 'ผู้ดูแลไม่มีเวลาเลย'))->false();             // เพิ่มคำ
        verify(ReportWriter::isDeletionOnly($draft, 'ย่อหน้าสอง ผู้ดูแลไม่มีเวลา'))->false();      // สลับลำดับ
        verify(ReportWriter::isDeletionOnly($draft, ''))->false();
    }

    public function testCheckQuotesDropsFabricatedOrMisattributedQuotes()
    {
        $answers = ['P01' => 'ต้องทำงานทั้งวัน ไม่มีเวลา', 'P02' => 'ค่ารถไปโรงพยาบาลแพง'];
        $check = ['removed' => 0, 'badQuotes' => 0];
        $out = $this->writer('')->checkQuotes(
            'ก "ต้องทำงานทั้งวัน" (ผู้ให้ข้อมูล P01) ข "ค่ารถแพงมาก" (ผู้ให้ข้อมูล P02) ค "ค่ารถไปโรงพยาบาลแพง" (ผู้ให้ข้อมูล P01) ง “ไม่มีเวลา”',
            $answers, $check);
        verify($out)->stringContainsString('"ต้องทำงานทั้งวัน" (ผู้ให้ข้อมูล P01)');
        verify($out)->stringContainsString('“ไม่มีเวลา”');                 // ไม่มีอ้างอิงแต่มีในคำตอบจริง
        verify($out)->stringNotContainsString('ค่ารถแพงมาก');              // แต่งคำพูด
        verify($out)->stringNotContainsString('"ค่ารถไปโรงพยาบาลแพง" (ผู้ให้ข้อมูล P01)'); // อ้างผิดคน
        verify($check['badQuotes'])->equals(2);
    }

    public function testVerifierDeletionsAppliedAndBilledTogether()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $draft = self::ESSAY . ' ซึ่งน่าจะทำให้ผู้ดูแลรู้สึกเสียดายโอกาสอย่างยิ่ง ดังที่ว่า "ทำงานทั้งวัน" (#1)';
        $client = new FakeChatClient([$draft, self::ESSAY . ' ดังที่ว่า "ทำงานทั้งวัน" (#1)'], 2000, 1500, 0.001);
        $r = (new ReportWriter($client, self::MODEL))->writeSection($this->project, $this->question, $this->user->id);

        verify($client->calls)->arrayCount(2);
        verify($client->calls[1]['messages'][1]['content'])->stringContainsString('ร่างที่ต้องตรวจ');
        verify($r['section']->content)->stringNotContainsString('เสียดายโอกาส');
        verify($r['section']->content)->stringContainsString('(ผู้ให้ข้อมูล P01)');
        verify($r['check']['removed'])->greaterThan(0);
        verify((float)AiJob::findOne($r['job']->id)->cost_usd)->equals(0.002); // 2 การเรียกรวมกัน
        verify(AiJob::findOne($r['job']->id)->input_tokens)->equals(4000);
    }

    public function testVerifierThatRewritesIsRejectedAndRefunded()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $client = new FakeChatClient([self::ESSAY, self::ESSAY . ' และเพิ่มข้อสรุปใหม่ที่ไม่มีในร่าง'], 2000, 1500, 0.001);
        try {
            (new ReportWriter($client, self::MODEL))->writeSection($this->project, $this->question, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException $e) {
            verify($e->getMessage())->stringContainsString('ตรวจทาน');
        }
        verify(Credits::balance($this->user->id))->equals(1000);
        verify(ReportSection::find()->count())->equals(0);
    }

    public function testEveryPromptCarriesGroundingRules()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ค่ารถแพง');
        $client = new FakeChatClient(self::ESSAY, 100, 100, 0.0001);
        (new ReportWriter($client, self::MODEL))->writeSection($this->project, $this->question, $this->user->id);
        verify($client->calls[0]['messages'][0]['content'])->stringContainsString('ห้ามตีความเกินข้อมูล');

        $s = new \app\components\ai\InterpretationSuggester($client, self::MODEL);
        $resp = $s->candidates($this->question, 1);
        verify($s->buildMessages($this->project, $this->question, 1, $resp)[0]['content'])->stringContainsString('ห้ามตีความเกินข้อมูล');
        verify($s->buildSynthesisMessages($this->project, $this->question, $s->synthesisItems($this->question))[0]['content'])
            ->stringContainsString('ห้ามตีความเกินข้อมูล');
    }

    public function testEditingAiTextMarksIt()
    {
        $s = new ReportSection(['project_id' => $this->project->id, 'question_id' => $this->question->id, 'content' => 'ก', 'source' => 'ai']);
        $s->save(false);
        $s->content = 'ข';
        $s->save();
        verify(ReportSection::findOne($s->id)->source)->equals('ai_edited');
    }
}
