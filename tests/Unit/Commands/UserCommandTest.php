<?php

namespace tests\unit\commands;

use app\commands\UserController;
use app\models\User;
use app\tests\Support\Factory;
use Yii;

class UserCommandTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
    }

    private function runCmd(string $action, array $args): int
    {
        $c = new UserController('user', Yii::$app);
        ob_start();
        try {
            return $c->runAction($action, $args);
        } finally {
            ob_end_clean();
        }
    }

    public function testCreateAdminByEmail()
    {
        verify($this->runCmd('admin', ['Boss@Gmail.test']))->equals(0);
        $u = User::findOne(['email' => 'boss@gmail.test']);
        verify($u->role)->equals(User::ROLE_ADMIN);
        verify($u->status)->equals(User::STATUS_ACTIVE);
    }

    public function testPromoteLegacyUserWithEmail()
    {
        $legacy = Factory::user(['username' => 'manit']);
        verify($this->runCmd('admin', ['manit@gmail.test', 'manit']))->equals(0);
        $legacy->refresh();
        verify($legacy->email)->equals('manit@gmail.test');
        verify($legacy->role)->equals(User::ROLE_ADMIN);
        verify(User::find()->count())->equals(1);
    }

    public function testRefusesEmailOwnedByAnotherUser()
    {
        Factory::user(['username' => 'a', 'email' => 'taken@gmail.test']);
        Factory::user(['username' => 'b']);
        verify($this->runCmd('admin', ['taken@gmail.test', 'b']))->notEquals(0);
        verify(User::findOne(['username' => 'b'])->role)->equals(User::ROLE_USER);
    }

    public function testSetEmailForLegacyUser()
    {
        Factory::user(['username' => 'somsri']);
        verify($this->runCmd('email', ['somsri', 'somsri@gmail.test']))->equals(0);
        verify(User::findOne(['username' => 'somsri'])->email)->equals('somsri@gmail.test');
        verify($this->runCmd('email', ['nobody', 'x@gmail.test']))->notEquals(0);
    }
}
