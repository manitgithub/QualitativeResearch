<?php

namespace tests\unit\models;

use app\models\Project;
use app\models\ProjectMember;
use app\tests\Support\Factory;

class ProjectAccessTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
    }

    public function testOwnerBecomesMemberAutomatically()
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $m = ProjectMember::findOne(['project_id' => $p->id, 'user_id' => $owner->id]);
        verify($m)->notNull();
        verify($m->role)->equals(ProjectMember::ROLE_OWNER);
    }

    public function testRoleMatrix()
    {
        $owner = Factory::user();
        $member = Factory::user();
        $viewer = Factory::user();
        $stranger = Factory::user();
        $admin = Factory::admin();
        $p = Factory::project($owner);
        Factory::member($p, $member, ProjectMember::ROLE_MEMBER);
        Factory::member($p, $viewer, ProjectMember::ROLE_VIEWER);

        $cases = [
            //            view   edit   manage
            'owner' => [$owner, true, true, true],
            'admin' => [$admin, true, true, true],
            'member' => [$member, true, true, false],
            'viewer' => [$viewer, true, false, false],
            'stranger' => [$stranger, false, false, false],
            'guest' => [null, false, false, false],
        ];
        foreach ($cases as $name => [$u, $view, $edit, $manage]) {
            verify($p->canView($u))->equals($view, "$name view");
            verify($p->canEdit($u))->equals($edit, "$name edit");
            verify($p->canManage($u))->equals($manage, "$name manage");
        }
    }

    public function testVisibleProjectsQuery()
    {
        $a = Factory::user();
        $b = Factory::user();
        $admin = Factory::admin();
        $pa = Factory::project($a, ['title' => 'A']);
        $pb = Factory::project($b, ['title' => 'B']);
        Factory::member($pb, $a);

        $titles = fn($u) => Project::findVisibleTo($u)->select('title')->orderBy('title')->column();
        verify($titles($a))->equals(['A', 'B']);
        verify($titles($b))->equals(['B']);
        verify($titles($admin))->equals(['A', 'B']);
    }
}
