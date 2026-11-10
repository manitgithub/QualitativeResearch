<?php

namespace tests\unit\components;

use app\components\ai\AiException;
use app\components\ai\Credits;
use app\components\ai\InterpretationSuggester;
use app\tests\Support\FakeChatClient;
use app\models\AiJob;
use app\models\AiSuggestion;
use app\models\Interpretation;
use app\tests\Support\Factory;

class InterpretationSuggesterTest extends \Codeception\Test\Unit
{
    private const MODEL = 'anthropic/claude-opus-5.5';

    private $user;
    private $project;
    private $question;
    private array $responses = [];

    protected function _before()
    {
        Factory::reset();
        // ตรึงสูตรราคาไว้ ไม่ให้ test ขึ้นกับค่าใน config/params.php
        \Yii::$app->params['ai.usdToThb'] = 34.0;
        \Yii::$app->params['ai.markup'] = 1.5;
        \Yii::$app->params['ai.bahtPerCredit'] = 0.10;
        $this->user = Factory::user();
        $this->project = Factory::project($this->user, ['ai_enabled' => 1]);
        $this->question = Factory::question($this->project, ['title' => 'อุปสรรคในการดูแลผู้ป่วย']);
        foreach (['P01' => 'ไม่มีเวลาเพราะต้องทำงาน', 'P02' => 'ค่าเดินทางแพง', 'P03' => ''] as $code => $text) {
            $inf = Factory::informant($this->project, $code, ['note' => 'ความลับ-ห้ามส่ง', 'age' => 77]);
            $this->responses[$code] = Factory::response($inf, $this->question, $text);
        }
        Credits::grant($this->user->id, 1000, null, null);
    }

    private function suggester($reply, ?float $cost = 0.01): InterpretationSuggester
    {
        return new InterpretationSuggester(new FakeChatClient($reply, 2000, 800, $cost), self::MODEL, 10);
    }

    public function testCreatesPendingSuggestionsAndCharges()
    {
        $reply = "```json\n" . json_encode(['items' => [
            ['id' => 1, 'suggestions' => [['text' => 'ขาดเวลา', 'ipo' => 'input'], ['text' => 'ภาระงาน', 'ipo' => 'bogus']]],
            ['id' => 2, 'suggestions' => [['text' => 'ค่าใช้จ่ายเดินทาง', 'ipo' => null]]],
            ['id' => 9, 'suggestions' => [['text' => 'id ที่ไม่ได้ขอ']]],
        ]], JSON_UNESCAPED_UNICODE) . "\n```";

        $r = $this->suggester($reply)->run($this->project, $this->question, 1, $this->user->id);

        verify($r['created'])->equals(3);
        verify($r['remaining'])->equals(0);
        $s = AiSuggestion::find()->where(['response_id' => $this->responses['P01']->id])->orderBy('id')->all();
        verify(array_map(fn($x) => [$x->text, $x->ipo, $x->status], $s))->equals([
            ['ขาดเวลา', 'input', 'pending'],
            ['ภาระงาน', null, 'pending'],
        ]);
        $job = AiJob::findOne($r['job']->id);
        verify($job->status)->equals(AiJob::STATUS_DONE);
        verify($job->item_count)->equals(2); // คำตอบว่างไม่ถูกส่ง
        verify($job->credits_charged)->equals(6); // $0.01 → 6 เครดิต
        verify(Credits::balance($this->user->id))->equals(994);
    }

    public function testDoesNotSendInformantPersonalData()
    {
        $client = new FakeChatClient('{"items":[]}');
        (new InterpretationSuggester($client, self::MODEL))->run($this->project, $this->question, 1, $this->user->id);
        $sent = json_encode($client->calls[0]['messages'], JSON_UNESCAPED_UNICODE);
        verify($sent)->stringContainsString('ค่าเดินทางแพง');
        verify($sent)->stringNotContainsString('P01');
        verify($sent)->stringNotContainsString('ความลับ-ห้ามส่ง');
    }

    public function testFailureRefundsAllCredits()
    {
        try {
            $this->suggester(new AiException('บริการล่ม'))->run($this->project, $this->question, 1, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException $e) {
            verify($e->getMessage())->equals('บริการล่ม');
        }
        verify(Credits::balance($this->user->id))->equals(1000);
        verify(AiJob::find()->one()->status)->equals(AiJob::STATUS_FAILED);
        verify(AiSuggestion::find()->count())->equals(0);
    }

    public function testGarbageReplyRefundsAllCredits()
    {
        $this->expectException(AiException::class);
        try {
            $this->suggester('ขอโทษ ฉันไม่เข้าใจ')->run($this->project, $this->question, 1, $this->user->id);
        } finally {
            verify(Credits::balance($this->user->id))->equals(1000);
        }
    }

    public function testInsufficientCreditsDoesNotCallAi()
    {
        $poor = Factory::user();
        $client = new FakeChatClient('{"items":[]}');
        try {
            (new InterpretationSuggester($client, self::MODEL))->run($this->project, $this->question, 1, $poor->id);
            $this->fail('expected exception');
        } catch (AiException $e) {
            verify($e->getMessage())->stringContainsString('เครดิตไม่พอ');
        }
        verify($client->calls)->empty();
        verify(Credits::balance($poor->id))->equals(0);
    }

    public function testSkipsResponsesAlreadyDoneOrPending()
    {
        (new Interpretation(['question_id' => $this->question->id, 'response_id' => $this->responses['P01']->id, 'round' => 1, 'text' => 'มีแล้ว']))->save(false);
        $s = $this->suggester('{"items":[{"id":1,"suggestions":[{"text":"ค่าเดินทาง","ipo":null}]}]}');
        verify(array_map(fn($r) => $r->id, $s->candidates($this->question, 1)))->equals([$this->responses['P02']->id]);
        $s->run($this->project, $this->question, 1, $this->user->id);
        // P02 มีข้อเสนอค้างแล้ว → ไม่มีอะไรเหลือให้ทำ
        verify($s->candidates($this->question, 1))->empty();
    }

    public function testRoundTwoNeedsRoundOneAndSendsPreviousIssues()
    {
        (new Interpretation(['question_id' => $this->question->id, 'response_id' => $this->responses['P02']->id, 'round' => 1, 'text' => 'ค่าใช้จ่ายเดินทางสูง']))->save(false);
        $client = new FakeChatClient('{"items":[]}');
        $s = new InterpretationSuggester($client, self::MODEL);
        verify(array_map(fn($r) => $r->id, $s->candidates($this->question, 2)))->equals([$this->responses['P02']->id]);
        $s->run($this->project, $this->question, 2, $this->user->id);
        verify($client->calls[0]['messages'][1]['content'])->stringContainsString('ประเด็นรอบก่อน: ค่าใช้จ่ายเดินทางสูง');
    }

    private function interp(string $code, int $round, string $text): void
    {
        (new Interpretation(['question_id' => $this->question->id, 'response_id' => $this->responses[$code]->id,
            'round' => $round, 'text' => $text]))->save(false);
    }

    public function testSynthesisItemsDedupedAndRankedByFrequency()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ค่าเดินทางแพง');
        $this->interp('P01', 2, 'ข้อจำกัดด้านเวลา');
        $this->interp('P01', 3, 'ภาระของผู้ดูแล');
        // รอบ 4 และที่ลบแล้วไม่นับ
        (new Interpretation(['question_id' => $this->question->id, 'round' => 4, 'text' => 'สังเคราะห์เดิม']))->save(false);
        (new Interpretation(['question_id' => $this->question->id, 'response_id' => $this->responses['P01']->id, 'round' => 1, 'text' => 'ลบแล้ว', 'flagDel' => 1]))->save(false);

        $items = $this->suggester('{}')->synthesisItems($this->question);
        verify(array_column($items, 'text'))->equals(['ไม่มีเวลา', 'ภาระของผู้ดูแล', 'ข้อจำกัดด้านเวลา', 'ค่าเดินทางแพง']);
        verify($items[0]['count'])->equals(2);
    }

    public function testSynthesisCreatesQuestionLevelGroups()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ค่าเดินทางแพง');
        $this->interp('P01', 2, 'ภาระงาน');
        (new Interpretation(['question_id' => $this->question->id, 'round' => 4, 'text' => 'ข้อจำกัดของครอบครัว']))->save(false);
        $client = new FakeChatClient(json_encode(['themes' => [
            ['text' => 'ข้อจำกัดของครอบครัว', 'ipo' => 'input', 'members' => [1, 2, 2, 99]],
            ['text' => 'ภาระงาน', 'ipo' => 'nope', 'members' => [1, 3]], // 1 ถูกใช้แล้ว → เหลือ 3
            ['text' => '', 'ipo' => 'output', 'members' => []],
            ['text' => 'กลุ่มว่างที่ AI แต่งเอง', 'ipo' => 'output', 'members' => []],
            ['text' => 'สมาชิกซ้ำกับกลุ่มอื่นทั้งหมด', 'ipo' => 'output', 'members' => [1, 2]],
        ]], JSON_UNESCAPED_UNICODE), 3000, 400, 0.001);
        $r = (new InterpretationSuggester($client, self::MODEL))->runSynthesis($this->project, $this->question, $this->user->id);

        verify($r['created'])->equals(2);
        verify($r['job']->feature)->equals(AiJob::FEATURE_SYNTHESIZE_IPO);
        $sent = $client->calls[0]['messages'][1]['content'];
        verify($sent)->stringContainsString('ประเด็นสังเคราะห์ที่นักวิจัยมีแล้ว');
        verify($sent)->stringContainsString('ค่าเดินทางแพง');

        $s = AiSuggestion::find()->where(['round' => 4])->orderBy('id')->all();
        verify(array_map(fn($x) => [$x->response_id, $x->text, $x->ipo], $s))->equals([
            [null, 'ข้อจำกัดของครอบครัว', 'input'],
            [null, 'ภาระงาน', null],
        ]);
        $items = array_column((new InterpretationSuggester($client, self::MODEL))->synthesisItems($this->question), 'text');
        verify($s[0]->getMemberList())->equals([$items[0], $items[1]]);
        verify($s[1]->getMemberList())->equals([$items[2]]);

        // รับ → ประเด็นระดับคำถาม รอบ 4
        $it = $s[0]->accept($this->user->id);
        verify($it->response_id)->null();
        verify($it->round)->equals(4);
        verify($it->source)->equals(Interpretation::SOURCE_AI);

        // ยังมีข้อเสนอค้าง → สั่งใหม่ไม่ได้ และไม่หักเครดิต
        $before = Credits::balance($this->user->id);
        try {
            (new InterpretationSuggester($client, self::MODEL))->runSynthesis($this->project, $this->question, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException $e) {
            verify($e->getMessage())->stringContainsString('ค้างอยู่');
        }
        verify(Credits::balance($this->user->id))->equals($before);
    }

    public function testSynthesisNeedsIssuesFirst()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->expectException(AiException::class);
        $this->suggester('{}')->runSynthesis($this->project, $this->question, $this->user->id);
    }

    public function testSynthesisWithNoGroupsRefunds()
    {
        $this->interp('P01', 1, 'ไม่มีเวลา');
        $this->interp('P02', 1, 'ค่าเดินทางแพง');
        try {
            $this->suggester('{"themes":[]}')->runSynthesis($this->project, $this->question, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException) {
        }
        verify(Credits::balance($this->user->id))->equals(1000);
        verify(AiSuggestion::find()->count())->equals(0);
    }

    public function testAcceptCreatesAiInterpretationAndEditMarksIt()
    {
        $this->suggester('{"items":[{"id":1,"suggestions":[{"text":"ขาดเวลา","ipo":"input"}]}]}')
            ->run($this->project, $this->question, 1, $this->user->id);
        $sg = AiSuggestion::find()->one();
        $it = $sg->accept($this->user->id);
        verify($it->source)->equals(Interpretation::SOURCE_AI);
        verify($it->response_id)->equals($this->responses['P01']->id);
        verify(AiSuggestion::findOne($sg->id)->interpretation_id)->equals($it->id);

        $it->text = 'ขาดเวลาดูแล';
        $it->save();
        verify(Interpretation::findOne($it->id)->source)->equals(Interpretation::SOURCE_AI_EDITED);
    }
}
