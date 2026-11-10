<?php

namespace app\controllers;

use app\models\User;
use app\models\UserAuth;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

/**
 * ข้อมูลส่วนตัว, ผูก/ยกเลิกบัญชี Google และ LINE
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

        $post = Yii::$app->request->post();
        if (isset($post['User'])) {
            // อีเมลแก้ได้เฉพาะผู้ดูแลระบบ เพราะใช้จับคู่บัญชี Google/LINE อัตโนมัติ
            $profile->setAttributes(array_intersect_key($post['User'],
                array_flip(['prefix', 'first_name', 'last_name'])));
            if ($profile->save()) {
                Yii::$app->session->setFlash('success', 'บันทึกข้อมูลแล้ว');
                return $this->refresh();
            }
        }
        return $this->render('index', [
            'profile' => $profile,
            'clients' => Yii::$app->authClientCollection->getClients(),
        ]);
    }

    public function actionUnlink($source)
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $auth = UserAuth::findOne(['user_id' => $user->id, 'source' => $source]);
        if ($auth) {
            if (count($user->auths) <= 1) {
                Yii::$app->session->setFlash('error', 'ต้องผูกบัญชีอื่น (Google หรือ LINE) ไว้ก่อน มิฉะนั้นจะเข้าระบบไม่ได้อีก');
            } else {
                $auth->delete();
                Yii::$app->session->setFlash('success', 'ยกเลิกการผูกบัญชี ' . (UserAuth::sourceLabels()[$source] ?? $source) . ' แล้ว');
            }
        }
        return $this->redirect(['index']);
    }
}
