<?php

namespace app\controllers;

use app\components\ExcelExporter;
use app\components\ProjectScopedController;
use app\models\Concept;
use app\models\Informant;
use app\models\Interpretation;
use app\models\Project;
use app\models\Question;
use app\models\Response;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response as WebResponse;

/**
 * รายงานและการวิเคราะห์ (แทน site/page&view=report*, tree*, data*, wordcloud, chartpie)
 */
class ReportController extends ProjectScopedController
{
    public function actionIndex($projectId)
    {
        return $this->redirect(['question', 'projectId' => $projectId]);
    }

    /**
     * ข้อมูลตามคำถามการวิจัย + ตีความ/ยกประเด็นรายคำตอบ
     * (รวม report3, report3edit, report3filter, report3filterup1/2, report3full)
     */
    public function actionQuestion($projectId, $questionId = null, $round = 1, $export = null, $q = null)
    {
        $project = $this->findProject($projectId);
        $round = max(1, min(4, (int)$round));
        $questions = $this->questions($project, Question::CATEGORY_RESEARCH);
        if ($questionId === null && $questions && !$export) {
            $questionId = $questions[0]->id;
        }
        $selected = $questionId === 'all' || $questionId === null
            ? $questions
            : array_values(array_filter($questions, fn($x) => $x->id == $questionId));

        $data = [];
        foreach ($selected as $question) {
            $responses = Response::find()->alias('r')
                ->innerJoinWith(['informant inf'])
                ->with(['option', 'interpretations'])
                ->where(['r.question_id' => $question->id, 'r.is_deleted' => 0, 'inf.is_deleted' => 0])
                ->andFilterWhere(['or', ['like', 'r.value_text', $q], ['like', 'inf.code', $q]])
                ->orderBy(['inf.code' => SORT_ASC])
                ->all();
            foreach ($responses as $r) {
                $r->populateRelation('question', $question);
            }
            $synth = Interpretation::find()
                ->where(['question_id' => $question->id, 'response_id' => null, 'is_deleted' => 0])
                ->orderBy(['round' => SORT_ASC, 'id' => SORT_ASC])->all();
            $data[] = ['question' => $question, 'responses' => $responses, 'synth' => $synth];
        }

        if ($export) {
            return $this->exportByQuestion($project, $data, $round);
        }

        return $this->render('question', [
            'project' => $project,
            'questions' => $questions,
            'questionId' => $questionId,
            'round' => $round,
            'data' => $data,
            'q' => $q,
            'canEdit' => $project->canEdit($this->currentUser()),
        ]);
    }

    /**
     * ประเด็นที่ได้ จัดกลุ่มตามข้อความ พร้อมจำนวนและผู้ให้ข้อมูล (แทน data4/treedata4, report3filterup)
     */
    public function actionThemes($projectId, $round = 1, $export = null)
    {
        $project = $this->findProject($projectId);
        $round = max(1, min(4, (int)$round));
        $themes = $this->themeData($project, $round);

        if ($export) {
            $rows = [];
            foreach ($themes as $t) {
                foreach ($t['themes'] as $th) {
                    $rows[] = [$t['question']->title, $th['text'], $th['count'], implode(', ', $th['informants'])];
                }
            }
            return ExcelExporter::send('themes-' . $project->id . '-r' . $round, [
                'ประเด็น' => [
                    'title' => $project->title . ' — ' . Interpretation::roundOptions()[$round],
                    'header' => ['คำถาม', 'ประเด็น', 'จำนวน', 'ผู้ให้ข้อมูล'],
                    'rows' => $rows,
                    'widths' => [50, 60, 10, 50],
                ],
            ]);
        }

        return $this->render('themes', ['project' => $project, 'round' => $round, 'themes' => $themes]);
    }

    /**
     * ข้อมูลตามผู้ให้ข้อมูล: ตาราง ผู้ให้ข้อมูล × คำถาม (แทน report1)
     */
    public function actionInformant($projectId, $export = null)
    {
        $project = $this->findProject($projectId);
        $questions = $this->questions($project);
        $informants = Informant::find()->with('group')
            ->where(['project_id' => $project->id, 'is_deleted' => 0])
            ->orderBy('code')->all();

        $matrix = [];
        foreach (Response::find()->with('option')
                     ->where(['question_id' => ArrayHelper::getColumn($questions, 'id'), 'is_deleted' => 0])
                     ->each(500) as $r) {
            $q = null;
            foreach ($questions as $qq) {
                if ($qq->id === $r->question_id) {
                    $q = $qq;
                    break;
                }
            }
            $r->populateRelation('question', $q);
            $matrix[$r->informant_id][$r->question_id] = $r->getDisplayValue();
        }

        if ($export) {
            $rows = [];
            foreach ($informants as $inf) {
                $row = [$inf->code, $inf->age, $inf->group->name ?? ''];
                foreach ($questions as $qq) {
                    $row[] = $matrix[$inf->id][$qq->id] ?? '';
                }
                $rows[] = $row;
            }
            return ExcelExporter::send('informants-' . $project->id, [
                'ผู้ให้ข้อมูล' => [
                    'title' => $project->title,
                    'header' => array_merge(['รหัส', 'อายุ', 'กลุ่ม'], array_map(fn($x) => $x->title, $questions)),
                    'rows' => $rows,
                    'widths' => array_merge([14, 8, 14], array_fill(0, count($questions), 40)),
                ],
            ]);
        }

        return $this->render('informant', [
            'project' => $project,
            'questions' => $questions,
            'informants' => $informants,
            'matrix' => $matrix,
        ]);
    }

    /**
     * สถิติคำถามแบบเลือกตอบ/ใช่-ไม่ใช่ (แทน report2, chartpie)
     */
    public function actionStats($projectId)
    {
        $project = $this->findProject($projectId);
        $questions = array_filter($this->questions($project), fn($x) => $x->answer_type !== Question::TYPE_TEXT);

        $charts = [];
        foreach ($questions as $question) {
            $rows = Response::find()->alias('r')
                ->innerJoin(['i' => Informant::tableName()], 'i.id = r.informant_id AND i.is_deleted = 0')
                ->select(['r.option_id', 'r.value_text', 'n' => 'COUNT(*)'])
                ->where(['r.question_id' => $question->id, 'r.is_deleted' => 0])
                ->groupBy(['r.option_id', 'r.value_text'])
                ->asArray()->all();
            $labels = $values = [];
            if ($question->answer_type === Question::TYPE_CHOICE) {
                $counts = array_column($rows, 'n', 'option_id');
                foreach ($question->options as $o) {
                    $labels[] = $o->label;
                    $values[] = (int)($counts[$o->id] ?? 0);
                }
            } else {
                $counts = array_column($rows, 'n', 'value_text');
                $labels = ['ใช่', 'ไม่ใช่'];
                $values = [(int)($counts['1'] ?? 0), (int)($counts['0'] ?? 0)];
            }
            $charts[] = ['question' => $question, 'labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
        }

        // กลุ่มอายุ
        $ages = Informant::find()->select('age')
            ->where(['project_id' => $project->id, 'is_deleted' => 0])->andWhere(['not', ['age' => null]])->column();
        $ageBins = [];
        foreach ($ages as $a) {
            $bin = (int)floor($a / 10) * 10;
            $key = $bin . '–' . ($bin + 9) . ' ปี';
            $ageBins[$bin] = ['label' => $key, 'n' => ($ageBins[$bin]['n'] ?? 0) + 1];
        }
        ksort($ageBins);

        return $this->render('stats', ['project' => $project, 'charts' => $charts, 'ageBins' => array_values($ageBins)]);
    }

    /**
     * แผนผังความคิด/Typology (แทน tree, treedata1-6, data1-6)
     */
    public function actionMindmap($projectId, $mode = 'themes', $round = 1)
    {
        $project = $this->findProject($projectId);
        return $this->render('mindmap', [
            'project' => $project,
            'mode' => in_array($mode, ['answers', 'interpretations', 'themes'], true) ? $mode : 'themes',
            'round' => max(1, min(4, (int)$round)),
        ]);
    }

    public function actionTreeData($projectId, $mode = 'themes', $round = 1)
    {
        Yii::$app->response->format = WebResponse::FORMAT_JSON;
        $project = $this->findProject($projectId);
        $round = max(1, min(4, (int)$round));
        $root = ['name' => $project->title, 'children' => []];

        if ($mode === 'answers') {
            foreach ($this->questions($project, Question::CATEGORY_RESEARCH) as $question) {
                $responses = Response::find()->alias('r')->innerJoinWith('informant inf')->with('option')
                    ->where(['r.question_id' => $question->id, 'r.is_deleted' => 0, 'inf.is_deleted' => 0])->all();
                $grouped = [];
                foreach ($responses as $r) {
                    $r->populateRelation('question', $question);
                    $grouped[$r->getDisplayValue()][] = $r->informant->code;
                }
                $root['children'][] = [
                    'name' => $question->title,
                    'children' => array_map(fn($k, $v) => ['name' => $k, 'count' => count($v)], array_keys($grouped), $grouped),
                ];
            }
        } else {
            foreach ($this->themeData($project, $round) as $t) {
                $root['children'][] = [
                    'name' => $t['question']->title,
                    'children' => array_map(fn($th) => $mode === 'themes'
                        ? ['name' => $th['text'], 'count' => $th['count'],
                            'children' => array_map(fn($c) => ['name' => $c], $th['informants'])]
                        : ['name' => $th['text'], 'count' => $th['count']], $t['themes']),
                ];
            }
        }
        return $root;
    }

    /**
     * Word cloud จากประเด็น (แทน wordcloud)
     */
    public function actionWordcloud($projectId, $questionId = null, $round = 1)
    {
        $project = $this->findProject($projectId);
        $round = max(1, min(4, (int)$round));
        $questions = $this->questions($project, Question::CATEGORY_RESEARCH);
        $ids = $questionId ? [(int)$questionId] : ArrayHelper::getColumn($questions, 'id');

        $texts = Interpretation::find()->alias('i')
            ->select('i.text')
            ->leftJoin(['r' => Response::tableName()], 'r.id = i.response_id')
            ->where(['i.question_id' => $ids, 'i.round' => $round, 'i.is_deleted' => 0])
            ->andWhere(['or', ['i.response_id' => null], ['r.is_deleted' => 0]])
            ->column();

        return $this->render('wordcloud', [
            'project' => $project,
            'questions' => $questions,
            'questionId' => $questionId,
            'round' => $round,
            'texts' => $texts,
        ]);
    }

    /**
     * สรุป Input-Process-Output (แทน report4, report5)
     */
    public function actionIpo($projectId, $round = 4)
    {
        $project = $this->findProject($projectId);
        $round = max(1, min(4, (int)$round));
        $ids = Question::find()->select('id')->where(['project_id' => $project->id, 'is_deleted' => 0])->column();
        $items = Interpretation::find()
            ->where(['question_id' => $ids, 'round' => $round, 'is_deleted' => 0])
            ->andWhere(['not', ['ipo' => null]])
            ->orderBy('id')->all();
        $ipo = ['input' => [], 'process' => [], 'output' => []];
        foreach ($items as $it) {
            $ipo[$it->ipo][$it->text] = ($ipo[$it->ipo][$it->text] ?? 0) + 1;
        }
        $concepts = Concept::find()->where(['project_id' => $project->id])->orderBy('id')->all();

        return $this->render('ipo', ['project' => $project, 'round' => $round, 'ipo' => $ipo, 'concepts' => $concepts]);
    }

    // ------------------------------------------------------------------

    /** @return Question[] */
    protected function questions(Project $project, ?string $category = null): array
    {
        return Question::find()->with('options')
            ->where(['project_id' => $project->id, 'is_deleted' => 0])
            ->andFilterWhere(['category' => $category])
            ->orderBy(['category' => SORT_DESC, 'sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /**
     * @return array<int,array{question:Question,themes:array<int,array{text:string,count:int,informants:string[]}>}>
     */
    protected function themeData(Project $project, int $round): array
    {
        $out = [];
        foreach ($this->questions($project, Question::CATEGORY_RESEARCH) as $question) {
            $rows = (new \yii\db\Query())
                ->select(['i.text', 'inf.code'])
                ->from(['i' => Interpretation::tableName()])
                ->leftJoin(['r' => Response::tableName()], 'r.id = i.response_id')
                ->leftJoin(['inf' => Informant::tableName()], 'inf.id = r.informant_id')
                ->where(['i.question_id' => $question->id, 'i.round' => $round, 'i.is_deleted' => 0])
                ->andWhere(['or', ['i.response_id' => null], ['and', ['r.is_deleted' => 0], ['inf.is_deleted' => 0]]])
                ->orderBy(['i.id' => SORT_ASC])
                ->all();
            $themes = [];
            foreach ($rows as $row) {
                $key = preg_replace('/\s+/u', ' ', trim($row['text']));
                if (!isset($themes[$key])) {
                    $themes[$key] = ['text' => $key, 'count' => 0, 'informants' => []];
                }
                $themes[$key]['count']++;
                if ($row['code'] !== null && !in_array($row['code'], $themes[$key]['informants'], true)) {
                    $themes[$key]['informants'][] = $row['code'];
                }
            }
            usort($themes, fn($a, $b) => $b['count'] <=> $a['count']);
            $out[] = ['question' => $question, 'themes' => $themes];
        }
        return $out;
    }

    protected function exportByQuestion(Project $project, array $data, int $round)
    {
        $sheets = [];
        foreach ($data as $i => $block) {
            /** @var Question $question */
            $question = $block['question'];
            $header = ['รหัสผู้ให้ข้อมูล', 'ข้อมูลที่ได้'];
            for ($r = 1; $r <= $round; $r++) {
                $header[] = Interpretation::roundOptions()[$r];
            }
            $rows = [];
            foreach ($block['responses'] as $resp) {
                $row = [$resp->informant->code, $resp->getDisplayValue()];
                for ($r = 1; $r <= $round; $r++) {
                    $items = array_filter($resp->interpretations, fn($x) => (int)$x->round === $r);
                    $row[] = implode("\n", array_map(
                        fn($x) => '• ' . $x->text . ($x->ipo ? " [{$x->ipo}]" : ''), $items));
                }
                $rows[] = $row;
            }
            $sheets[($i + 1) . '. ' . $question->title] = [
                'title' => $question->title,
                'header' => $header,
                'rows' => $rows,
                'widths' => array_merge([16, 70], array_fill(0, $round, 40)),
            ];
        }
        if (!$sheets) {
            throw new NotFoundHttpException('ไม่มีข้อมูล');
        }
        return ExcelExporter::send('report-' . $project->id, $sheets);
    }
}
