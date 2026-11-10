<?php

namespace tests\functional;

use app\tests\Support\Factory;
use app\tests\Support\FunctionalTester;

/**
 * ช่องค้นหาโครงการในหน้าแรก
 */
class ProjectSearchCest
{
    private $somsri;
    private $wichai;

    public function _before(FunctionalTester $I)
    {
        Factory::reset();
        $this->somsri = Factory::user(['first_name' => 'สมศรี', 'last_name' => 'ใจดี']);
        $this->wichai = Factory::user(['first_name' => 'วิชัย', 'last_name' => 'ทองดี']);
        Factory::project($this->somsri, ['title' => 'การดูแลผู้สูงอายุในชุมชน', 'description' => 'ศึกษาบทบาท อสม.']);
        Factory::project($this->somsri, ['title' => 'ประสบการณ์ผู้ป่วยเบาหวาน']);
        Factory::project($this->wichai, ['title' => 'ผู้สูงอายุติดเตียง']);   // not visible to somsri
        Factory::project($this->somsri, ['title' => 'ผู้สูงอายุ (เก่า)', 'is_archived' => 1]);
    }

    private function search(FunctionalTester $I, string $q, array $extra = []): void
    {
        $I->amOnRoute('site/index', ['q' => $q] + $extra);
        $I->seeResponseCodeIs(200);
    }

    public function searchBoxIsShown(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->somsri);
        $I->amOnRoute('site/index');
        $I->seeElement('form[role=search] input[name=q]');
        $I->see('การดูแลผู้สูงอายุในชุมชน');
        $I->see('ประสบการณ์ผู้ป่วยเบาหวาน');
    }

    public function searchByTitleOnlyShowsOwnProjects(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->somsri);
        $this->search($I, 'ผู้สูงอายุ');
        $I->see('การดูแลผู้สูงอายุในชุมชน');
        $I->dontSee('ประสบการณ์ผู้ป่วยเบาหวาน');
        $I->dontSee('ผู้สูงอายุติดเตียง');     // someone else's project
        $I->dontSee('ผู้สูงอายุ (เก่า)');      // archived
        $I->see('พบ 1 โครงการ');
    }

    public function searchByDescriptionAndOwnerName(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->somsri);
        $this->search($I, 'อสม.');
        $I->see('การดูแลผู้สูงอายุในชุมชน');
        $I->see('พบ 1 โครงการ');

        $I->amLoggedInAs(\app\models\User::findOne($this->wichai->id));
        $this->search($I, 'ทองดี');
        $I->see('ผู้สูงอายุติดเตียง');
    }

    public function multipleWordsMustAllMatch(FunctionalTester $I)
    {
        $admin = Factory::admin();
        $I->amLoggedInAs($admin);
        $this->search($I, 'ผู้สูงอายุ วิชัย');
        $I->see('ผู้สูงอายุติดเตียง');
        $I->dontSee('การดูแลผู้สูงอายุในชุมชน');
        $I->see('พบ 1 โครงการ');
    }

    public function searchInArchive(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->somsri);
        $this->search($I, 'ผู้สูงอายุ', ['archived' => 1]);
        $I->see('ผู้สูงอายุ (เก่า)');
        $I->dontSee('การดูแลผู้สูงอายุในชุมชน');
        $I->seeInSource('name="archived" value="1"');
    }

    public function noResultsAndEscaping(FunctionalTester $I)
    {
        $I->amLoggedInAs($this->somsri);
        $this->search($I, '<script>alert(1)</script>');
        $I->see('ไม่พบโครงการที่ตรงกับ');
        $I->dontSeeInSource('<script>alert(1)</script>');
        $this->search($I, "'%_ OR 1=1");
        $I->see('ไม่พบโครงการที่ตรงกับ');
    }
}
