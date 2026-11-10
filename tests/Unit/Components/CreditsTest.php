<?php

namespace tests\unit\components;

use app\components\ai\AiException;
use app\components\ai\Credits;
use app\components\ai\Pricing;
use app\models\AiJob;
use app\models\CreditLedger;
use app\tests\Support\Factory;
use Yii;

class CreditsTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
        Yii::$app->params['ai.usdToThb'] = 34.0;
        Yii::$app->params['ai.markup'] = 1.5;
        Yii::$app->params['ai.bahtPerCredit'] = 0.10;
    }

    private function job(int $userId): int
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $j = new AiJob(['user_id' => $userId, 'project_id' => $p->id, 'feature' => 'x', 'model' => 'm', 'created_at' => time()]);
        $j->save(false);
        return $j->id;
    }

    /** ยอดใน wallet ต้องเท่ากับผลรวม ledger และเท่ากับ balance_after ล่าสุดเสมอ */
    private function assertConsistent(int $userId): void
    {
        $sum = (int)CreditLedger::find()->where(['user_id' => $userId])->sum('delta');
        $last = CreditLedger::find()->where(['user_id' => $userId])->orderBy(['id' => SORT_DESC])->one();
        verify(Credits::balance($userId))->equals($sum);
        verify($last ? $last->balance_after : 0)->equals($sum);
    }

    public function testGrantAndDeduct()
    {
        $u = Factory::user();
        $admin = Factory::admin();
        verify(Credits::balance($u->id))->equals(0);
        verify(Credits::grant($u->id, 100, 'โอน 10 บาท', $admin->id))->equals(100);
        verify(Credits::grant($u->id, -30, null, $admin->id))->equals(70);
        $this->assertConsistent($u->id);
        verify(CreditLedger::findOne(['user_id' => $u->id, 'delta' => 100])->created_by)->equals($admin->id);
    }

    public function testCannotDeductBelowZero()
    {
        $u = Factory::user();
        Credits::grant($u->id, 10, null, null);
        $this->expectException(AiException::class);
        Credits::grant($u->id, -11, null, null);
    }

    public function testFailedChangeLeavesNoTrace()
    {
        $u = Factory::user();
        Credits::grant($u->id, 10, null, null);
        try {
            Credits::grant($u->id, -11, null, null);
        } catch (AiException) {
        }
        verify(Credits::balance($u->id))->equals(10);
        verify(CreditLedger::find()->where(['user_id' => $u->id])->count())->equals(1);
    }

    public function testHoldRequiresBalance()
    {
        $u = Factory::user();
        $job = $this->job($u->id);
        Credits::grant($u->id, 5, null, null);
        $this->expectException(AiException::class);
        Credits::hold($u->id, 6, $job);
    }

    public function testSettleRefundsUnusedHold()
    {
        $u = Factory::user();
        $job = $this->job($u->id);
        Credits::grant($u->id, 100, null, null);
        Credits::hold($u->id, 40, $job);
        verify(Credits::balance($u->id))->equals(60);
        verify(Credits::settle($u->id, 40, 15, $job))->equals(15);
        verify(Credits::balance($u->id))->equals(85);
        $this->assertConsistent($u->id);
    }

    public function testSettleChargesExtraOnlyUpToBalance()
    {
        $u = Factory::user();
        $job = $this->job($u->id);
        Credits::grant($u->id, 50, null, null);
        Credits::hold($u->id, 40, $job);       // คงเหลือ 10
        verify(Credits::settle($u->id, 40, 70, $job))->equals(50); // ใช้จริง 70 แต่หักเพิ่มได้แค่ 10
        verify(Credits::balance($u->id))->equals(0);
        $this->assertConsistent($u->id);
    }

    public function testSettleZeroRefundsEverything()
    {
        $u = Factory::user();
        $job = $this->job($u->id);
        Credits::grant($u->id, 30, null, null);
        Credits::hold($u->id, 30, $job);
        Credits::settle($u->id, 30, 0, $job);
        verify(Credits::balance($u->id))->equals(30);
        $this->assertConsistent($u->id);
    }

    public function testWelcomeCreditsOnlyOnce()
    {
        Yii::$app->params['ai.welcomeCredits'] = 5;
        $u = Factory::user();
        verify(Credits::welcome($u->id))->true();
        verify(Credits::welcome($u->id))->false();
        verify(Credits::balance($u->id))->equals(5);
        verify(CreditLedger::find()->where(['user_id' => $u->id, 'kind' => CreditLedger::KIND_WELCOME])->count())->equals(1);
        $this->assertConsistent($u->id);

        // ใช้หมดแล้วก็ไม่ได้ใหม่
        $job = $this->job($u->id);
        Credits::hold($u->id, 5, $job);
        verify(Credits::welcome($u->id))->false();
        verify(Credits::balance($u->id))->equals(0);
    }

    public function testWelcomeCreditsCanBeDisabled()
    {
        Yii::$app->params['ai.welcomeCredits'] = 0;
        $u = Factory::user();
        verify(Credits::welcome($u->id))->false();
        verify(CreditLedger::find()->where(['user_id' => $u->id])->count())->equals(0);
    }

    public function testPricingFormula()
    {
        // $0.01 × 34 × 1.5 = 0.51 บาท = 5.1 → 6 เครดิต
        verify(Pricing::creditsForUsd(0.01))->equals(6);
        // ใช้น้อยมากก็คิดขั้นต่ำ 1 เครดิต
        verify(Pricing::creditsForUsd(0.0000001))->equals(1);
        // Opus 5.5: 1M input = $4
        verify(Pricing::costUsd('anthropic/claude-opus-5.5', 1_000_000, 0))->equals(4.0);
    }
}
