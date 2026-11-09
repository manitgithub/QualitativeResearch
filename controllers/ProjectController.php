<?php

namespace app\controllers;

use app\components\ProjectScopedController;
use app\models\InformantGroup;
use app\models\Interpretation;
use app\models\Project;
use app\models\ProjectMember;
use app\models\Question;
use app\models\Response;
use app\models\User;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class ProjectController extends ProjectScopedController
{
    protected array $postActions = ['archive', 'restore', 'remove-member', 'delete-group'];

    public function actionCreate()
    {
        $model = new Project(['started_on' => date('Y-m-d')]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'สร้างโครงการแล้ว เริ่มเพิ่มคำถามได้เลย');
            return $this->redirect(['question/index', 'projectId' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findProject($id, self::ACCESS_MANAGE);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'บันทึกแล้ว');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    /**
     * หน้าภาพรวมโครงการ
     */
    public function actionView($id)
    {
        $project = $this->findProject($id);

        $questionIds = Question::find()->select('id')->where(['project_id' => $project->id, 'is_deleted' => 0])->column();
        $stats = [
            'informants' => $project->countInformants(),
            'research' => (int)$project->getResearchQuestions()->count(),
            'demographic' => (int)$project->getDemographicQuestions()->count(),
            'responses' => (int)Response::find()->where(['question_id' => $questionIds, 'is_deleted' => 0])->count(),
        ];
        $rounds = Interpretation::find()
            ->select(['round', 'n' => 'COUNT(*)'])
            ->where(['question_id' => $questionIds, 'is_deleted' => 0])
            ->groupBy('round')
            ->asArray()
            ->all();
        $stats['rounds'] = array_column($rounds, 'n', 'round');

        // ความคืบหน้าต่อคำถาม: จำนวนคำตอบ และจำนวนคำตอบที่สรุปประเด็นแล้ว
        $progress = [];
        if ($questionIds) {
            $answered = Response::find()->select(['question_id', 'n' => 'COUNT(*)'])
                ->where(['question_id' => $questionIds, 'is_deleted' => 0])
                ->groupBy('question_id')->asArray()->all();
            $coded = Response::find()->alias('r')
                ->select(['r.question_id', 'n' => 'COUNT(DISTINCT r.id)'])
                ->innerJoin(['i' => Interpretation::tableName()], 'i.response_id = r.id AND i.is_deleted = 0 AND i.round = 1')
                ->where(['r.question_id' => $questionIds, 'r.is_deleted' => 0])
                ->groupBy('r.question_id')->asArray()->all();
            $progress = [
                'answered' => array_column($answered, 'n', 'question_id'),
                'coded' => array_column($coded, 'n', 'question_id'),
            ];
        }

        return $this->render('view', [
            'project' => $project,
            'stats' => $stats,
            'progress' => $progress,
            'canEdit' => $project->canEdit($this->currentUser()),
            'canManage' => $project->canManage($this->currentUser()),
        ]);
    }

    public function actionArchive($id)
    {
        $project = $this->findProject($id, self::ACCESS_MANAGE);
        $project->updateAttributes(['is_archived' => 1]);
        Yii::$app->session->setFlash('success', 'เก็บโครงการเข้าคลังแล้ว');
        return $this->redirect(['site/index']);
    }

    public function actionRestore($id)
    {
        $project = $this->findProject($id, self::ACCESS_MANAGE);
        $project->updateAttributes(['is_archived' => 0]);
        Yii::$app->session->setFlash('success', 'นำโครงการออกจากคลังแล้ว');
        return $this->redirect(['view', 'id' => $project->id]);
    }

    /**
     * จัดการทีมงาน + กลุ่มผู้ให้ข้อมูล
     */
    public function actionMembers($id)
    {
        $project = $this->findProject($id, self::ACCESS_MANAGE);

        $member = new ProjectMember(['project_id' => $project->id, 'role' => ProjectMember::ROLE_MEMBER]);
        if (Yii::$app->request->post('ProjectMember')) {
            $member->load(Yii::$app->request->post());
            $member->project_id = $project->id;
            if ($member->save()) {
                Yii::$app->session->setFlash('success', 'เพิ่มทีมงานแล้ว');
                return $this->refresh();
            }
        }

        if ($roles = Yii::$app->request->post('roles')) {
            foreach ((array)$roles as $userId => $role) {
                if ((int)$userId === (int)$project->owner_id || !isset(ProjectMember::roleOptions()[$role])) {
                    continue;
                }
                ProjectMember::updateAll(['role' => $role], ['project_id' => $project->id, 'user_id' => (int)$userId]);
            }
            Yii::$app->session->setFlash('success', 'บันทึกบทบาทแล้ว');
            return $this->refresh();
        }

        $group = new InformantGroup(['project_id' => $project->id]);
        if ($group->load(Yii::$app->request->post())) {
            $group->project_id = $project->id;
            if ($group->save()) {
                return $this->refresh();
            }
        }

        $memberIds = array_map(fn($m) => $m->user_id, $project->members);
        $candidates = array_diff_key(User::listActive(), array_flip($memberIds));

        return $this->render('members', [
            'project' => $project,
            'member' => $member,
            'candidates' => $candidates,
            'group' => $group,
        ]);
    }

    public function actionRemoveMember($id, $userId)
    {
        $project = $this->findProject($id, self::ACCESS_MANAGE);
        if ((int)$userId === (int)$project->owner_id) {
            throw new BadRequestHttpException('ไม่สามารถนำหัวหน้าโครงการออกได้');
        }
        ProjectMember::deleteAll(['project_id' => $project->id, 'user_id' => (int)$userId]);
        Yii::$app->session->setFlash('success', 'นำออกจากทีมแล้ว');
        return $this->redirect(['members', 'id' => $project->id]);
    }

    public function actionDeleteGroup($id, $groupId)
    {
        $project = $this->findProject($id, self::ACCESS_MANAGE);
        $group = InformantGroup::findOne(['id' => (int)$groupId, 'project_id' => $project->id]);
        if (!$group) {
            throw new NotFoundHttpException();
        }
        $group->delete();
        return $this->redirect(['members', 'id' => $project->id]);
    }
}
