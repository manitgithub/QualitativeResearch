<?php

namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['username', 'trim'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'ชื่อผู้ใช้หรืออีเมล',
            'password' => 'รหัสผ่าน',
            'rememberMe' => 'จดจำการเข้าระบบ',
        ];
    }

    public function validatePassword($attribute)
    {
        if ($this->hasErrors()) {
            return;
        }
        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            return;
        }
        if ($user->status === User::STATUS_PENDING) {
            $this->addError($attribute, 'บัญชีนี้รอผู้ดูแลระบบอนุมัติ');
        } elseif ($user->status !== User::STATUS_ACTIVE) {
            $this->addError($attribute, 'บัญชีนี้ถูกระงับการใช้งาน');
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $user = $this->getUser();
        $ok = Yii::$app->user->login($user, $this->rememberMe ? Yii::$app->params['user.rememberMeDuration'] : 0);
        if ($ok) {
            LoginLog::record($user, 'password');
        }
        return $ok;
    }

    public function getUser(): ?User
    {
        if ($this->_user === false) {
            $this->_user = User::findByLogin((string)$this->username);
        }
        return $this->_user;
    }
}
