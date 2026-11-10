<?php

namespace tests\unit\commands;

use app\commands\CreditsController;
use app\components\ai\Credits;
use app\models\User;
use app\tests\Support\Factory;
use Yii;

class CreditsCommandTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
        Yii::$app->params['ai.welcomeCredits'] = 5;
    }

    private function runCmd(): string
    {
        $c = new CreditsController('credits', Yii::$app);
        ob_start();
        try {
            verify($c->runAction('welcome-existing'))->equals(0);
        } finally {
            $out = ob_get_clean();
        }
        return $out;
    }

    public function testGivesActiveUsersOnceAndSkipsOthers()
    {
        $a = Factory::user();
        $b = Factory::admin();
        $already = Factory::user();
        Credits::welcome($already->id);
        $pending = Factory::user(['status' => User::STATUS_PENDING]);
        $suspended = Factory::user(['status' => User::STATUS_INACTIVE]);

        $this->runCmd();
        verify(Credits::balance($a->id))->equals(5);
        verify(Credits::balance($b->id))->equals(5);
        verify(Credits::balance($already->id))->equals(5);
        verify(Credits::balance($pending->id))->equals(0);
        verify(Credits::balance($suspended->id))->equals(0);

        // รันซ้ำไม่แจกเพิ่ม
        $this->runCmd();
        verify(Credits::balance($a->id))->equals(5);
        verify(\app\models\CreditLedger::find()->where(['kind' => 'welcome'])->count())->equals(3);
    }
}
