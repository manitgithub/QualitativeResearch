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
                    ['allow' => true, 'actions' => ['login', 'auth', 'error', 'logout', 'welcome'], 'roles' => ['?', '@']],
                    // หน้าแรก: ยังไม่เข้าระบบ = หน้าแนะนำระบบ, เข้าระบบแล้ว = โครงการของฉัน
                    ['allow' => true, 'actions' => ['index'], 'roles' => ['?']],
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
            // หน้า error ใช้ layout เรียบง่าย (ไม่มีเมนู/เครดิต) — ถ้าเมนูหลักพัง หน้า error ยังแสดงได้
            'error' => ['class' => \yii\web\ErrorAction::class, 'layout' => 'blank'],
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
    public function actionIndex($archived = 0, $q = null)
    {
        if (Yii::$app->user->isGuest) {
            return $this->actionWelcome();
        }
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $q = trim((string)$q);
        $query = Project::findVisibleTo($user)
            ->with(['members.user'])
            ->andWhere(['project.is_archived' => (int)$archived])
            ->orderBy(['project.updated_at' => SORT_DESC]);

        // ค้นหา: ทุกคำต้องพบในชื่อโครงการ รายละเอียด หรือชื่อหัวหน้าโครงการ
        if ($q !== '') {
            $query->joinWith('owner o', false);
            foreach (array_slice(preg_split('/\s+/u', $q), 0, 8) as $word) {
                $query->andWhere(['or',
                    ['like', 'project.title', $word],
                    ['like', 'project.description', $word],
                    ['like', 'o.first_name', $word],
                    ['like', 'o.last_name', $word],
                    ['like', 'o.username', $word],
                ]);
            }
        }
        $projects = $query->all();

        $ids = array_map(fn($p) => $p->id, $projects);
        $counts = [];
        if ($ids) {
            $db = Yii::$app->db;
            foreach ([
                'informants' => 'SELECT project_id, COUNT(*) FROM {{%informant}} WHERE flagDel=0 AND project_id IN (' . implode(',', $ids) . ') GROUP BY project_id',
                'questions' => 'SELECT project_id, COUNT(*) FROM {{%question}} WHERE flagDel=0 AND project_id IN (' . implode(',', $ids) . ') GROUP BY project_id',
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
            'q' => $q,
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

    /**
     * หน้าแนะนำระบบ (สาธารณะ) พร้อมสถิติการใช้งานจริง
     */
    public function actionWelcome()
    {
        $this->layout = 'landing';
        $stats = Yii::$app->cache->getOrSet('welcome-stats', function () {
            $db = Yii::$app->db;
            $count = fn(string $sql) => (int)$db->createCommand($sql)->queryScalar();
            return [
                'users' => $count('SELECT COUNT(*) FROM {{%user}} WHERE flagDel = 0 AND status = 10'),
                'projects' => $count('SELECT COUNT(*) FROM {{%project}} WHERE flagDel = 0'),
                'informants' => $count('SELECT COUNT(*) FROM {{%informant}} WHERE flagDel = 0'),
                'issues' => $count('SELECT COUNT(*) FROM {{%interpretation}} WHERE flagDel = 0'),
            ];
        }, 600);
        return $this->render('welcome', ['stats' => $stats]);
    }

    /**
     * คู่มือการใช้งาน
     */
    public function actionDocs()
    {
        return $this->render('docs');
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
