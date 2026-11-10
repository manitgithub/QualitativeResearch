<?php

namespace tests\unit\models;

use app\models\forms\ImportForm;
use app\models\Informant;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use app\tests\Support\Factory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;
use yii\web\UploadedFile;

class ImportTest extends \Codeception\Test\Unit
{
    private $project;
    private $qText;
    private $qChoice;
    private $qBool;

    protected function _before()
    {
        Factory::reset();
        $owner = Factory::user();
        Yii::$app->user->login($owner);
        $this->project = Factory::project($owner);
        $this->qText = Factory::question($this->project, ['title' => 'บทบาทในชุมชน']);
        $this->qChoice = Factory::question($this->project, ['answer_type' => Question::TYPE_CHOICE, 'title' => 'เพศ', 'optionsText' => "ชาย\nหญิง"]);
        $this->qBool = Factory::question($this->project, ['answer_type' => Question::TYPE_BOOLEAN, 'title' => 'อบรม']);
    }

    private function import(string $content, string $ext = 'csv', bool $overwrite = false): ImportForm
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $content);
        $f = new ImportForm($this->project);
        $f->overwrite = $overwrite;
        $f->file = new UploadedFile(['name' => "x.$ext", 'tempName' => $path, 'type' => 'text/csv', 'size' => strlen($content), 'error' => UPLOAD_ERR_OK]);
        $f->import();
        return $f;
    }

    public function testCsvImportMatchesByIdAndTitle()
    {
        $csv = "รหัส,อายุ,#{$this->qChoice->id} เพศ,บทบาท ในชุมชน,#{$this->qBool->id}\n"
            . "A1,30,หญิง,ผู้นำ,ใช่\nA2,,อื่นๆ,=1+1,no\n,,,,\n";
        $f = $this->import($csv);
        verify($f->hasErrors())->false();
        verify($f->summary)->stringContainsString('ผู้ให้ข้อมูลใหม่ 2 คน');

        $a1 = Informant::findOne(['code' => 'A1']);
        verify($a1->age)->equals(30);
        $r = Response::find()->where(['informant_id' => $a1->id])->indexBy('question_id')->all();
        verify($r[$this->qText->id]->value_text)->equals('ผู้นำ');            // matched by title ignoring spaces
        verify($r[$this->qBool->id]->value_text)->equals('1');
        verify(QuestionOption::findOne($r[$this->qChoice->id]->option_id)->label)->equals('หญิง');

        // unknown choice is added as a new option
        verify(QuestionOption::find()->where(['question_id' => $this->qChoice->id, 'label' => 'อื่นๆ'])->exists())->true();
    }

    public function testWindows874CsvFromThaiExcel()
    {
        $csv = "code,#{$this->qText->id}\nB1,ทดสอบภาษาไทย\n";
        $f = $this->import(iconv('UTF-8', 'CP874', $csv));
        verify($f->hasErrors())->false();
        verify(Response::find()->select('value_text')->scalar())->equals('ทดสอบภาษาไทย');
    }

    public function testExistingAnswersKeptUnlessOverwrite()
    {
        $this->import("code,#{$this->qText->id}\nC1,เดิม\n");
        $this->import("code,#{$this->qText->id}\nC1,ใหม่\n");
        verify(Response::find()->select('value_text')->scalar())->equals('เดิม');
        $f = $this->import("code,#{$this->qText->id}\nC1,ใหม่\n", 'csv', true);
        verify($f->summary)->stringContainsString('ปรับปรุง 1 คน');
        verify(Response::find()->select('value_text')->scalar())->equals('ใหม่');
        verify(Informant::find()->count())->equals(1);
    }

    public function testRejectsFileWithoutMatchingColumns()
    {
        $f = $this->import("code,unknown\nD1,x\n");
        verify($f->hasErrors('file'))->true();
        verify(Informant::find()->count())->equals(0);
    }

    public function testTemplateRoundTrip()
    {
        $path = (new ImportForm($this->project))->buildTemplate();
        $sheet = IOFactory::load($path)->getActiveSheet();
        $sheet->fromArray(['E1', 25, 'คำตอบจากแม่แบบ', 'ชาย', 'ไม่ใช่'], null, 'A2');
        IOFactory::createWriter($sheet->getParent(), 'Xlsx')->save($path);
        $f = $this->import(file_get_contents($path), 'xlsx');
        @unlink($path);
        verify($f->hasErrors())->false();
        verify(Response::find()->count())->equals(3);
    }
}
