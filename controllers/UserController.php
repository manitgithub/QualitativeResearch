<?php

namespace app\controllers;

use app\models\LoginLog;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * จัดการผู้ใช้ (เฉพาะผู้ดูแลระบบ)
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => fn() => Yii::$app->user->identity->isAdmin(),
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['approve' => ['POST'], 'unlink' => ['POST']],
            ],
        ];
    }

    public function actionIndex($status = null, $q = null)
    {
        $query = User::find()->with('auths')
            ->andFilterWhere(['status' => $status])
            ->andFilterWhere(['or',
                ['like', 'username', $q], ['like', 'email', $q],
                ['like', 'first_name', $q], ['like', 'last_name', $q],
            ]);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['status' => SORT_ASC, 'first_name' => SORT_ASC],
                'attributes' => ['first_name', 'username', 'email', 'status', 'last_login_at', 'created_at']],
        ]);
        $pending = User::find()->where(['status' => User::STATUS_PENDING])->count();
        return $this->render('index', ['provider' => $provider, 'status' => $status, 'q' => $q, 'pending' => $pending]);
    }

    public function actionCreate()
    {
        $model = new User(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_USER]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'เพิ่มผู้ใช้แล้ว');
            return $this->redirect(['index']);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post())) {
            if ((int)$model->id === (int)Yii::$app->user->id) {
                // ป้องกันผู้ดูแลลดสิทธิ์/ระงับตัวเอง
                $model->role = User::ROLE_ADMIN;
                $model->status = User::STATUS_ACTIVE;
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'บันทึกแล้ว');
                return $this->redirect(['index']);
            }
        }
        $logs = LoginLog::find()->where(['user_id' => $model->id])->orderBy(['id' => SORT_DESC])->limit(20)->all();
        return $this->render('form', ['model' => $model, 'logs' => $logs]);
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $model->updateAttributes(['status' => User::STATUS_ACTIVE]);
        Yii::$app->session->setFlash('success', 'อนุมัติ ' . $model->getFullName() . ' แล้ว');
        return $this->redirect(['index']);
    }

    protected function findModel($id): User
    {
        $m = User::findOne((int)$id);
        if (!$m) {
            throw new NotFoundHttpException();
        }
        return $m;
    }
}
