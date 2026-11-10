<?php

namespace tests\unit\components;

use app\components\ai\AiException;
use app\components\ai\ConceptBuilder;
use app\components\ai\Credits;
use app\models\AiJob;
use app\models\Concept;
use app\models\Interpretation;
use app\tests\Support\Factory;
use app\tests\Support\FakeChatClient;
use Yii;

class ConceptBuilderTest extends \Codeception\Test\Unit
{
    private const MODEL = 'anthropic/claude-opus-5.5';

    private $user;
    private $project;
    private $q1;
    private $q2;

    protected function _before()
    {
        Factory::reset();
        Yii::$app->params['ai.usdToThb'] = 34.0;
        Yii::$app->params['ai.markup'] = 1.5;
        Yii::$app->params['ai.bahtPerCredit'] = 0.10;
        $this->user = Factory::user();
        $this->project = Factory::project($this->user, ['ai_enabled' => 1]);
        $this->q1 = Factory::question($this->project, ['title' => 'อุปสรรค']);
        $this->q2 = Factory::question($this->project, ['title' => 'การจัดการ']);
        Credits::grant($this->user->id, 1000, null, null);
    }

    private function synth($q, string $text, ?string $ipo): void
    {
        (new Interpretation(['question_id' => $q->id, 'round' => 4, 'text' => $text, 'ipo' => $ipo]))->save(false);
    }

    public function testItemsPreferSynthesisAndFallBackToRawIssues()
    {
        $this->synth($this->q1, 'ภาระค่าใช้จ่าย', 'input');
        $r = Factory::response(Factory::informant($this->project, 'P01'), $this->q2, 'ไปวัด');
        (new Interpretation(['question_id' => $this->q2->id, 'response_id' => $r->id, 'round' => 1, 'text' => 'ใช้ศาสนาคลายเครียด']))->save(false);

        $items = (new ConceptBuilder(new FakeChatClient('{}'), self::MODEL))->items($this->project);
        verify($items)->equals([
            ['text' => 'ภาระค่าใช้จ่าย', 'ipo' => 'input', 'question' => 'อุปสรรค'],
            ['text' => 'ใช้ศาสนาคลายเครียด', 'ipo' => null, 'question' => 'การจัดการ'],
        ]);
    }

    public function testRunCreatesPendingConceptsWithSources()
    {
        $this->synth($this->q1, 'ภาระค่าใช้จ่าย', 'input');
        $this->synth($this->q1, 'ขาดผู้ช่วยดูแล', 'input');
        $this->synth($this->q2, 'ใช้ศาสนาคลายเครียด', 'process');
        (new Concept(['project_id' => $this->project->id, 'name' => 'แนวคิดเดิม']))->save(false);

        $client = new FakeChatClient(json_encode(['concepts' => [
            ['name' => 'ข้อจำกัดของผู้ดูแล', 'input' => true, 'problem' => true, 'members' => [1, 2, 2, 99]],
            ['name' => 'การจัดการความเครียด', 'process' => true, 'success' => 'false', 'members' => [3]],
            ['name' => 'แนว คิดเดิม', 'output' => true, 'members' => [1]],          // ซ้ำกับที่มีอยู่
            ['name' => 'แนวคิดลอย ๆ', 'output' => true, 'members' => []],          // ไม่มีที่มา
        ]], JSON_UNESCAPED_UNICODE), 1000, 300, 0.001);
        $r = (new ConceptBuilder($client, self::MODEL))->run($this->project, $this->user->id);

        verify($r['created'])->equals(2);
        verify($r['job']->feature)->equals(AiJob::FEATURE_CONCEPT_MAP);
        $sent = $client->calls[0]['messages'];
        verify($sent[0]['content'])->stringContainsString('ห้ามตีความเกินข้อมูล');
        verify($sent[1]['content'])->stringContainsString('แนวคิดเดิม');

        $c = Concept::find()->where(['ai_pending' => 1])->orderBy('id')->all();
        verify(array_map(fn($x) => [$x->name, (int)$x->is_input, (int)$x->is_process, (int)$x->is_problem, (int)$x->is_success, $x->source], $c))->equals([
            ['ข้อจำกัดของผู้ดูแล', 1, 0, 1, 0, 'ai'],
            ['การจัดการความเครียด', 0, 1, 0, 0, 'ai'],
        ]);
        verify($c[0]->getMemberList())->equals(['ภาระค่าใช้จ่าย', 'ขาดผู้ช่วยดูแล']);

        // มีข้อเสนอค้าง → สั่งใหม่ไม่ได้ ไม่หักเครดิต
        $before = Credits::balance($this->user->id);
        verify(fn() => (new ConceptBuilder($client, self::MODEL))->run($this->project, $this->user->id))->callableThrows(AiException::class);
        verify(Credits::balance($this->user->id))->equals($before);
    }

    public function testNothingNewRefunds()
    {
        $this->synth($this->q1, 'ก', 'input');
        $this->synth($this->q1, 'ข', 'input');
        try {
            (new ConceptBuilder(new FakeChatClient('{"concepts":[{"name":"x","members":[]}]}'), self::MODEL))->run($this->project, $this->user->id);
            $this->fail('expected exception');
        } catch (AiException) {
        }
        verify(Credits::balance($this->user->id))->equals(1000);
        verify(Concept::find()->count())->equals(0);
    }

    public function testNeedsIssues()
    {
        $this->expectException(AiException::class);
        (new ConceptBuilder(new FakeChatClient('{}'), self::MODEL))->run($this->project, $this->user->id);
    }
}
