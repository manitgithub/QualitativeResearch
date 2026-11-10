<?php

namespace tests\functional;

use app\components\ai\Credits;
use app\models\AiJob;
use app\models\AiSuggestion;
use app\models\Interpretation;
use app\models\ProjectMember;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * หน้าจอ AI: รับ/ปฏิเสธข้อเสนอ, สิทธิ์, การเปิดใช้ระดับโครงการ, เติมเครดิตโดยผู้ดูแล
 */
class AiCest
{
    private array $u = [];
    private $p;
    private $q;
    private $resp;

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
        Yii::$app->params['openrouter.apiKey'] = 'x';
        $this->u = [
            'owner' => Factory::user(),
            'member' => Factory::user(),
            'viewer' => Factory::user(),
            'stranger' => Factory::user(),
            'admin' => Factory::admin(),
        ];
        $this->p = Factory::project($this->u['owner']);
        Factory::member($this->p, $this->u['member'], ProjectMember::ROLE_MEMBER);
        Factory::member($this->p, $this->u['viewer'], ProjectMember::ROLE_VIEWER);
        $this->q = Factory::question($this->p);
        $this->resp = Factory::response(Factory::informant($this->p), $this->q, 'ไม่มีเวลา');
    }

    private function post(FunctionalTester $I, string $route, array $params = [], array $body = []): void
    {
        $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), $body);
    }

    private function suggestion(string $text = 'ขาดเวลา'): AiSuggestion
    {
        $job = new AiJob(['user_id' => $this->u['member']->id, 'project_id' => $this->p->id, 'feature' => AiJob::FEATURE_SUGGEST_INTERPRETATION,
            'model' => 'm', 'status' => AiJob::STATUS_DONE, 'created_at' => time()]);
        $job->save(false);
        $s = new AiSuggestion(['ai_job_id' => $job->id, 'question_id' => $this->q->id, 'response_id' => $this->resp->id,
            'round' => 1, 'text' => $text, 'ipo' => 'input', 'status' => AiSuggestion::STATUS_PENDING, 'created_at' => time()]);
        $s->save(false);
        return $s;
    }

    public function pendingSuggestionShownAndAccepted(FunctionalTester $I)
    {
        $s = $this->suggestion();
        $I->amLoggedInAs($this->u['member']);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id]);
        $I->see('ขาดเวลา', '.ai-suggestion');
        $I->see('AI เสนอ 1 ประเด็น');

        $this->post($I, 'ai/accept', ['id' => $s->id]);
        $I->seeRecord(Interpretation::class, ['response_id' => $this->resp->id, 'round' => 1, 'text' => 'ขาดเวลา', 'ipo' => 'input', 'source' => 'ai']);
        $I->seeRecord(AiSuggestion::class, ['id' => $s->id, 'status' => 'accepted', 'decided_by' => $this->u['member']->id]);

        // ตัดสินใจไปแล้วกดซ้ำไม่ได้
        $this->post($I, 'ai/accept', ['id' => $s->id]);
        $I->seeResponseCodeIs(404);
        verify(Interpretation::find()->where(['response_id' => $this->resp->id])->count())->equals(1);
    }

    public function rejectAndRejectAll(FunctionalTester $I)
    {
        $a = $this->suggestion('ก');
        $b = $this->suggestion('ข');
        $c = $this->suggestion('ค');
        $I->amLoggedInAs($this->u['owner']);
        $this->post($I, 'ai/reject', ['id' => $a->id]);
        $I->seeRecord(AiSuggestion::class, ['id' => $a->id, 'status' => 'rejected']);
        $this->post($I, 'ai/accept-all', ['questionId' => $this->q->id, 'round' => 1]);
        $I->seeRecord(AiSuggestion::class, ['id' => $b->id, 'status' => 'accepted']);
        $I->seeRecord(AiSuggestion::class, ['id' => $c->id, 'status' => 'accepted']);
        $I->dontSeeRecord(Interpretation::class, ['text' => 'ก']);
    }

    public function synthesisGroupShownAndAccepted(FunctionalTester $I)
    {
        $job = new AiJob(['user_id' => $this->u['member']->id, 'project_id' => $this->p->id, 'feature' => AiJob::FEATURE_SYNTHESIZE_IPO,
            'model' => 'm', 'status' => AiJob::STATUS_DONE, 'created_at' => time()]);
        $job->save(false);
        $s = new AiSuggestion(['ai_job_id' => $job->id, 'question_id' => $this->q->id, 'response_id' => null, 'round' => 4,
            'text' => 'ข้อจำกัดด้านเวลาของผู้ดูแล', 'ipo' => 'input', 'members' => json_encode(['ไม่มีเวลา', 'ต้องทำงาน'], JSON_UNESCAPED_UNICODE),
            'status' => AiSuggestion::STATUS_PENDING, 'created_at' => time()]);
        $s->save(false);
        $this->p->updateAttributes(['ai_enabled' => 1]);

        $I->amLoggedInAs($this->u['member']);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id, 'round' => 4]);
        $I->see('ข้อจำกัดด้านเวลาของผู้ดูแล', '.ai-suggestion');
        $I->see('รวมจาก 2 ประเด็น: ไม่มีเวลา · ต้องทำงาน');
        $I->see('AI เสนอ 1 ประเด็น');
        $I->dontSeeElement('.ai-synth-btn'); // มีข้อเสนอค้าง ปุ่มสั่งใหม่ถูกซ่อน

        $this->post($I, 'ai/accept', ['id' => $s->id]);
        $I->seeRecord(Interpretation::class, ['question_id' => $this->q->id, 'response_id' => null, 'round' => 4,
            'text' => 'ข้อจำกัดด้านเวลาของผู้ดูแล', 'ipo' => 'input', 'source' => 'ai']);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id, 'round' => 4]);
        $I->seeElement('.ai-synth-btn');
    }

    public function viewerAndStrangerCannotDecide(FunctionalTester $I)
    {
        $s = $this->suggestion();
        foreach (['viewer', 'stranger'] as $who) {
            $I->amLoggedInAs($this->u[$who]);
            $this->post($I, 'ai/accept', ['id' => $s->id]);
            $I->seeResponseCodeIs(403);
            $this->post($I, 'ai/suggest', ['questionId' => $this->q->id, 'round' => 1]);
            $I->seeResponseCodeIs(403);
        }
        $I->amLoggedInAs($this->u['viewer']);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id]);
        $I->dontSeeElement('.ai-suggestion');
        $I->seeRecord(AiSuggestion::class, ['id' => $s->id, 'status' => 'pending']);
    }

    public function suggestBlockedUntilProjectEnablesAi(FunctionalTester $I)
    {
        Credits::grant($this->u['member']->id, 100, null, null);
        $I->amLoggedInAs($this->u['member']);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id]);
        $I->dontSeeElement('.ai-suggest-btn');
        $this->post($I, 'ai/suggest', ['questionId' => $this->q->id, 'round' => 1]);
        $I->seeResponseCodeIs(403);
        verify(AiJob::find()->count())->equals(0);

        $this->p->updateAttributes(['ai_enabled' => 1]);
        $I->amOnRoute('report/question', ['projectId' => $this->p->id, 'questionId' => $this->q->id]);
        $I->seeElement('.ai-suggest-btn');
    }

    public function onlyManagerCanEnableAi(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->u['member']);
        $this->post($I, 'project/update', ['id' => $this->p->id], ['Project' => ['title' => 'x', 'ai_enabled' => 1]]);
        $I->seeResponseCodeIs(403);
        $I->seeRecord(\app\models\Project::class, ['id' => $this->p->id, 'ai_enabled' => 0]);

        $I->amLoggedInAs($this->u['owner']);
        $this->post($I, 'project/update', ['id' => $this->p->id], ['Project' => ['title' => 'x', 'ai_enabled' => 1]]);
        $I->seeRecord(\app\models\Project::class, ['id' => $this->p->id, 'ai_enabled' => 1]);
    }

    public function welcomeCreditsWhenNewUserActivated(FunctionalTester $I)
    {
        Yii::$app->params['ai.welcomeCredits'] = 5;
        $I->amLoggedInAs($this->u['admin']);

        // อนุมัติเป็นผู้ใช้ใหม่ → ได้ 5 เครดิต (อนุมัติซ้ำก็ไม่ได้เพิ่ม)
        $pending = Factory::user(['status' => \app\models\User::STATUS_PENDING]);
        $this->post($I, 'user/approve', ['id' => $pending->id]);
        verify(Credits::balance($pending->id))->equals(5);

        // รวมเข้าบัญชีเดิม → บัญชีเดิมไม่ได้เครดิตต้อนรับ
        $pending2 = Factory::user(['status' => \app\models\User::STATUS_PENDING]);
        $this->post($I, 'user/approve', ['id' => $pending2->id], ['mergeInto' => $this->u['member']->id]);
        verify(Credits::balance($this->u['member']->id))->equals(0);

        // ผู้ดูแลเพิ่มผู้ใช้ใหม่ที่ใช้งานได้ทันที → ได้ 5 เครดิต
        $this->post($I, 'user/create', [], ['User' => ['first_name' => 'ใหม่', 'email' => 'new@example.test', 'role' => 'user', 'status' => '10']]);
        $new = \app\models\User::findOne(['email' => 'new@example.test']);
        verify($new)->notNull();
        verify(Credits::balance($new->id))->equals(5);
    }

    public function adminGrantsCredits(FunctionalTester $I)
    {
        $target = $this->u['member'];
        $I->amLoggedInAs($this->u['owner']);
        $this->post($I, 'user/credit', ['id' => $target->id], ['amount' => 500, 'note' => 'x']);
        $I->seeResponseCodeIs(403);
        verify(Credits::balance($target->id))->equals(0);

        $I->amLoggedInAs($this->u['admin']);
        $this->post($I, 'user/credit', ['id' => $target->id], ['amount' => 500, 'note' => 'โอน 50 บาท']);
        verify(Credits::balance($target->id))->equals(500);
        $this->post($I, 'user/credit', ['id' => $target->id], ['amount' => -600]);
        verify(Credits::balance($target->id))->equals(500);

        $I->amLoggedInAs($target);
        $I->amOnRoute('profile/index');
        $I->see('500', '#credits');
        $I->see('โอน 50 บาท', '#credits');
    }
}
