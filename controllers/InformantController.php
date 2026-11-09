<?php

namespace app\controllers;

use app\components\ProjectScopedController;
use app\models\CollectionMethod;
use app\models\forms\ImportForm;
use app\models\forms\InterviewForm;
use app\models\Informant;
use app\models\InformantGroup;
use app\models\Response;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class InformantController extends ProjectScopedController
{
    public function actionIndex($projectId, $q = null, $groupId = null)
    {
        $project = $this->findProject($projectId);

        $query = Informant::find()
            ->with(['group', 'collectionMethod'])
            ->where(['project_id' => $project->id, 'is_deleted' => 0])
            ->andFilterWhere(['like', 'code', $q])
            ->andFilterWhere(['group_id' => $groupId]);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => [
                'defaultOrder' => ['code' => SORT_ASC],
                'attributes' => ['code', 'age', 'interviewed_on', 'created_at'],
            ],
        ]);

        $ids = array_map(fn($m) => $m->id, $provider->getModels());
        $answerCounts = $ids ? Response::find()
            ->select(['informant_id', 'n' => 'COUNT(*)'])
            ->where(['informant_id' => $ids, 'is_deleted' => 0])
            ->groupBy('informant_id')->asArray()->all() : [];

        return $this->render('index', [
            'project' => $project,
            'provider' => $provider,
            'answerCounts' => array_column($answerCounts, 'n', 'informant_id'),
            'groups' => InformantGroup::options($project->id),
            'q' => $q,
            'groupId' => $groupId,
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    /**
     * บันทึกผู้ให้ข้อมูลใหม่พร้อมคำตอบทุกข้อ (หรือเฉพาะข้อ ?questionId=)
     */
    public function actionCreate($projectId, $questionId = null)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $form = new InterviewForm($project, null, $questionId ? (int)$questionId : null);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', 'บันทึกข้อมูลผู้ให้ข้อมูล ' . $form->informant->code . ' แล้ว');
            if (Yii::$app->request->post('addAnother')) {
                return $this->redirect(['create', 'projectId' => $project->id, 'questionId' => $questionId]);
            }
            return $this->redirect(['view', 'id' => $form->informant->id]);
        }

        return $this->render('form', $this->formParams($project, $form));
    }

    public function actionUpdate($id)
    {
        $informant = $this->findInformant($id);
        $project = $this->findProject($informant->project_id, self::ACCESS_EDIT);
        $form = new InterviewForm($project, $informant);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', 'บันทึกแล้ว');
            return $this->redirect(['view', 'id' => $informant->id]);
        }

        return $this->render('form', $this->formParams($project, $form));
    }

    public function actionView($id)
    {
        $informant = $this->findInformant($id);
        $project = $this->findProject($informant->project_id);

        $responses = Response::find()
            ->with(['question', 'option', 'interpretations'])
            ->where(['informant_id' => $informant->id, 'is_deleted' => 0])
            ->all();
        usort($responses, fn($a, $b) => [$a->question->category !== 'demographic', $a->question->sort_order, $a->question_id]
            <=> [$b->question->category !== 'demographic', $b->question->sort_order, $b->question_id]);

        $ids = Informant::find()->select('id')
            ->where(['project_id' => $project->id, 'is_deleted' => 0])
            ->orderBy(['code' => SORT_ASC])->column();
        $pos = array_search($informant->id, $ids);

        return $this->render('view', [
            'project' => $project,
            'informant' => $informant,
            'responses' => array_values(array_filter($responses, fn($r) => !$r->question->is_deleted)),
            'prevId' => $pos > 0 ? $ids[$pos - 1] : null,
            'nextId' => $pos !== false && $pos < count($ids) - 1 ? $ids[$pos + 1] : null,
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    public function actionDelete($id)
    {
        $informant = $this->findInformant($id);
        $project = $this->findProject($informant->project_id, self::ACCESS_EDIT);
        $informant->softDelete();
        Yii::$app->session->setFlash('success', 'ลบผู้ให้ข้อมูล ' . $informant->code . ' แล้ว');
        return $this->redirect(['index', 'projectId' => $project->id]);
    }

    /**
     * นำเข้าจาก Excel/CSV
     */
    public function actionImport($projectId)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $model = new ImportForm($project);

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');
            $model->load(Yii::$app->request->post());
            if ($model->import()) {
                Yii::$app->session->setFlash('success', $model->summary);
                return $this->redirect(['index', 'projectId' => $project->id]);
            }
        }

        return $this->render('import', ['project' => $project, 'model' => $model]);
    }

    public function actionTemplate($projectId)
    {
        $project = $this->findProject($projectId);
        $path = (new ImportForm($project))->buildTemplate();
        $response = Yii::$app->response->sendFile($path, 'template-' . $project->id . '.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, fn() => @unlink($path));
        return $response;
    }

    protected function formParams($project, InterviewForm $form): array
    {
        return [
            'project' => $project,
            'form' => $form,
            'groups' => InformantGroup::options($project->id),
            'methods' => CollectionMethod::options(),
        ];
    }

    protected function findInformant($id): Informant
    {
        $m = Informant::findOne(['id' => (int)$id, 'is_deleted' => 0]);
        if (!$m) {
            throw new NotFoundHttpException('ไม่พบผู้ให้ข้อมูล');
        }
        return $m;
    }
}
