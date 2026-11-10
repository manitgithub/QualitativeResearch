<?php

namespace app\controllers;

use app\components\ai\AiException;
use app\components\ai\Credits;
use app\components\UserMerger;
use app\models\CreditLedger;
use app\models\LoginLog;
use app\models\User;
use app\models\UserAuth;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
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
                'actions' => ['approve' => ['POST'], 'reject' => ['POST'], 'unlink' => ['POST'], 'credit' => ['POST']],
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
        $pendingUsers = User::find()->with('auths')->where(['status' => User::STATUS_PENDING])->orderBy('created_at')->all();
        $mergeTargets = [];
        if ($pendingUsers) {
            foreach (User::find()->where(['<>', 'status', User::STATUS_PENDING])->orderBy('first_name')->all() as $u) {
                $mergeTargets[$u->id] = $u->getFullName() . ($u->username ? " ({$u->username})" : '') . ($u->email ? " · {$u->email}" : '');
            }
        }
        return $this->render('index', [
            'provider' => $provider, 'status' => $status, 'q' => $q,
            'pendingUsers' => $pendingUsers, 'mergeTargets' => $mergeTargets,
        ]);
    }

    public function actionCreate()
    {
        $model = new User(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_USER]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if ((int)$model->status === User::STATUS_ACTIVE) {
                Credits::welcome($model->id);
            }
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
        return $this->render('form', [
            'model' => $model,
            'logs' => $logs,
            'balance' => Credits::balance($model->id),
            'ledger' => CreditLedger::find()->with('job')->where(['user_id' => $model->id])->orderBy(['id' => SORT_DESC])->limit(20)->all(),
        ]);
    }

    /**
     * อนุมัติบัญชีที่สมัครด้วย Google/LINE
     * POST mergeInto = id บัญชีเดิม → รวมเข้ากับบัญชีนั้น (ได้โครงการเดิมคืน); ว่าง = เปิดเป็นผู้ใช้ใหม่
     */
    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $intoId = (int)Yii::$app->request->post('mergeInto');
        if ($intoId) {
            if ($model->status !== User::STATUS_PENDING) {
                throw new BadRequestHttpException('รวมได้เฉพาะบัญชีที่รออนุมัติ');
            }
            $into = $this->findModel($intoId);
            try {
                (new UserMerger())->merge($model, $into);
            } catch (UserException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('success', 'อนุมัติและรวมเข้ากับบัญชีเดิมของ ' . $into->getFullName() . ' แล้ว');
        } else {
            $model->updateAttributes(['status' => User::STATUS_ACTIVE]);
            Credits::welcome($model->id);
            Yii::$app->session->setFlash('success', 'อนุมัติ ' . $model->getFullName() . ' เป็นผู้ใช้ใหม่แล้ว');
        }
        return $this->redirect(['index']);
    }

    /**
     * ปฏิเสธคำขอ: ลบบัญชีที่รออนุมัติ (และการผูก Google/LINE ของบัญชีนั้น)
     */
    public function actionReject($id)
    {
        $model = $this->findModel($id);
        if ($model->status !== User::STATUS_PENDING) {
            throw new BadRequestHttpException('ปฏิเสธได้เฉพาะบัญชีที่รออนุมัติ');
        }
        // ซ่อนบัญชีและการผูก Google/LINE (flagDel) — คนเดิมยังส่งคำขอใหม่ได้
        UserAuth::deleteAll(['user_id' => $model->id]);
        $model->delete();
        Yii::$app->session->setFlash('success', 'ปฏิเสธคำขอของ ' . $model->getFullName() . ' แล้ว');
        return $this->redirect(['index']);
    }

    /**
     * ยกเลิกการผูก Google/LINE ของผู้ใช้ (เช่น ผูกผิดคน) — ผู้ใช้ต้อง login ใหม่และรออนุมัติ
     */
    public function actionUnlink($id, $source)
    {
        $model = $this->findModel($id);
        UserAuth::deleteAll(['user_id' => $model->id, 'source' => $source]);
        $model->generateAuthKey();      // ตัด session "จดจำการเข้าระบบ" เดิม
        $model->save(false, ['auth_key', 'updated_at']);
        Yii::$app->session->setFlash('success', 'ยกเลิกการผูกบัญชีแล้ว');
        return $this->redirect(['update', 'id' => $model->id]);
    }

    /**
     * เติม (+) หรือหัก (-) เครดิต AI ของผู้ใช้
     */
    public function actionCredit($id)
    {
        $model = $this->findModel($id);
        $amount = (int)Yii::$app->request->post('amount');
        $note = trim((string)Yii::$app->request->post('note')) ?: null;
        try {
            $after = Credits::grant($model->id, $amount, $note, (int)Yii::$app->user->id);
            Yii::$app->session->setFlash('success', ($amount > 0 ? "เติม {$amount}" : 'หัก ' . -$amount) . " เครดิตแล้ว คงเหลือ {$after} เครดิต");
        } catch (AiException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['update', 'id' => $model->id]);
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
