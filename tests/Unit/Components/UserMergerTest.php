<?php

namespace tests\unit\components;

use app\components\UserMerger;
use app\models\LoginLog;
use app\models\Project;
use app\models\ProjectMember;
use app\models\User;
use app\models\UserAuth;
use app\tests\Support\Factory;
use yii\base\UserException;

class UserMergerTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
    }

    private function pendingWithGoogle(array $attrs = []): User
    {
        $u = Factory::user(array_merge(['status' => User::STATUS_PENDING, 'username' => null, 'email' => 'new@gmail.test',
            'avatar_url' => 'https://example.test/a.png'], $attrs));
        (new UserAuth(['user_id' => $u->id, 'source' => 'google', 'source_id' => 'g-1', 'email' => 'new@gmail.test']))->save(false);
        (new LoginLog(['user_id' => $u->id, 'method' => 'google', 'created_at' => time()]))->save(false);
        return $u;
    }

    public function testMergeMovesLoginAndActivatesLegacyAccount()
    {
        $legacy = Factory::user(['username' => 'manit', 'first_name' => 'มานิต', 'status' => User::STATUS_ACTIVE, 'legacy_id' => 1]);
        $project = Factory::project($legacy);
        $pending = $this->pendingWithGoogle();

        $merged = (new UserMerger())->merge($pending, $legacy);

        verify($merged->id)->equals($legacy->id);
        verify(User::findOne($pending->id))->null();
        verify($merged->status)->equals(User::STATUS_ACTIVE);
        verify($merged->email)->equals('new@gmail.test');            // copied: legacy had none
        verify($merged->avatar_url)->equals('https://example.test/a.png');
        verify(UserAuth::findOne(['source_id' => 'g-1'])->user_id)->equals($legacy->id);
        verify(LoginLog::find()->where(['user_id' => $legacy->id])->count())->equals(1);
        verify(Project::findOne($project->id)->owner_id)->equals($legacy->id);
    }

    public function testExistingEmailIsKept()
    {
        $legacy = Factory::user(['email' => 'keep@example.test']);
        $merged = (new UserMerger())->merge($this->pendingWithGoogle(), $legacy);
        verify($merged->email)->equals('keep@example.test');
    }

    public function testMembershipsAndCreatedDataAreMovedWithoutDuplicates()
    {
        $legacy = Factory::user();
        $other = Factory::user();
        $p1 = Factory::project($other);
        $p2 = Factory::project($other);
        $pending = $this->pendingWithGoogle();
        Factory::member($p1, $legacy);
        Factory::member($p1, $pending);   // both members of p1 -> must not duplicate
        Factory::member($p2, $pending);
        $q = Factory::question($p1, ['created_by' => $pending->id]);

        (new UserMerger())->merge($pending, $legacy);

        verify(ProjectMember::find()->where(['project_id' => $p1->id, 'user_id' => $legacy->id])->count())->equals(1);
        verify(ProjectMember::find()->where(['project_id' => $p2->id, 'user_id' => $legacy->id])->exists())->true();
        verify(ProjectMember::find()->where(['user_id' => $pending->id])->exists())->false();
        verify(\app\models\Question::findOne($q->id)->created_by)->equals($legacy->id);
    }

    public function testRefusesWhenTargetAlreadyLinkedToSameProvider()
    {
        $legacy = Factory::user();
        (new UserAuth(['user_id' => $legacy->id, 'source' => 'google', 'source_id' => 'g-other']))->save(false);
        $pending = $this->pendingWithGoogle();
        $this->expectException(UserException::class);
        try {
            (new UserMerger())->merge($pending, $legacy);
        } finally {
            verify(User::findOne($pending->id))->notNull();             // nothing changed
            verify(UserAuth::findOne(['source_id' => 'g-1'])->user_id)->equals($pending->id);
        }
    }

    public function testSuggestionsFindSimilarName()
    {
        $match = Factory::user(['username' => 'somsri', 'first_name' => 'สมศรี', 'last_name' => 'ใจดี']);
        Factory::user(['username' => 'other', 'first_name' => 'วิชัย', 'last_name' => 'ทองดี']);
        $pending = $this->pendingWithGoogle(['first_name' => 'สมศรี', 'last_name' => 'ใจดี', 'email' => 'x@gmail.test']);
        $s = UserMerger::suggestions($pending);
        verify(array_key_first($s))->equals($match->id);
    }
}
