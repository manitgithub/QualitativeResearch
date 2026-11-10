<?php

namespace app\controllers;

use app\components\ai\AiException;
use app\components\ai\InterpretationSuggester;
use app\components\ProjectScopedController;
use app\models\AiSuggestion;
use app\models\Question;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * AI ช่วยสรุป/ยกประเด็น และรับ/ปฏิเสธข้อเสนอของ AI (ทุก action เป็น POST แล้วกลับหน้าเดิม)
 */
class AiController extends ProjectScopedController
{
    protected array $postActions = ['suggest', 'accept', 'reject', 'accept-all', 'reject-all'];

    /**
     * ให้ AI เสนอประเด็นของคำถามหนึ่งข้อ ในรอบที่เลือก (หักเครดิตของผู้กด)
     */
    public function actionSuggest($questionId, $round)
    {
        $question = $this->findQuestion($questionId);
        $project = $this->findProject($question->project_id, self::ACCESS_EDIT);
        $this->requireAi($project);

        @set_time_limit(300);
        try {
            $ai = InterpretationSuggester::create();
            if ((int)$round === InterpretationSuggester::SYNTH_ROUND) {
                $r = $ai->runSynthesis($project, $question, (int)Yii::$app->user->id);
                $msg = "AI รวม {$r['job']->item_count} ประเด็นย่อยเหลือ {$r['created']} กลุ่ม ใช้ {$r['job']->credits_charged} เครดิต — ตรวจแล้วกดรับหรือปฏิเสธ";
            } else {
                $r = $ai->run($project, $question, (int)$round, (int)Yii::$app->user->id);
                $msg = "AI เสนอ {$r['created']} ประเด็นจาก {$r['job']->item_count} คำตอบ ใช้ {$r['job']->credits_charged} เครดิต — ตรวจแล้วกดรับหรือปฏิเสธ";
            }
            if ($r['remaining']) {
                $msg .= " (เหลืออีก {$r['remaining']} คำตอบ กดอีกครั้งเพื่อทำต่อ)";
            }
            Yii::$app->session->setFlash('success', $msg);
        } catch (AiException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->back($question, (int)$round);
    }

    public function actionAccept($id)
    {
        $s = $this->findSuggestion($id);
        try {
            $s->accept((int)Yii::$app->user->id);
        } catch (\yii\base\UserException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->back($s->question, $s->round, $s->response_id);
    }

    public function actionReject($id)
    {
        $s = $this->findSuggestion($id);
        $s->reject((int)Yii::$app->user->id);
        return $this->back($s->question, $s->round, $s->response_id);
    }

    public function actionAcceptAll($questionId, $round)
    {
        return $this->decideAll($questionId, (int)$round, true);
    }

    public function actionRejectAll($questionId, $round)
    {
        return $this->decideAll($questionId, (int)$round, false);
    }

    private function decideAll($questionId, int $round, bool $accept)
    {
        $question = $this->findQuestion($questionId);
        $this->findProject($question->project_id, self::ACCESS_EDIT);
        $n = 0;
        $tx = Yii::$app->db->beginTransaction();
        try {
            foreach (AiSuggestion::find()->where([
                'question_id' => $question->id, 'round' => $round, 'status' => AiSuggestion::STATUS_PENDING,
            ])->orderBy('id')->all() as $s) {
                $accept ? $s->accept((int)Yii::$app->user->id) : $s->reject((int)Yii::$app->user->id);
                $n++;
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        Yii::$app->session->setFlash('success', ($accept ? 'รับ' : 'ปฏิเสธ') . "ข้อเสนอของ AI {$n} ประเด็นแล้ว");
        return $this->back($question, $round);
    }

    private function requireAi($project): void
    {
        if (!InterpretationSuggester::isConfigured()) {
            throw new ForbiddenHttpException('ระบบยังไม่ได้เปิดใช้ AI');
        }
        if (!$project->ai_enabled) {
            throw new ForbiddenHttpException('โครงการนี้ยังไม่อนุญาตให้ใช้ AI (หัวหน้าโครงการเปิดได้ที่หน้าแก้ไขโครงการ)');
        }
    }

    private function findQuestion($id): Question
    {
        $q = Question::findOne(['id' => (int)$id, 'flagDel' => 0]);
        if (!$q) {
            throw new NotFoundHttpException('ไม่พบคำถาม');
        }
        return $q;
    }

    private function findSuggestion($id): AiSuggestion
    {
        $s = AiSuggestion::find()->with('question')->where(['id' => (int)$id, 'status' => AiSuggestion::STATUS_PENDING])->one();
        if (!$s) {
            throw new NotFoundHttpException('ไม่พบข้อเสนอ หรือมีผู้ตัดสินใจไปแล้ว');
        }
        $this->findProject($s->question->project_id, self::ACCESS_EDIT);
        return $s;
    }

    private function back(Question $q, int $round, ?int $responseId = null)
    {
        $url = ['report/question', 'projectId' => $q->project_id, 'questionId' => $q->id, 'round' => max($round, 1)];
        if ($responseId) {
            $url['#'] = 'r' . $responseId;
        }
        return $this->goBackOr($url);
    }
}
