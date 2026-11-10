<?php

namespace tests\functional;

use app\models\User;
use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * หน้าแรก: ยังไม่เข้าระบบเห็นหน้าแนะนำระบบพร้อมสถิติจริง, เข้าระบบแล้วเห็นโครงการของฉัน
 */
class WelcomeCest
{
    public function _before(FunctionalTester $I)
    {
        Factory::reset();
        Yii::$app->cache->delete('welcome-stats');
    }

    public function guestSeesLandingWithRealStats(FunctionalTester $I)
    {
        $owner = Factory::user();
        Factory::user();
        Factory::user(['status' => User::STATUS_PENDING]);   // ไม่นับ
        $p = Factory::project($owner);
        Factory::informant($p, 'P01');
        Factory::informant($p, 'P02');

        $I->amOnRoute('site/index');
        $I->seeResponseCodeIs(200);
        $I->see('เปลี่ยนงานวิจัยเชิงคุณภาพ', 'h1');
        $I->see('ระบบทำอะไรได้บ้าง');
        $I->seeLink('เข้าสู่ระบบ');
        $I->see('2', '.stat-card .stat-num');            // นักวิจัยที่ใช้งาน (ไม่นับคนรออนุมัติ)
        $nums = $I->grabMultiple('.stat-num');
        verify($nums)->equals(['2', '1', '2', '0']);
        $I->dontSee('โครงการทดสอบ');                       // ไม่เปิดเผยข้อมูลโครงการ
    }

    public function loggedInHomeIsProjectList(FunctionalTester $I)
    {
        $I->amLoggedInAs(Factory::user());
        $I->amOnRoute('site/index');
        $I->see('โครงการของฉัน', 'h1');
        $I->dontSee('ระบบทำอะไรได้บ้าง');

        $I->amOnRoute('site/welcome');
        $I->see('ระบบทำอะไรได้บ้าง');
        $I->seeLink('โครงการของฉัน');
        $I->dontSeeLink('เข้าสู่ระบบ');
    }
}
