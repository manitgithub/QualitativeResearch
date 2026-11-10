<?php

namespace tests\unit\components;

use app\components\AuthHandler;
use app\models\LoginLog;
use app\models\User;
use app\models\UserAuth;
use app\tests\Support\Factory;
use Yii;
use yii\authclient\BaseClient;

class StubClient extends BaseClient
{
    public function __construct(string $id, private array $attrs)
    {
        parent::__construct();
        $this->setId($id);
        $this->setTitle(ucfirst($id));
    }

    protected function initUserAttributes()
    {
        return $this->attrs;
    }

    public function getUserAttributes()
    {
        return $this->attrs;
    }
}

class AuthHandlerTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
        Yii::$app->params['socialSignupRequiresApproval'] = true;
    }

    private function handle(string $src, array $attrs): AuthHandler
    {
        $h = new AuthHandler(new StubClient($src, $attrs));
        $h->handle();
        return $h;
    }

    public function testFirstEverUserBecomesActiveAdmin()
    {
        $h = $this->handle('google', ['id' => 'g1', 'email' => 'First@Example.test', 'verified_email' => true, 'name' => 'สมหญิง ใจดี']);
        verify($h->success)->true();
        $u = User::findOne(['email' => 'first@example.test']);
        verify($u->role)->equals(User::ROLE_ADMIN);
        verify($u->status)->equals(User::STATUS_ACTIVE);
        verify($u->first_name)->equals('สมหญิง');
        verify($u->last_name)->equals('ใจดี');
        verify(Yii::$app->user->id)->equals($u->id);
        verify(LoginLog::find()->where(['method' => 'google'])->count())->equals(1);
    }

    public function testNewSocialUserNeedsApproval()
    {
        Factory::admin();
        $h = $this->handle('line', ['id' => 'U1', 'name' => 'Somchai']);
        verify($h->success)->false();
        verify($h->message)->stringContainsString('รอผู้ดูแลระบบอนุมัติ');
        verify(Yii::$app->user->isGuest)->true();
        $u = UserAuth::findOne(['source' => 'line', 'source_id' => 'U1'])->user;
        verify($u->status)->equals(User::STATUS_PENDING);

        $u->updateAttributes(['status' => User::STATUS_ACTIVE]);
        verify($this->handle('line', ['id' => 'U1'])->success)->true();
        verify(Yii::$app->user->id)->equals($u->id);
    }

    public function testApprovalCanBeDisabled()
    {
        Factory::admin();
        Yii::$app->params['socialSignupRequiresApproval'] = false;
        Yii::$app->params['ai.welcomeCredits'] = 5;
        verify($this->handle('line', ['id' => 'U2'])->success)->true();
        // เปิดใช้งานทันที → ได้เครดิตต้อนรับ
        verify(\app\components\ai\Credits::balance((int)Yii::$app->user->id))->equals(5);
    }

    public function testPendingSignupGetsNoWelcomeCreditsYet()
    {
        Factory::admin();
        Yii::$app->params['ai.welcomeCredits'] = 5;
        $this->handle('line', ['id' => 'U3']);
        $u = UserAuth::findOne(['source' => 'line', 'source_id' => 'U3'])->user;
        verify(\app\components\ai\Credits::balance($u->id))->equals(0);
    }

    public function testVerifiedEmailLinksToExistingUser()
    {
        $existing = Factory::user(['email' => 'legacy@example.test']);
        $h = $this->handle('google', ['id' => 'g2', 'email' => 'LEGACY@example.test', 'verified_email' => true]);
        verify($h->success)->true();
        verify(Yii::$app->user->id)->equals($existing->id);
        verify(UserAuth::findOne(['source' => 'google', 'source_id' => 'g2'])->user_id)->equals($existing->id);
    }

    public function testAdminPrecreatedAccountLinksAndLogsInWithoutWaiting()
    {
        Factory::admin();
        $legacy = Factory::user(['username' => 'somsri', 'email' => 'somsri@gmail.test', 'legacy_id' => 2]);
        $h = $this->handle('google', ['id' => 'g-s', 'email' => 'somsri@gmail.test', 'verified_email' => true, 'name' => 'Somsri']);
        verify($h->success)->true();
        verify(Yii::$app->user->id)->equals($legacy->id);
        verify(User::find()->count())->equals(2);     // no extra pending account
    }

    public function testUnverifiedEmailDoesNotTakeOverAccount()
    {
        Factory::admin();
        $victim = Factory::user(['email' => 'victim@example.test']);
        $this->handle('google', ['id' => 'evil', 'email' => 'victim@example.test', 'verified_email' => false]);
        verify(UserAuth::findOne(['source_id' => 'evil'])->user_id)->notEquals($victim->id);
        verify(Yii::$app->user->id)->notEquals($victim->id);
    }

    public function testLinkingWhileLoggedIn()
    {
        $me = Factory::user();
        $other = Factory::user();
        Yii::$app->user->login($me);

        verify($this->handle('google', ['id' => 'g-me'])->success)->true();
        verify($this->handle('google', ['id' => 'g-me2'])->message)->stringContainsString('ไว้แล้ว');   // one per provider

        Yii::$app->user->logout(false);
        Yii::$app->user->login($other);
        $h = $this->handle('google', ['id' => 'g-me']);
        verify($h->success)->false();
        verify($h->message)->stringContainsString('ผู้ใช้อื่น');
        verify(UserAuth::findOne(['source_id' => 'g-me'])->user_id)->equals($me->id);
    }

    public function testSuspendedUserBlocked()
    {
        $u = Factory::user(['status' => User::STATUS_INACTIVE]);
        (new UserAuth(['user_id' => $u->id, 'source' => 'line', 'source_id' => 'U9']))->save(false);
        $h = $this->handle('line', ['id' => 'U9']);
        verify($h->success)->false();
        verify($h->message)->stringContainsString('ระงับ');
        verify(Yii::$app->user->isGuest)->true();
    }

    public function testMissingProviderIdRejected()
    {
        verify($this->handle('google', ['email' => 'x@example.test'])->success)->false();
        verify(User::find()->count())->equals(0);
    }
}
