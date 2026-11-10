<?php

namespace app\controllers;

use app\components\ai\AiException;
use app\components\ai\ReportWriter;
use app\components\ProjectScopedController;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\ReportSection;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * ออกเล่มรายงาน: บทวิเคราะห์รายคำถาม + บทสรุป (AI ช่วยเขียน นักวิจัยแก้ไขได้) แล้วส่งออก Word / พิมพ์
 */
class BookController extends ProjectScopedController
{
    protected array $postActions = ['generate', 'save'];

    public function actionIndex($projectId)
    {
        $project = $this->findProject($projectId);
        $canEdit = $project->canEdit($this->currentUser()) && !$project->is_archived;
        return $this->render('index', $this->bookData($project) + [
            'canEdit' => $canEdit,
            'aiReady' => $canEdit && $project->ai_enabled && ReportWriter::isConfigured(),
        ]);
    }

    /**
     * ให้ AI เขียนบทวิเคราะห์ของคำถาม (questionId) หรือบทสรุปภาพรวม (questionId = summary)
     */
    public function actionGenerate($projectId, $questionId)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        if (!ReportWriter::isConfigured() || !$project->ai_enabled) {
            throw new ForbiddenHttpException('โครงการนี้ยังไม่อนุญาตให้ใช้ AI (หัวหน้าโครงการเปิดได้ที่หน้าแก้ไขโครงการ)');
        }
        @set_time_limit(300);
        $anchor = $questionId === 'summary' ? 'summary' : 'q' . (int)$questionId;
        try {
            $writer = ReportWriter::create();
            $r = $questionId === 'summary'
                ? $writer->writeSummary($project, (int)Yii::$app->user->id)
                : $writer->writeSection($project, $this->findQuestion($project, $questionId), (int)Yii::$app->user->id);
            $c = $r['check'];
            $checked = $c['removed'] || $c['badQuotes']
                ? "รอบตรวจทานตัดข้อความที่ข้อมูลไม่รองรับ {$c['removed']} ตัวอักษร" . ($c['badQuotes'] ? " และคำพูดที่ไม่ตรงกับคำตอบจริง {$c['badQuotes']} จุด" : '')
                : 'รอบตรวจทานไม่พบข้อความเกินข้อมูล';
            Yii::$app->session->setFlash('success', "AI เขียนเสร็จแล้ว ใช้ {$r['job']->credits_charged} เครดิต · {$checked} — นักวิจัยต้องตรวจทานก่อนนำไปใช้");
        } catch (AiException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['index', 'projectId' => $project->id, '#' => $anchor]);
    }

    public function actionSave($projectId, $questionId)
    {
        $project = $this->findProject($projectId, self::ACCESS_EDIT);
        $qid = $questionId === 'summary' ? null : $this->findQuestion($project, $questionId)->id;
        $content = (string)Yii::$app->request->post('content', '');
        $section = ReportSection::findOne(['project_id' => $project->id, 'question_id' => $qid])
            ?? new ReportSection(['project_id' => $project->id, 'question_id' => $qid, 'source' => Interpretation::SOURCE_HUMAN]);
        if (trim($content) === '') {
            if (!$section->isNewRecord) {
                $section->softDelete();
            }
            Yii::$app->session->setFlash('success', 'ลบเนื้อหาแล้ว');
        } else {
            $section->content = $content;
            Yii::$app->session->setFlash($section->save() ? 'success' : 'error',
                $section->hasErrors() ? implode(' ', $section->getFirstErrors()) : 'บันทึกแล้ว');
        }
        return $this->redirect(['index', 'projectId' => $project->id, '#' => $qid ? 'q' . $qid : 'summary']);
    }

    /**
     * ส่งออกเล่ม: format=doc ดาวน์โหลดไฟล์ Word, format=print หน้าพร้อมพิมพ์ / บันทึกเป็น PDF
     */
    public function actionExport($projectId, $format = 'doc')
    {
        $project = $this->findProject($projectId);
        $html = $this->renderPartial('_book', $this->bookData($project) + ['forWord' => $format === 'doc']);
        if ($format !== 'doc') {
            return $this->renderPartial('print', ['project' => $project, 'body' => $html]);
        }
        $name = preg_replace('/[\\\\\/:*?"<>|\s]+/u', '_', mb_substr($project->title, 0, 60)) ?: 'report';
        return Yii::$app->response->sendContentAsFile(
            "\xEF\xBB\xBF" . $this->renderPartial('word', ['project' => $project, 'body' => $html]),
            "{$name}.doc",
            ['mimeType' => 'application/msword'],
        );
    }

    private function bookData(Project $project): array
    {
        $questions = Question::find()
            ->where(['project_id' => $project->id, 'category' => Question::CATEGORY_RESEARCH, 'flagDel' => 0])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $themes = [];
        foreach (Interpretation::find()->where([
            'question_id' => array_map(fn($q) => $q->id, $questions), 'round' => 4, 'response_id' => null, 'flagDel' => 0,
        ])->orderBy('id')->all() as $it) {
            $themes[$it->question_id][] = $it;
        }
        $team = [];
        foreach ($project->getMembers()->with('user')->all() as $m) {
            if ($m->user) {
                $team[] = $m->user->getFullName();
            }
        }
        return [
            'project' => $project,
            'questions' => $questions,
            'themes' => $themes,
            'sections' => ReportSection::forProject($project->id),
            'team' => $team,
        ];
    }

    private function findQuestion(Project $project, $id): Question
    {
        $q = Question::findOne(['id' => (int)$id, 'project_id' => $project->id, 'flagDel' => 0]);
        if (!$q) {
            throw new NotFoundHttpException('ไม่พบคำถาม');
        }
        return $q;
    }
}
