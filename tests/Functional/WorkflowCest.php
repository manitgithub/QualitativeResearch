<?php

namespace tests\functional;

use app\models\Informant;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;

/**
 * ขั้นตอนการใช้งานจริงตั้งแต่สร้างโครงการจนถึงรายงาน
 */
class WorkflowCest
{
    public function _before(FunctionalTester $I)
    {
        Factory::reset();
    }

    private function post(FunctionalTester $I, string $route, array $params = [], array $body = []): void
    {
        $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), $body);
    }

    public function endToEnd(FunctionalTester $I)
    {
        $owner = Factory::user();
        $I->amLoggedInAs($owner);

        // 1. create project
        $I->amOnRoute('project/create');
        $I->submitForm('main form', ['Project' => ['title' => 'ประสบการณ์ผู้ดูแลผู้สูงอายุ', 'description' => 'ทดสอบ']]);
        $p = Project::findOne(['title' => 'ประสบการณ์ผู้ดูแลผู้สูงอายุ']);
        verify($p)->notNull();
        verify($p->owner_id)->equals($owner->id);

        // 2. questions of every type
        $this->post($I, 'question/create', ['projectId' => $p->id], ['Question' => ['category' => 'demographic', 'answer_type' => 'choice', 'title' => 'เพศ', 'optionsText' => "ชาย\nหญิง"]]);
        $this->post($I, 'question/create', ['projectId' => $p->id], ['Question' => ['category' => 'demographic', 'answer_type' => 'boolean', 'title' => 'เคยอบรม']]);
        $this->post($I, 'question/create', ['projectId' => $p->id], ['Question' => ['category' => 'research', 'answer_type' => 'text', 'title' => 'ความท้าทายในการดูแล']]);
        $qs = Question::find()->where(['project_id' => $p->id])->indexBy('title')->all();
        verify($qs)->arrayCount(3);
        $female = QuestionOption::findOne(['question_id' => $qs['เพศ']->id, 'label' => 'หญิง']);

        // choice question without options is rejected
        $this->post($I, 'question/create', ['projectId' => $p->id], ['Question' => ['category' => 'research', 'answer_type' => 'choice', 'title' => 'ไม่มีตัวเลือก']]);
        $I->dontSeeRecord(Question::class, ['title' => 'ไม่มีตัวเลือก']);

        // reorder
        $this->post($I, 'question/reorder', ['projectId' => $p->id], ['ids' => [$qs['เคยอบรม']->id, $qs['เพศ']->id]]);
        $I->seeRecord(Question::class, ['id' => $qs['เคยอบรม']->id, 'sort_order' => 1]);

        // 3. record an interview
        $I->amOnRoute('informant/create', ['projectId' => $p->id]);
        $I->see('ความท้าทายในการดูแล');
        $this->post($I, 'informant/create', ['projectId' => $p->id], [
            'Informant' => ['code' => 'CG01', 'age' => '52', 'interviewed_on' => '2026-11-09'],
            'answers' => [$qs['เพศ']->id => $female->id, $qs['เคยอบรม']->id => '1', $qs['ความท้าทายในการดูแล']->id => "เหนื่อยมาก <b>ไม่มีคนช่วย</b>"],
            'interpretations' => [$qs['ความท้าทายในการดูแล']->id => [['text' => 'ภาระการดูแล', 'ipo' => 'input']]],
        ]);
        $inf = Informant::findOne(['code' => 'CG01']);
        verify($inf)->notNull();
        $I->amOnRoute('informant/view', ['id' => $inf->id]);
        $I->see('หญิง');
        $I->see('ภาระการดูแล');
        $I->seeInSource('&lt;b&gt;ไม่มีคนช่วย&lt;/b&gt;');   // user text is HTML-escaped
        $I->dontSeeInSource('<b>ไม่มีคนช่วย</b>');

        // 4. analysis: add round-2 theme and a round-4 synthesis
        $resp = Response::findOne(['informant_id' => $inf->id, 'question_id' => $qs['ความท้าทายในการดูแล']->id]);
        $this->post($I, 'interpretation/create', [], ['Interpretation' => ['response_id' => $resp->id, 'round' => 2, 'text' => 'ขาดแรงสนับสนุน']]);
        $this->post($I, 'interpretation/create', [], ['Interpretation' => ['question_id' => $qs['ความท้าทายในการดูแล']->id, 'round' => 4, 'text' => 'ระบบสนับสนุนผู้ดูแล', 'ipo' => 'output']]);
        $I->amOnRoute('report/question', ['projectId' => $p->id, 'round' => 4]);
        $I->see('ขาดแรงสนับสนุน');
        $I->see('ระบบสนับสนุนผู้ดูแล');
        verify($I->grabPageSource())->stringContainsString('id="interp-form-tpl"');

        $I->amOnRoute('report/themes', ['projectId' => $p->id, 'round' => 1]);
        $I->see('ภาระการดูแล');
        $I->see('CG01');

        $I->amOnRoute('report/ipo', ['projectId' => $p->id, 'round' => 4]);
        $I->see('ระบบสนับสนุนผู้ดูแล');

        $I->amOnRoute('report/stats', ['projectId' => $p->id]);
        $I->see('เพศ');

        $I->sendAjaxGetRequest('/index-test.php?' . http_build_query(['r' => 'report/tree-data', 'projectId' => $p->id, 'mode' => 'themes']));
        $tree = json_decode($I->grabPageSource(), true);
        verify($tree['name'])->equals('ประสบการณ์ผู้ดูแลผู้สูงอายุ');
        verify($tree['children'][0]['children'][0]['name'])->equals('ภาระการดูแล');
        verify($tree['children'][0]['children'][0]['children'][0]['name'])->equals('CG01');

        // 5. edit + delete interpretation (soft delete)
        $it = Interpretation::findOne(['text' => 'ขาดแรงสนับสนุน']);
        $this->post($I, 'interpretation/update', ['id' => $it->id], ['Interpretation' => ['text' => 'ขาดแรงสนับสนุนจากครอบครัว', 'ipo' => 'process']]);
        $I->seeRecord(Interpretation::class, ['id' => $it->id, 'text' => 'ขาดแรงสนับสนุนจากครอบครัว', 'ipo' => 'process']);
        $this->post($I, 'interpretation/delete', ['id' => $it->id]);
        $I->seeRecord(Interpretation::class, ['id' => $it->id, 'is_deleted' => 1]);
        $I->amOnRoute('report/question', ['projectId' => $p->id, 'round' => 2]);
        $I->dontSee('ขาดแรงสนับสนุนจากครอบครัว');

        // 6. exports are real xlsx files
        foreach ([
            ['report/question', ['questionId' => 'all', 'round' => 4, 'export' => 1]],
            ['report/themes', ['export' => 1]],
            ['report/informant', ['export' => 1]],
            ['informant/template', []],
        ] as [$route, $params]) {
            $I->amOnRoute($route, ['projectId' => $p->id] + $params);
            $I->seeResponseCodeIs(200);
            verify(substr($I->grabPageSource(), 0, 2))->equals('PK');   // xlsx = zip archive
        }

        // 7. archive hides from home and blocks edits
        $this->post($I, 'project/archive', ['id' => $p->id]);
        $I->amOnRoute('site/index');
        $I->dontSee('ประสบการณ์ผู้ดูแลผู้สูงอายุ');
        $I->amOnRoute('site/index', ['archived' => 1]);
        $I->see('ประสบการณ์ผู้ดูแลผู้สูงอายุ');
        $this->post($I, 'project/restore', ['id' => $p->id]);
        $I->seeRecord(Project::class, ['id' => $p->id, 'is_archived' => 0]);
    }

    public function membersAndGroups(FunctionalTester $I)
    {
        $owner = Factory::user();
        $colleague = Factory::user(['first_name' => 'เพื่อนร่วมทีม']);
        $p = Factory::project($owner);
        $I->amLoggedInAs($owner);

        $this->post($I, 'project/members', ['id' => $p->id], ['ProjectMember' => ['user_id' => $colleague->id, 'role' => 'viewer']]);
        $I->seeRecord(\app\models\ProjectMember::class, ['project_id' => $p->id, 'user_id' => $colleague->id, 'role' => 'viewer']);

        $this->post($I, 'project/members', ['id' => $p->id], ['roles' => [$colleague->id => 'member', $owner->id => 'viewer']]);
        $I->seeRecord(\app\models\ProjectMember::class, ['user_id' => $colleague->id, 'role' => 'member']);
        $I->seeRecord(\app\models\ProjectMember::class, ['user_id' => $owner->id, 'role' => 'owner']);   // owner unchanged

        $this->post($I, 'project/remove-member', ['id' => $p->id, 'userId' => $owner->id]);
        $I->seeResponseCodeIs(400);

        $this->post($I, 'project/members', ['id' => $p->id], ['InformantGroup' => ['name' => 'ผู้ป่วย']]);
        $I->seeRecord(\app\models\InformantGroup::class, ['project_id' => $p->id, 'name' => 'ผู้ป่วย']);
    }

    public function conceptMap(FunctionalTester $I)
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $I->amLoggedInAs($owner);
        $this->post($I, 'concept/index', ['projectId' => $p->id], ['Concept' => ['name' => 'ภาวะผู้นำ', 'is_input' => 1, 'is_success' => 1]]);
        $c = \app\models\Concept::findOne(['name' => 'ภาวะผู้นำ']);
        verify((int)$c->is_input)->equals(1);
        // unchecked boxes are cleared on update
        $this->post($I, 'concept/update', ['id' => $c->id], ['Concept' => ['name' => 'ภาวะผู้นำ', 'is_output' => 1]]);
        $c->refresh();
        verify([(int)$c->is_input, (int)$c->is_output, (int)$c->is_success])->equals([0, 1, 0]);
        $I->amOnRoute('report/ipo', ['projectId' => $p->id]);
        $I->see('ภาวะผู้นำ');
    }

    public function largeProjectPagesPaginate(FunctionalTester $I)
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $qs = [];
        for ($i = 1; $i <= 7; $i++) {
            $qs[] = Factory::question($p, ['title' => "คำถาม {$i}", 'sort_order' => $i]);
        }
        $rows = [];
        $now = time();
        for ($i = 1; $i <= 250; $i++) {
            $rows[] = [$p->id, sprintf('P%03d', $i), 0, $now, $now];
        }
        \Yii::$app->db->createCommand()->batchInsert('informant', ['project_id', 'code', 'is_deleted', 'created_at', 'updated_at'], $rows)->execute();

        $I->amLoggedInAs($owner);
        $I->amOnRoute('report/informant', ['projectId' => $p->id]);
        $I->see('250 คน');
        $I->see('P100');
        $I->dontSee('P101');
        $I->seeElement('.pagination');

        $I->amOnRoute('report/question', ['projectId' => $p->id, 'questionId' => 'all']);
        $I->see('คำถาม 3', '.card-header');
        $I->dontSee('คำถาม 4', '.card-header');
        $I->see('หน้าละ 3 คำถาม');
    }
}
