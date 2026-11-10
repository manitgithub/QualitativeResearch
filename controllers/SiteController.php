<?php

namespace app\controllers;

use app\components\AuthHandler;
use app\models\Project;
use Yii;
use yii\authclient\AuthAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['login', 'auth', 'error', 'logout'], 'roles' => ['?', '@']],
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['logout' => ['post']],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => ['class' => \yii\web\ErrorAction::class],
            'auth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'onAuthSuccess'],
                'cancelCallback' => function () {
                    Yii::$app->session->setFlash('warning', 'ยกเลิกการเข้าระบบ');
                    return $this->redirect(['site/login']);
                },
            ],
        ];
    }

    public function onAuthSuccess($client)
    {
        $wasGuest = Yii::$app->user->isGuest;
        $handler = new AuthHandler($client);
        $handler->handle();

        if ($handler->message) {
            Yii::$app->session->setFlash($handler->success ? 'success' : 'error', $handler->message);
        }
        /** @var AuthAction $action */
        $action = $this->action;
        if (!$wasGuest) {
            $action->setSuccessUrl(\yii\helpers\Url::to(['profile/index']));
        } elseif (!$handler->success) {
            $action->setSuccessUrl(\yii\helpers\Url::to(['site/login']));
        } else {
            $action->setSuccessUrl(Yii::$app->user->getReturnUrl(['site/index']));
        }
    }

    /**
     * หน้าหลัก: รายการโครงการที่ผู้ใช้เข้าถึงได้
     */
    public function actionIndex($archived = 0)
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $projects = Project::findVisibleTo($user)
            ->with(['members.user'])
            ->andWhere(['is_archived' => (int)$archived])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();

        $ids = array_map(fn($p) => $p->id, $projects);
        $counts = [];
        if ($ids) {
            $db = Yii::$app->db;
            foreach ([
                'informants' => 'SELECT project_id, COUNT(*) FROM {{%informant}} WHERE is_deleted=0 AND project_id IN (' . implode(',', $ids) . ') GROUP BY project_id',
                'questions' => 'SELECT project_id, COUNT(*) FROM {{%question}} WHERE is_deleted=0 AND project_id IN (' . implode(',', $ids) . ') GROUP BY project_id',
            ] as $key => $sql) {
                foreach ($db->createCommand($sql)->queryAll(\PDO::FETCH_NUM) as [$pid, $n]) {
                    $counts[$pid][$key] = (int)$n;
                }
            }
        }

        return $this->render('index', [
            'projects' => $projects,
            'counts' => $counts,
            'archived' => (int)$archived,
        ]);
    }

    /**
     * หน้าเข้าระบบ: Google หรือ LINE เท่านั้น (ไม่มีการเข้าด้วยรหัสผ่าน)
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $this->layout = 'blank';
        return $this->render('login', ['clients' => Yii::$app->authClientCollection->getClients()]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
