<?php

namespace app\controllers;

use app\components\ProjectScopedController;
use app\models\Question;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response as WebResponse;

class QuestionController extends ProjectScopedController
{
    protected array $postActions = ['delete', 'reorder'];

    public function actionIndex($projectId)
    {
        $project = $this->findProject($projectId);
        $questions = Question::find()
            ->with('options')
            ->where(['project_id' => $project->id, 'flagDel' => 0])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $grouped = [Question::CATEGORY_RESEARCH => [], Question::CATEGORY_DEMOGRAPHIC => []];
        foreach ($questions as $q) {
            $grouped[$q->category][] = $q;
        }

        return $this->render('index', [
            'project' => $project,
            'grouped' => $grouped,
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    public function actionCreate($projectId, $category = Question::CATEGORY_RESEARCH)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $max = (int)Question::find()->where(['project_id' => $project->id, 'category' => $category])->max('sort_order');
        $model = new Question([
            'project_id' => $project->id,
            'category' => isset(Question::categoryOptions()[$category]) ? $category : Question::CATEGORY_RESEARCH,
            'answer_type' => Question::TYPE_TEXT,
            'sort_order' => $max + 1,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $model->project_id = $project->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'เพิ่มคำถามแล้ว');
                if (Yii::$app->request->post('addAnother')) {
                    return $this->redirect(['create', 'projectId' => $project->id, 'category' => $model->category]);
                }
                return $this->redirect(['index', 'projectId' => $project->id]);
            }
        }
        return $this->render('form', ['project' => $project, 'model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findQuestion($id);
        $project = $this->findProject($model->project_id, self::ACCESS_EDIT);
        $model->loadOptionsText();

        if ($model->load(Yii::$app->request->post())) {
            $model->project_id = $project->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'บันทึกแล้ว');
                return $this->redirect(['index', 'projectId' => $project->id]);
            }
        }
        return $this->render('form', ['project' => $project, 'model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findQuestion($id);
        $project = $this->findProject($model->project_id, self::ACCESS_EDIT);
        $model->softDelete();
        Yii::$app->session->setFlash('success', 'ลบคำถามแล้ว (คำตอบเดิมยังถูกเก็บไว้)');
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    /**
     * รับลำดับใหม่จากการลากวาง: ids[] = question id ตามลำดับ
     */
    public function actionReorder($projectId)
    {
        Yii::$app->response->format = WebResponse::FORMAT_JSON;
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $ids = array_map('intval', (array)Yii::$app->request->post('ids', []));
        foreach ($ids as $i => $qid) {
            Question::updateAll(['sort_order' => $i + 1], ['id' => $qid, 'project_id' => $project->id]);
        }
        return ['ok' => true];
    }

    protected function findQuestion($id): Question
    {
        $q = Question::findOne(['id' => (int)$id, 'flagDel' => 0]);
        if (!$q) {
            throw new NotFoundHttpException('ไม่พบคำถาม');
        }
        return $q;
    }
}
