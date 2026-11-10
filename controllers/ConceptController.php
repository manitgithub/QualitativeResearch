<?php

namespace app\controllers;

use app\components\ai\AiException;
use app\components\ai\ConceptBuilder;
use app\components\ProjectScopedController;
use app\models\Concept;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Conceptual map (เดิม dt_conceptual)
 */
class ConceptController extends ProjectScopedController
{
    protected array $postActions = ['delete', 'update', 'ai', 'accept', 'reject', 'accept-all'];

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
            'concepts' => Concept::find()->where(['project_id' => $project->id, 'ai_pending' => 0])->orderBy('id')->all(),
            'pending' => Concept::find()->where(['project_id' => $project->id, 'ai_pending' => 1])->orderBy('id')->all(),
            'aiReady' => $project->ai_enabled && \app\components\ai\AiTask::isConfigured(),
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    /**
     * ให้ AI เสนอแนวคิดจากประเด็นสังเคราะห์ของทุกคำถาม (หักเครดิตของผู้กด)
     */
    public function actionAi($projectId)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        if (!ConceptBuilder::isConfigured() || !$project->ai_enabled) {
            throw new ForbiddenHttpException('โครงการนี้ยังไม่อนุญาตให้ใช้ AI (หัวหน้าโครงการเปิดได้ที่หน้าแก้ไขโครงการ)');
        }
        @set_time_limit(300);
        try {
            $r = ConceptBuilder::create()->run($project, (int)Yii::$app->user->id);
            Yii::$app->session->setFlash('success', "AI เสนอ {$r['created']} แนวคิดจาก {$r['job']->item_count} ประเด็น ใช้ {$r['job']->credits_charged} เครดิต — ตรวจแล้วกดรับหรือปฏิเสธ");
        } catch (AiException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    public function actionAccept($id)
    {
        $model = $this->findPending($id);
        $model->updateAttributes(['ai_pending' => 0]);
        return $this->redirect(['index', 'projectId' => $model->project_id]);
    }

    public function actionReject($id)
    {
        $model = $this->findPending($id);
        $model->delete();
        return $this->redirect(['index', 'projectId' => $model->project_id]);
    }

    public function actionAcceptAll($projectId)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $n = Concept::updateAll(['ai_pending' => 0, 'updated_at' => time()], ['project_id' => $project->id, 'ai_pending' => 1]);
        Yii::$app->session->setFlash('success', "รับแนวคิดจาก AI {$n} รายการแล้ว");
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    private function findPending($id): Concept
    {
        $model = Concept::findOne(['id' => (int)$id, 'ai_pending' => 1]);
        if (!$model) {
            throw new NotFoundHttpException('ไม่พบข้อเสนอ หรือมีผู้ตัดสินใจไปแล้ว');
        }
        $this->findProject($model->project_id, self::ACCESS_EDIT);
        return $model;
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
