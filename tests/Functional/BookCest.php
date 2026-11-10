<?php

namespace tests\functional;

use app\models\Interpretation;
use app\models\ProjectMember;
use app\models\ReportSection;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * เล่มรายงาน: ดู / แก้ไข / ส่งออก และสิทธิ์
 */
class BookCest
{
    private array $u = [];
    private $p;
    private $q;

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
        Yii::$app->params['openrouter.apiKey'] = 'x';
        $this->u = ['owner' => Factory::user(), 'viewer' => Factory::user(), 'stranger' => Factory::user()];
        $this->p = Factory::project($this->u['owner'], ['title' => 'โครงการเล่ม']);
        Factory::member($this->p, $this->u['viewer'], ProjectMember::ROLE_VIEWER);
        $this->q = Factory::question($this->p, ['title' => 'อุปสรรคการดูแล']);
        (new Interpretation(['question_id' => $this->q->id, 'round' => 4, 'text' => 'ข้อจำกัดด้านเวลา', 'ipo' => 'input']))->save(false);
    }

    private function post(FunctionalTester $I, string $route, array $params = [], array $body = []): void
    {
        $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), $body);
    }

    public function ownerWritesAndExports(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->u['owner']);
        $I->amOnRoute('book/index', ['projectId' => $this->p->id]);
        $I->see('เล่มรายงาน / บทวิเคราะห์', 'h1');
        $I->see('ข้อจำกัดด้านเวลา', '.book-table');
        $I->seeElement('textarea[name=content]');
        $I->dontSeeElement('.ai-write-btn'); // ยังไม่เปิด AI ในโครงการ

        $this->post($I, 'book/save', ['projectId' => $this->p->id, 'questionId' => $this->q->id], ['content' => "ย่อหน้าแรก\n\nย่อหน้าที่สอง"]);
        $I->seeRecord(ReportSection::class, ['question_id' => $this->q->id, 'source' => 'human', 'flagDel' => 0]);
        $this->post($I, 'book/save', ['projectId' => $this->p->id, 'questionId' => 'summary'], ['content' => 'สรุปรวม']);
        $I->seeRecord(ReportSection::class, ['project_id' => $this->p->id, 'question_id' => null, 'content' => 'สรุปรวม']);

        $I->amOnRoute('book/export', ['projectId' => $this->p->id, 'format' => 'print']);
        $I->see('บทที่ 4');
        $I->see('4.1 อุปสรรคการดูแล');
        $I->see('ย่อหน้าที่สอง', 'p.book-p');
        $I->see('บทที่ 5');

        $I->amOnRoute('book/export', ['projectId' => $this->p->id, 'format' => 'doc']);
        $I->seeInSource('urn:schemas-microsoft-com:office:word');
        $I->see('ย่อหน้าแรก');

        // ล้างข้อความ = ซ่อนเนื้อหา
        $this->post($I, 'book/save', ['projectId' => $this->p->id, 'questionId' => $this->q->id], ['content' => '  ']);
        $I->dontSeeRecord(ReportSection::class, ['question_id' => $this->q->id, 'flagDel' => 0]);
    }

    public function aiButtonOnlyWhenProjectAllows(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->u['owner']);
        $this->post($I, 'book/generate', ['projectId' => $this->p->id, 'questionId' => $this->q->id]);
        $I->seeResponseCodeIs(403);
        $this->p->updateAttributes(['ai_enabled' => 1]);
        $I->amOnRoute('book/index', ['projectId' => $this->p->id]);
        $I->seeElement('.ai-write-btn');
    }

    public function viewerReadsOnlyStrangerBlocked(FunctionalTester $I)
    {
        (new ReportSection(['project_id' => $this->p->id, 'question_id' => $this->q->id, 'content' => 'เนื้อหาเดิม', 'source' => 'human']))->save(false);
        $this->p->updateAttributes(['ai_enabled' => 1]);

        $I->amLoggedInAs($this->u['viewer']);
        $I->amOnRoute('book/index', ['projectId' => $this->p->id]);
        $I->see('เนื้อหาเดิม');
        $I->dontSeeElement('textarea[name=content]');
        $I->dontSeeElement('.ai-write-btn');
        $this->post($I, 'book/save', ['projectId' => $this->p->id, 'questionId' => $this->q->id], ['content' => 'แก้']);
        $I->seeResponseCodeIs(403);
        $this->post($I, 'book/generate', ['projectId' => $this->p->id, 'questionId' => 'summary']);
        $I->seeResponseCodeIs(403);
        $I->amOnRoute('book/export', ['projectId' => $this->p->id, 'format' => 'print']);
        $I->seeResponseCodeIs(200);

        $I->amLoggedInAs($this->u['stranger']);
        $I->amOnRoute('book/index', ['projectId' => $this->p->id]);
        $I->seeResponseCodeIs(403);
        $I->amOnRoute('book/export', ['projectId' => $this->p->id]);
        $I->seeResponseCodeIs(403);
    }
}
