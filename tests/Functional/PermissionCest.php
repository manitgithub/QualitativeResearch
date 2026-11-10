<?php

namespace tests\functional;

use app\models\Interpretation;
use app\models\ProjectMember;
use app\models\Question;
use app\models\User;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;

/**
 * ตรวจสิทธิ์ทุก route ตามบทบาท: owner / member / viewer / คนนอก / admin
 */
class PermissionCest
{
    private array $u = [];
    private $p;
    private $q;
    private $inf;
    private $resp;
    private $it;

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
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
        $this->inf = Factory::informant($this->p);
        $this->resp = Factory::response($this->inf, $this->q);
        $this->it = new Interpretation(['question_id' => $this->q->id, 'response_id' => $this->resp->id, 'round' => 1, 'text' => 'x']);
        $this->it->save(false);
    }

    /** GET routes and the minimum role that may open them */
    private function getRoutes(): array
    {
        $pid = $this->p->id;
        return [
            'view' => [
                ['project/view', ['id' => $pid]],
                ['question/index', ['projectId' => $pid]],
                ['informant/index', ['projectId' => $pid]],
                ['informant/view', ['id' => $this->inf->id]],
                ['report/question', ['projectId' => $pid]],
                ['report/themes', ['projectId' => $pid]],
                ['report/informant', ['projectId' => $pid]],
                ['report/stats', ['projectId' => $pid]],
                ['report/mindmap', ['projectId' => $pid]],
                ['report/tree-data', ['projectId' => $pid]],
                ['report/wordcloud', ['projectId' => $pid]],
                ['report/ipo', ['projectId' => $pid]],
                ['concept/index', ['projectId' => $pid]],
                ['informant/template', ['projectId' => $pid]],
                ['report/question', ['projectId' => $pid, 'export' => 1]],
            ],
            'edit' => [
                ['informant/create', ['projectId' => $pid]],
                ['informant/update', ['id' => $this->inf->id]],
                ['informant/import', ['projectId' => $pid]],
                ['question/create', ['projectId' => $pid]],
                ['question/update', ['id' => $this->q->id]],
            ],
            'manage' => [
                ['project/update', ['id' => $pid]],
                ['project/members', ['id' => $pid]],
            ],
        ];
    }

    private function allowed(string $role, string $level): bool
    {
        return match ($level) {
            'view' => $role !== 'stranger',
            'edit' => in_array($role, ['owner', 'member', 'admin'], true),
            'manage' => in_array($role, ['owner', 'admin'], true),
        };
    }

    public function routeMatrix(FunctionalTester $I)
    {
        foreach ($this->u as $role => $user) {
            $I->amLoggedInAs($user);
            foreach ($this->getRoutes() as $level => $routes) {
                foreach ($routes as [$route, $params]) {
                    $I->amOnRoute($route, $params);
                    $I->seeResponseCodeIs($this->allowed($role, $level) ? 200 : 403);
                }
            }
            $I->amOnRoute('user/index');
            $I->seeResponseCodeIs($role === 'admin' ? 200 : 403);
            $I->amOnRoute('site/logout');
            \Yii::$app->user->logout(false);
        }
    }

    public function writeActionsRespectRoles(FunctionalTester $I)
    {
        $writes = fn() => [
            'add interpretation' => ['interpretation/create', [], ['Interpretation' => ['response_id' => $this->resp->id, 'round' => 2, 'text' => 'new']]],
            'edit interpretation' => ['interpretation/update', ['id' => $this->it->id], ['Interpretation' => ['text' => 'edited']]],
            'delete question' => ['question/delete', ['id' => Factory::question($this->p)->id], []],
            'add member' => ['project/members', ['id' => $this->p->id], ['ProjectMember' => ['user_id' => Factory::user()->id, 'role' => 'member']]],
        ];
        // true = allowed (success redirect/page), false = 403
        foreach (['viewer' => [false, false, false, false], 'stranger' => [false, false, false, false],
                     'member' => [true, true, true, false], 'owner' => [true, true, true, true]] as $role => $expect) {
            $I->amLoggedInAs($this->u[$role]);
            $i = 0;
            foreach ($writes() as $name => [$route, $params, $body]) {
                $I->sendAjaxPostRequest('/index-test.php?' . http_build_query(['r' => $route] + $params), $body);
                if ($expect[$i++]) {
                    $I->seeResponseCodeIsBetween(200, 399);
                } else {
                    $I->seeResponseCodeIs(403);
                }
            }
            \Yii::$app->user->logout(false);
        }
        // viewer/stranger writes left no trace
        $I->dontSeeRecord(Interpretation::class, ['text' => 'new', 'created_by' => $this->u['viewer']->id]);
        $I->dontSeeRecord(Interpretation::class, ['text' => 'new', 'created_by' => $this->u['stranger']->id]);
    }

    public function deleteRequiresPost(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->u['owner']);
        $I->amOnRoute('question/delete', ['id' => $this->q->id]);
        $I->seeResponseCodeIs(405);
        $I->seeRecord(Question::class, ['id' => $this->q->id, 'flagDel' => 0]);
    }

    public function archivedProjectIsReadOnly(FunctionalTester $I)
    {
        $this->p->updateAttributes(['is_archived' => 1]);
        $I->amLoggedInAs($this->u['member']);
        $I->amOnRoute('project/view', ['id' => $this->p->id]);
        $I->seeResponseCodeIs(200);
        $I->amOnRoute('informant/create', ['projectId' => $this->p->id]);
        $I->seeResponseCodeIs(403);
    }

    public function adminCannotDemoteSelf(FunctionalTester $I)
    {
        $admin = $this->u['admin'];
        $I->amLoggedInAs($admin);
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Fupdate&id=' . $admin->id, ['User' => ['first_name' => 'A', 'role' => 'user', 'status' => 0]]);
        $I->seeRecord(User::class, ['id' => $admin->id, 'role' => 'admin', 'status' => 10]);
    }

    public function adminApprovesPendingUser(FunctionalTester $I)
    {
        $pending = Factory::user(['status' => User::STATUS_PENDING, 'first_name' => 'ผู้ขอใหม่']);
        $I->amLoggedInAs($this->u['admin']);
        $I->amOnRoute('user/index');
        $I->see('รออนุมัติ');
        $I->see('ผู้ขอใหม่');
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Fapprove&id=' . $pending->id, ['mergeInto' => '']);
        $I->seeRecord(User::class, ['id' => $pending->id, 'status' => User::STATUS_ACTIVE]);
    }

    public function adminMergesPendingIntoLegacyAccount(FunctionalTester $I)
    {
        $legacy = Factory::user(['username' => 'legacy-owner', 'legacy_id' => 99]);
        $proj = Factory::project($legacy, ['title' => 'โครงการเดิม']);
        $pending = Factory::user(['status' => User::STATUS_PENDING, 'username' => null, 'email' => 'owner@gmail.test']);
        (new \app\models\UserAuth(['user_id' => $pending->id, 'source' => 'line', 'source_id' => 'U-legacy']))->save(false);

        $I->amLoggedInAs($this->u['admin']);
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Fapprove&id=' . $pending->id, ['mergeInto' => $legacy->id]);
        $I->dontSeeRecord(User::class, ['id' => $pending->id]);
        $I->seeRecord(\app\models\UserAuth::class, ['source_id' => 'U-legacy', 'user_id' => $legacy->id]);
        $I->seeRecord(User::class, ['id' => $legacy->id, 'email' => 'owner@gmail.test', 'status' => User::STATUS_ACTIVE]);

        // the person now logs in as the legacy account and sees their old project
        \Yii::$app->user->logout(false);
        $I->amLoggedInAs(User::findOne($legacy->id));
        $I->amOnRoute('site/index');
        $I->see('โครงการเดิม');
    }

    public function adminRejectsPending(FunctionalTester $I)
    {
        $pending = Factory::user(['status' => User::STATUS_PENDING]);
        $active = Factory::user();
        $I->amLoggedInAs($this->u['admin']);
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Freject&id=' . $pending->id);
        $I->dontSeeRecord(User::class, ['id' => $pending->id]);
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Freject&id=' . $active->id);
        $I->seeResponseCodeIs(400);
        $I->seeRecord(User::class, ['id' => $active->id]);
    }

    public function nonAdminCannotApprove(FunctionalTester $I)
    {
        $pending = Factory::user(['status' => User::STATUS_PENDING]);
        $I->amLoggedInAs($this->u['owner']);
        $I->sendAjaxPostRequest('/index-test.php?r=user%2Fapprove&id=' . $pending->id, ['mergeInto' => $this->u['owner']->id]);
        $I->seeResponseCodeIs(403);
        $I->seeRecord(User::class, ['id' => $pending->id, 'status' => User::STATUS_PENDING]);
    }
}
