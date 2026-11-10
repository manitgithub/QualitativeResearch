<?php

namespace app\models\forms;

use app\models\Informant;
use app\models\Project;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * นำเข้าข้อมูลสัมภาษณ์จาก Excel (.xlsx/.xls) หรือ CSV
 *
 * รูปแบบไฟล์ (ดาวน์โหลดแม่แบบได้จากระบบ):
 *   แถว 1   : หัวคอลัมน์  | รหัสผู้ให้ข้อมูล | อายุ | #12 คำถามข้อ... | #15 คำถามข้อ... |
 *   แถว 2.. : ข้อมูล
 * คอลัมน์คำถามจับคู่ด้วย "#<id>" ที่ต้นหัวคอลัมน์ หรือข้อความคำถามที่ตรงกันทุกตัวอักษร
 * ถ้ารหัสผู้ให้ข้อมูลมีอยู่แล้ว จะเติม/แทนที่คำตอบของคนเดิม
 */
class ImportForm extends Model
{
    /** @var UploadedFile|null */
    public $file;
    public $overwrite = false;

    public string $summary = '';

    public function __construct(private Project $project, $config = [])
    {
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            ['file', 'file', 'skipOnEmpty' => false, 'extensions' => ['xlsx', 'xls', 'csv'],
                'checkExtensionByMimeType' => false, 'maxSize' => 20 * 1024 * 1024],
            ['overwrite', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return ['file' => 'ไฟล์ Excel หรือ CSV', 'overwrite' => 'แทนที่คำตอบเดิม (ถ้ามี)'];
    }

    public function import(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        try {
            $rows = $this->readRows($this->file->tempName, strtolower($this->file->extension));
        } catch (\Throwable $e) {
            $this->addError('file', 'อ่านไฟล์ไม่ได้: ' . $e->getMessage());
            return false;
        }
        if (count($rows) < 2) {
            $this->addError('file', 'ไม่พบข้อมูล (ต้องมีหัวคอลัมน์และข้อมูลอย่างน้อย 1 แถว)');
            return false;
        }

        $header = array_map(fn($h) => trim((string)$h), array_shift($rows));
        [$columns, $ageCol, $unmatched] = $this->mapColumns($header);
        if (!$columns) {
            $this->addError('file', 'ไม่พบคอลัมน์ที่ตรงกับคำถามในโครงการ (หัวคอลัมน์ควรขึ้นต้นด้วย #รหัสคำถาม)');
            return false;
        }

        $created = $updated = $answers = 0;
        $tx = Yii::$app->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $code = trim((string)($row[0] ?? ''));
                if ($code === '') {
                    continue;
                }
                $informant = Informant::findOne(['project_id' => $this->project->id, 'code' => $code, 'is_deleted' => 0]);
                if (!$informant) {
                    $informant = new Informant([
                        'project_id' => $this->project->id,
                        'code' => $code,
                        'interviewed_on' => date('Y-m-d'),
                    ]);
                    $created++;
                } else {
                    $updated++;
                }
                if ($ageCol !== null && is_numeric($row[$ageCol] ?? null)) {
                    $informant->age = (int)$row[$ageCol];
                }
                $informant->save(false);

                foreach ($columns as $col => $question) {
                    $raw = trim((string)($row[$col] ?? ''));
                    if ($raw === '') {
                        continue;
                    }
                    $existing = Response::findOne(['informant_id' => $informant->id, 'question_id' => $question->id, 'is_deleted' => 0]);
                    if ($existing && !$this->overwrite) {
                        continue;
                    }
                    $r = $existing ?: new Response(['informant_id' => $informant->id, 'question_id' => $question->id]);
                    [$r->value_text, $r->option_id] = $this->convert($question, $raw);
                    $r->save(false);
                    $answers++;
                }
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e, __METHOD__);
            $this->addError('file', 'นำเข้าไม่สำเร็จ: ' . $e->getMessage());
            return false;
        }

        $this->summary = "นำเข้าแล้ว: ผู้ให้ข้อมูลใหม่ {$created} คน, ปรับปรุง {$updated} คน, คำตอบ {$answers} รายการ";
        if ($unmatched) {
            $this->summary .= ' (ข้ามคอลัมน์ที่ไม่ตรงกับคำถาม: ' . implode(', ', array_slice($unmatched, 0, 5)) . ')';
        }
        return true;
    }

    /**
     * @return array{0:array<int,Question>,1:?int,2:string[]}
     */
    private function mapColumns(array $header): array
    {
        $questions = Question::find()->with('options')
            ->where(['project_id' => $this->project->id, 'is_deleted' => 0])->indexBy('id')->all();
        $byTitle = [];
        foreach ($questions as $q) {
            $byTitle[$this->normalizeTitle($q->title)] = $q;
        }

        $columns = [];
        $ageCol = null;
        $unmatched = [];
        foreach ($header as $i => $h) {
            if ($i === 0 || $h === '') {
                continue;
            }
            if (in_array(mb_strtolower($h), ['อายุ', 'age'], true)) {
                $ageCol = $i;
                continue;
            }
            if (preg_match('/^#(\d+)/', $h, $m) && isset($questions[(int)$m[1]])) {
                $columns[$i] = $questions[(int)$m[1]];
            } elseif (isset($byTitle[$this->normalizeTitle($h)])) {
                $columns[$i] = $byTitle[$this->normalizeTitle($h)];
            } else {
                $unmatched[] = $h;
            }
        }
        return [$columns, $ageCol, $unmatched];
    }

    private function normalizeTitle(string $s): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower($s));
    }

    /**
     * @return array{0:?string,1:?int}
     */
    private function convert(Question $q, string $raw): array
    {
        if ($q->answer_type === Question::TYPE_BOOLEAN) {
            $yes = in_array(mb_strtolower($raw), ['1', 'ใช่', 'yes', 'y', 'true', 'on', 'มี'], true);
            return [$yes ? '1' : '0', null];
        }
        if ($q->answer_type === Question::TYPE_CHOICE) {
            foreach ($q->options as $o) {
                if ($this->normalizeTitle($o->label) === $this->normalizeTitle($raw)) {
                    return [null, $o->id];
                }
            }
            // ตัวเลือกใหม่ที่ยังไม่มี: เพิ่มให้อัตโนมัติ
            $opt = new QuestionOption([
                'question_id' => $q->id,
                'label' => $raw,
                'sort_order' => count($q->options) + 1,
            ]);
            $opt->save(false);
            $q->populateRelation('options', array_merge($q->options, [$opt]));
            return [null, $opt->id];
        }
        return [$raw, null];
    }

    private function readRows(string $path, string $ext): array
    {
        if ($ext === 'csv') {
            $content = file_get_contents($path);
            // รองรับไฟล์ CSV ที่บันทึกจาก Excel ภาษาไทย (TIS-620/Windows-874)
            // (mbstring ไม่รองรับ Windows-874 จึงใช้ iconv)
            if (!mb_check_encoding($content, 'UTF-8')) {
                $converted = @iconv('CP874', 'UTF-8//IGNORE', $content);
                if ($converted === false) {
                    throw new \RuntimeException('ไฟล์ไม่ใช่ UTF-8 และแปลงจาก Windows-874 ไม่ได้ กรุณาบันทึกเป็น CSV UTF-8');
                }
                $content = $converted;
            }
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $rows = [];
            $fh = fopen('php://temp', 'r+');
            fwrite($fh, $content);
            rewind($fh);
            while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
                $rows[] = $r;
            }
            fclose($fh);
            return $rows;
        }
        $sheet = IOFactory::load($path)->getActiveSheet();
        return $sheet->toArray(null, true, false, false);
    }

    /**
     * สร้างไฟล์แม่แบบ .xlsx ที่มีหัวคอลัมน์ตรงกับคำถามในโครงการ
     */
    public function buildTemplate(): string
    {
        $questions = Question::find()->where(['project_id' => $this->project->id, 'is_deleted' => 0])
            ->orderBy(['category' => SORT_ASC, 'sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('ข้อมูล');
        $headers = ['รหัสผู้ให้ข้อมูล', 'อายุ'];
        foreach ($questions as $q) {
            $headers[] = '#' . $q->id . ' ' . mb_substr(preg_replace('/\s+/u', ' ', $q->title), 0, 80);
        }
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setWidth($c === 1 ? 18 : 30);
        }

        $help = $ss->createSheet();
        $help->setTitle('คำอธิบาย');
        $help->fromArray([['คอลัมน์', 'รูปแบบคำตอบ', 'ตัวเลือก']], null, 'A1');
        $r = 2;
        foreach ($questions as $q) {
            $help->fromArray([[
                '#' . $q->id,
                $q->getTypeLabel(),
                $q->answer_type === Question::TYPE_CHOICE ? implode(' / ', array_map(fn($o) => $o->label, $q->options))
                    : ($q->answer_type === Question::TYPE_BOOLEAN ? 'ใช่ / ไม่ใช่' : ''),
            ]], null, 'A' . $r++);
        }

        $path = Yii::getAlias('@runtime') . '/tpl-' . Yii::$app->security->generateRandomString(8) . '.xlsx';
        IOFactory::createWriter($ss, 'Xlsx')->save($path);
        return $path;
    }
}
