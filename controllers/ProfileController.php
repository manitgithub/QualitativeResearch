<?php

namespace app\controllers;

use app\models\User;
use app\models\UserAuth;
use Yii;
use yii\base\DynamicModel;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

/**
 * ข้อมูลส่วนตัว, รหัสผ่าน, ผูก/ยกเลิกบัญชี Google และ LINE
 */
class ProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['unlink' => ['POST']]],
        ];
    }

    public function actionIndex()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $profile = User::findOne($user->id);
        $profile->setScenario('default');

        $pwd = new DynamicModel(['current', 'password', 'confirm']);
        $pwd->addRule(['password', 'confirm'], 'required')
            ->addRule('password', 'string', ['min' => 8])
            ->addRule('confirm', 'compare', ['compareAttribute' => 'password', 'message' => 'รหัสผ่านไม่ตรงกัน'])
            ->addRule('current', $profile->hasPassword() ? 'required' : 'safe');
        $pwd->setAttributeLabels(['current' => 'รหัสผ่านปัจจุบัน', 'password' => 'รหัสผ่านใหม่', 'confirm' => 'ยืนยันรหัสผ่านใหม่']);

        $post = Yii::$app->request->post();
        if (isset($post['User'])) {
            // อีเมลแก้ได้เฉพาะผู้ดูแลระบบ เพราะใช้จับคู่บัญชี Google/LINE อัตโนมัติ
            $profile->setAttributes(array_intersect_key($post['User'],
                array_flip(['prefix', 'first_name', 'last_name', 'username'])));
            if ($profile->save()) {
                Yii::$app->session->setFlash('success', 'บันทึกข้อมูลแล้ว');
                return $this->refresh();
            }
        }
        if (isset($post['DynamicModel'])) {
            $pwd->load($post);
            if ($pwd->validate()) {
                if ($profile->hasPassword() && !$profile->validatePassword((string)$pwd->current)) {
                    $pwd->addError('current', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
                } elseif (!$profile->username && !$profile->email) {
                    $pwd->addError('password', 'กรุณาตั้งชื่อผู้ใช้หรืออีเมลก่อน จึงจะใช้รหัสผ่านเข้าระบบได้');
                } else {
                    $profile->setPassword($pwd->password);
                    $profile->save(false, ['password_hash', 'legacy_password', 'updated_at']);
                    Yii::$app->session->setFlash('success', 'เปลี่ยนรหัสผ่านแล้ว');
                    return $this->refresh();
                }
            }
        }

        return $this->render('index', [
            'profile' => $profile,
            'pwd' => $pwd,
            'clients' => Yii::$app->authClientCollection->getClients(),
        ]);
    }

    public function actionUnlink($source)
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $auth = UserAuth::findOne(['user_id' => $user->id, 'source' => $source]);
        if ($auth) {
            if (!$user->hasPassword() && count($user->auths) <= 1) {
                Yii::$app->session->setFlash('error', 'ต้องตั้งรหัสผ่านหรือผูกบัญชีอื่นไว้ก่อน มิฉะนั้นจะเข้าระบบไม่ได้');
            } else {
                $auth->delete();
                Yii::$app->session->setFlash('success', 'ยกเลิกการผูกบัญชี ' . (UserAuth::sourceLabels()[$source] ?? $source) . ' แล้ว');
            }
        }
        return $this->redirect(['index']);
    }
}
