<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $method
 * @property string|null $ip
 * @property string|null $user_agent
 * @property int $created_at
 */
class LoginLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%login_log}}';
    }

    public static function record(User $user, string $method): void
    {
        $req = Yii::$app->request;
        $log = new static([
            'user_id' => $user->id,
            'method' => $method,
            'ip' => $req instanceof \yii\web\Request ? $req->userIP : null,
            'user_agent' => $req instanceof \yii\web\Request ? mb_substr((string)$req->userAgent, 0, 500) : null,
            'created_at' => time(),
        ]);
        $log->save(false);

        $user->updateAttributes(['last_login_at' => time()]);
    }
}
