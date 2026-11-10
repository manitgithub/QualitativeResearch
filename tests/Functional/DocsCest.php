<?php

namespace tests\functional;

use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;

/**
 * คู่มือการใช้งาน: เปิดได้เมื่อเข้าระบบ, สารบัญลิงก์ไปยังหัวข้อที่มีอยู่จริงทุกข้อ
 */
class DocsCest
{
    public function _before(FunctionalTester $I)
    {
        Factory::reset();
    }

    public function tocLinksPointToRealSections(FunctionalTester $I)
    {
        $I->amLoggedInAs(Factory::user());
        $I->amOnRoute('site/index');
        $I->seeLink('คู่มือ');
        $I->amOnRoute('site/docs');
        $I->see('คู่มือการใช้งาน', 'h1');

        $hrefs = $I->grabMultiple('.docs-toc-nav a.toc-link', 'href');
        verify(count($hrefs))->greaterThan(40);
        foreach ($hrefs as $href) {
            verify($href)->stringStartsWith('#');
            $I->seeElement('#' . substr($href, 1));
        }
        // ลิงก์ภายในเนื้อหาก็ต้องไปถึงหัวข้อจริง
        foreach ($I->grabMultiple('.docs-body a[href^="#"]', 'href') as $href) {
            $I->seeElement('#' . substr($href, 1));
        }
    }

    public function guestIsSentToLogin(FunctionalTester $I)
    {
        $I->amOnRoute('site/docs');
        $I->dontSee('คู่มือการใช้งาน', 'h1');
    }
}
