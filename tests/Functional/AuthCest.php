<?php

namespace tests\functional;

use app\models\User;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;

class AuthCest
{
    public function _before(FunctionalTester $I)
    {
        Factory::reset();
    }

    public function guestIsRedirectedToLogin(FunctionalTester $I)
    {
        foreach (['project/create', 'user/index', 'profile/index', 'site/docs'] as $route) {
            $I->amOnRoute($route);
            $I->seeInCurrentUrl('site%2Flogin');
        }
        // หน้าแรกของผู้ที่ยังไม่เข้าระบบ = หน้าแนะนำระบบ (ไม่ใช่รายการโครงการ)
        $I->amOnRoute('site/index');
        $I->see('ระบบทำอะไรได้บ้าง');
        $I->dontSee('โครงการของฉัน', 'h1');
    }

    public function loginPageShowsGoogleAndLine(FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
        $I->see('เข้าสู่ระบบด้วย Gmail');
        $I->see('เข้าสู่ระบบด้วย LINE');
        $I->seeElement('a', ['href' => '/index-test.php?r=site%2Fauth&authclient=google']);
    }

    public function noPasswordLogin(FunctionalTester $I)
    {
        Factory::user(['username' => 'bob']);
        $I->amOnRoute('site/login');
        $I->dontSeeElement('input[type=password]');
        $I->dontSeeElement('#login-form');
        $I->sendAjaxPostRequest('/index-test.php?r=site%2Flogin', ['LoginForm' => ['username' => 'bob', 'password' => 'anything']]);
        verify(\Yii::$app->user->isGuest)->true();
        $I->amOnRoute('profile/index');
        $I->seeInCurrentUrl('site%2Flogin');
    }

    public function logoutRequiresPost(FunctionalTester $I)
    {
        $I->amLoggedInAs(Factory::user());
        $I->amOnRoute('site/logout');
        $I->seeResponseCodeIs(405);
        $I->sendAjaxPostRequest('/index-test.php?r=site%2Flogout');
        $I->amOnRoute('profile/index');
        $I->seeInCurrentUrl('site%2Flogin');
    }

    public function profileUpdateCannotChangeEmailOrRole(FunctionalTester $I)
    {
        $u = Factory::user(['email' => 'me@example.test']);
        $I->amLoggedInAs($u);
        $I->sendAjaxPostRequest('/index-test.php?r=profile%2Findex', ['User' => [
            'first_name' => 'ชื่อใหม่', 'email' => 'attacker@example.test', 'role' => 'admin', 'status' => 10, 'username' => 'hijack',
        ]]);
        $I->seeRecord(User::class, ['id' => $u->id, 'first_name' => 'ชื่อใหม่', 'email' => 'me@example.test', 'role' => 'user', 'username' => $u->username]);
    }

    public function cannotUnlinkLastLoginMethod(FunctionalTester $I)
    {
        $u = Factory::user(['username' => null]);
        (new \app\models\UserAuth(['user_id' => $u->id, 'source' => 'google', 'source_id' => 'g']))->save(false);
        $I->amLoggedInAs($u);
        $I->sendAjaxPostRequest('/index-test.php?r=profile%2Funlink&source=google');
        $I->seeRecord(\app\models\UserAuth::class, ['user_id' => $u->id, 'source' => 'google']);
    }
}
