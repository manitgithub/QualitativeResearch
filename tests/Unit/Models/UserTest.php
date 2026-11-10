<?php

namespace tests\unit\models;

use app\models\User;
use app\tests\Support\Factory;
use Yii;

class UserTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
    }

    public function testNoPasswordColumnsRemain()
    {
        $cols = array_keys(Yii::$app->db->getTableSchema('user', true)->columns);
        verify(array_filter($cols, fn($c) => str_contains($c, 'pass')))->empty();
    }

    public function testThaiUsernameWithSpacesIsAllowed()
    {
        $u = new User(['username' => 'มานิตย์ จิต', 'first_name' => 'x']);
        verify($u->validate(['username']))->true();
        verify((new User(['username' => 'kl', 'first_name' => 'x']))->validate(['username']))->true();   // short legacy names
        $bad = new User(['username' => 'bad<script>', 'first_name' => 'x']);
        verify($bad->validate(['username']))->false();
    }

    public function testOnlyActiveUsersAreIdentities()
    {
        $active = Factory::user();
        $pending = Factory::user(['status' => User::STATUS_PENDING]);
        $banned = Factory::user(['status' => User::STATUS_INACTIVE]);
        verify(User::findIdentity($active->id))->notNull();
        verify(User::findIdentity($pending->id))->null();
        verify(User::findIdentity($banned->id))->null();
    }

    public function testAuthKeyGeneratedAndValidated()
    {
        $u = Factory::user();
        verify(strlen($u->auth_key))->equals(32);
        verify($u->validateAuthKey($u->auth_key))->true();
        verify($u->validateAuthKey('x'))->false();
    }

    public function testFindByUsernameOrEmail()
    {
        $u = Factory::user(['username' => 'bob', 'email' => 'bob@example.test']);
        verify(User::findByLogin('bob')->id)->equals($u->id);
        verify(User::findByLogin('bob@example.test')->id)->equals($u->id);
        verify(User::findByLogin('nobody'))->null();
    }

    public function testEmailUniqueAndValid()
    {
        Factory::user(['email' => 'a@example.test']);
        $dup = new User(['first_name' => 'x', 'email' => 'a@example.test']);
        verify($dup->validate(['email']))->false();
        $bad = new User(['first_name' => 'x', 'email' => 'not-an-email']);
        verify($bad->validate(['email']))->false();
    }
}
