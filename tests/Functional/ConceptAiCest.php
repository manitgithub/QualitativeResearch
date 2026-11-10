<?php

namespace tests\functional;

use app\models\Concept;
use app\models\ProjectMember;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * ปุ่ม AI ช่วยใน Conceptual map: แสดง/รับ/ปฏิเสธข้อเสนอ และสิทธิ์
 */
class ConceptAiCest
{
    private array $u = [];
    private $p;

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
        Yii::$app->params['openrouter.apiKey'] = 'x';
        $this->u = ['owner' => Factory::user(), 'viewer' => Factory::user()];
        $this->p = Factory::project($this->u['owner'], ['ai_enabled' => 1]);
        Factory::member($this->p, $this->u['viewer'], ProjectMember::ROLE_VIEWER);
    }

    private function post(FunctionalTester $I, string $route, array $params = []): void
    {
        $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), []);
    }

    private function pending(string $name): Concept
    {
        $c = new Concept(['project_id' => $this->p->id, 'name' => $name, 'is_input' => 1, 'source' => 'ai', 'ai_pending' => 1,
            'members' => json_encode(['ประเด็นที่มา'], JSON_UNESCAPED_UNICODE)]);
        $c->save(false);
        return $c;
    }

    public function buttonAndPendingFlow(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->u['owner']);
        $I->amOnRoute('concept/index', ['projectId' => $this->p->id]);
        $I->seeElement('.ai-concept-btn');

        $a = $this->pending('แนวคิดเอ');
        $b = $this->pending('แนวคิดบี');
        $c = $this->pending('แนวคิดซี');
        $I->amOnRoute('concept/index', ['projectId' => $this->p->id]);
        $I->dontSeeElement('.ai-concept-btn');            // มีข้อเสนอค้าง ซ่อนปุ่ม
        $I->see('AI เสนอ 3 แนวคิด');
        $I->see('ที่มา 1 ประเด็น: ประเด็นที่มา');

        // ยังไม่รับ → ไม่ขึ้นในรายงาน IPO
        $I->amOnRoute('report/ipo', ['projectId' => $this->p->id]);
        $I->dontSee('แนวคิดเอ');

        $this->post($I, 'concept/accept', ['id' => $a->id]);
        $I->seeRecord(Concept::class, ['id' => $a->id, 'ai_pending' => 0]);
        $this->post($I, 'concept/reject', ['id' => $b->id]);
        $I->dontSeeRecord(Concept::class, ['id' => $b->id]);   // soft-deleted
        $this->post($I, 'concept/accept-all', ['projectId' => $this->p->id]);
        $I->seeRecord(Concept::class, ['id' => $c->id, 'ai_pending' => 0]);
        $this->post($I, 'concept/accept', ['id' => $c->id]);
        $I->seeResponseCodeIs(404);
    }

    public function viewerCannotUseAi(FunctionalTester $I)
    {
        $a = $this->pending('แนวคิดเอ');
        $I->amLoggedInAs($this->u['viewer']);
        $I->amOnRoute('concept/index', ['projectId' => $this->p->id]);
        $I->dontSeeElement('.ai-concept-btn');
        $I->dontSee('AI เสนอ');
        $this->post($I, 'concept/ai', ['projectId' => $this->p->id]);
        $I->seeResponseCodeIs(403);
        $this->post($I, 'concept/accept', ['id' => $a->id]);
        $I->seeResponseCodeIs(403);
    }

    public function blockedWhenProjectDisallowsAi(FunctionalTester $I)
    {
        $this->p->updateAttributes(['ai_enabled' => 0]);
        $I->amLoggedInAs($this->u['owner']);
        $I->amOnRoute('concept/index', ['projectId' => $this->p->id]);
        $I->dontSeeElement('.ai-concept-btn');
        $this->post($I, 'concept/ai', ['projectId' => $this->p->id]);
        $I->seeResponseCodeIs(403);
    }
}
