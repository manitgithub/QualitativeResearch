<?php

namespace app\controllers;

use app\components\ProjectScopedController;
use app\models\Interpretation;
use app\models\Question;
use app\models\Response;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * เพิ่ม/แก้/ลบ การตีความ (ฟอร์มย่อยในหน้ารายงาน) ทุก action เป็น POST แล้วกลับไปหน้าเดิม
 */
class InterpretationController extends ProjectScopedController
{
    protected array $postActions = ['create', 'update', 'delete'];

    public function actionCreate()
    {
        $data = Yii::$app->request->post('Interpretation', []);
        $model = new Interpretation();
        $model->load(['Interpretation' => $data]);

        if ($model->response_id) {
            $response = Response::findOne(['id' => (int)$model->response_id, 'flagDel' => 0]);
            if (!$response) {
                throw new NotFoundHttpException('ไม่พบคำตอบ');
            }
            $model->question_id = $response->question_id;
        }
        $question = Question::findOne((int)$model->question_id);
        if (!$question) {
            throw new BadRequestHttpException('ไม่ระบุคำถาม');
        }
        $project = $this->findProject($question->project_id, self::ACCESS_EDIT);

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'เพิ่มประเด็นแล้ว');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }
        return $this->goBackOr(['report/question', 'projectId' => $project->id, 'questionId' => $question->id]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $project = $this->findProject($model->question->project_id, self::ACCESS_EDIT);
        $data = Yii::$app->request->post('Interpretation', []);
        $model->setAttributes(array_intersect_key($data, array_flip(['text', 'ipo', 'round'])));
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'แก้ไขประเด็นแล้ว');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }
        return $this->goBackOr(['report/question', 'projectId' => $project->id, 'questionId' => $model->question_id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $project = $this->findProject($model->question->project_id, self::ACCESS_EDIT);
        $model->softDelete();
        Yii::$app->session->setFlash('success', 'ลบประเด็นแล้ว');
        return $this->goBackOr(['report/question', 'projectId' => $project->id, 'questionId' => $model->question_id]);
    }

    protected function findModel($id): Interpretation
    {
        $m = Interpretation::find()->with('question')->where(['id' => (int)$id, 'flagDel' => 0])->one();
        if (!$m) {
            throw new NotFoundHttpException('ไม่พบประเด็น');
        }
        return $m;
    }
}
