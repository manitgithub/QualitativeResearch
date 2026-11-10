<?php

namespace tests\functional;

use app\models\Concept;
use app\models\InformantGroup;
use app\models\ProjectMember;
use app\models\User;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * ปุ่มลบในหน้าเว็บทุกจุด → ตั้ง flagDel = 1 แถวยังอยู่ในฐานข้อมูล
 */
class SoftDeleteCest
{
    private function flag(string $table, array $where): ?int
    {
        $v = (new \yii\db\Query())->select('flagDel')->from($table)->where($where)->scalar();
        return $v === false ? null : (int)$v;
    }

    private function post(FunctionalTester $I, string $route, array $params = [], array $body = []): void
    {
        $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), $body);
    }

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
    }

    public function everyDeleteButtonFlags(FunctionalTester $I)
    {
        $owner = Factory::user();
        $admin = Factory::admin();
        $mate = Factory::user();
        $p = Factory::project($owner);
        Factory::member($p, $mate);
        $q = Factory::question($p);
        $inf = Factory::informant($p);
        $r = Factory::response($inf, $q);
        $it = new \app\models\Interpretation(['question_id' => $q->id, 'response_id' => $r->id, 'round' => 1, 'text' => 'x']);
        $it->save(false);
        $g = new InformantGroup(['project_id' => $p->id, 'name' => 'g']);
        $g->save();
        $c = new Concept(['project_id' => $p->id, 'name' => 'c']);
        $c->save();

        $I->amLoggedInAs($owner);
        $this->post($I, 'interpretation/delete', ['id' => $it->id]);
        $this->post($I, 'informant/delete', ['id' => $inf->id]);
        $this->post($I, 'question/delete', ['id' => $q->id]);
        $this->post($I, 'concept/delete', ['id' => $c->id]);
        $this->post($I, 'project/delete-group', ['id' => $p->id, 'groupId' => $g->id]);
        $this->post($I, 'project/remove-member', ['id' => $p->id, 'userId' => $mate->id]);

        verify($this->flag('interpretation', ['id' => $it->id]))->equals(1);
        verify($this->flag('informant', ['id' => $inf->id]))->equals(1);
        verify($this->flag('question', ['id' => $q->id]))->equals(1);
        verify($this->flag('concept', ['id' => $c->id]))->equals(1);
        verify($this->flag('informant_group', ['id' => $g->id]))->equals(1);
        verify($this->flag('project_member', ['project_id' => $p->id, 'user_id' => $mate->id]))->equals(1);

        // hidden from the pages
        $I->amOnRoute('informant/index', ['projectId' => $p->id]);
        $I->dontSee('P01', 'td');
        $I->amOnRoute('informant/view', ['id' => $inf->id]);
        $I->seeResponseCodeIs(404);
        $I->amOnRoute('project/members', ['id' => $p->id]);
        $I->dontSee($mate->getFullName(), 'table');

        // re-adding the removed member revives the same row
        $this->post($I, 'project/members', ['id' => $p->id], ['ProjectMember' => ['user_id' => $mate->id, 'role' => 'viewer']]);
        verify($this->flag('project_member', ['project_id' => $p->id, 'user_id' => $mate->id]))->equals(0);
        $I->seeRecord(ProjectMember::class, ['project_id' => $p->id, 'user_id' => $mate->id, 'role' => 'viewer']);
        verify($p->canView(User::findOne($mate->id)))->true();
    }

    public function userDeletionsFlag(FunctionalTester $I)
    {
        $admin = Factory::admin();
        $pending = Factory::user(['status' => User::STATUS_PENDING]);
        (new \app\models\UserAuth(['user_id' => $pending->id, 'source' => 'line', 'source_id' => 'U-p']))->save(false);
        $linked = Factory::user();
        (new \app\models\UserAuth(['user_id' => $linked->id, 'source' => 'google', 'source_id' => 'g-l']))->save(false);

        $I->amLoggedInAs($admin);
        $this->post($I, 'user/reject', ['id' => $pending->id]);
        $this->post($I, 'user/unlink', ['id' => $linked->id, 'source' => 'google']);

        verify($this->flag('user', ['id' => $pending->id]))->equals(1);
        verify($this->flag('user_auth', ['source_id' => 'U-p']))->equals(1);
        verify($this->flag('user_auth', ['source_id' => 'g-l']))->equals(1);
        $I->amOnRoute('user/index');
        $I->dontSee('รออนุมัติ', '.card-header');
    }

    public function removedMemberLosesAccess(FunctionalTester $I)
    {
        $owner = Factory::user();
        $mate = Factory::user();
        $p = Factory::project($owner);
        Factory::member($p, $mate);
        ProjectMember::findOne(['project_id' => $p->id, 'user_id' => $mate->id])->delete();
        $I->amLoggedInAs($mate);
        $I->amOnRoute('project/view', ['id' => $p->id]);
        $I->seeResponseCodeIs(403);
    }
}
