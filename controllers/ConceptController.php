<?php

namespace app\controllers;

use app\components\ProjectScopedController;
use app\models\Concept;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Conceptual map (เดิม dt_conceptual)
 */
class ConceptController extends ProjectScopedController
{
    protected array $postActions = ['delete', 'update'];

    public function actionIndex($projectId)
    {
        $project = $this->findProject($projectId);
        $model = new Concept(['project_id' => $project->id]);

        if ($model->load(Yii::$app->request->post())) {
            $this->findProject($projectId, self::ACCESS_EDIT);
            $model->project_id = $project->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'เพิ่มแล้ว');
                return $this->refresh();
            }
        }

        return $this->render('index', [
            'project' => $project,
            'model' => $model,
            'concepts' => Concept::find()->where(['project_id' => $project->id])->orderBy('id')->all(),
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $project = $this->findProject($model->project_id, self::ACCESS_EDIT);
        // checkbox ที่ไม่ถูกติ๊กจะไม่ถูกส่งมา จึงตั้งค่าเริ่มเป็น 0 ก่อน
        $data = Yii::$app->request->post('Concept', []) + array_fill_keys(Concept::flagColumns(), 0);
        $model->setAttributes(array_intersect_key($data, array_flip(array_merge(['name'], Concept::flagColumns()))));
        $model->save();
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $project = $this->findProject($model->project_id, self::ACCESS_EDIT);
        $model->delete();
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    protected function findModel($id): Concept
    {
        $m = Concept::findOne((int)$id);
        if (!$m) {
            throw new NotFoundHttpException();
        }
        return $m;
    }
}
