<?php

namespace tests\unit\components;

use app\components\ExcelExporter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;

class ExcelExporterTest extends \Codeception\Test\Unit
{
    public function testWritesSheetsAndNeutralisesFormulas()
    {
        $response = ExcelExporter::send('t', [
            "ชื่อ/ที่ผิด:กฎ*\tแท็บ" => ['title' => 'หัวรายงาน', 'header' => ['ก', 'ข'], 'rows' => [['=1+1', 5], ['@cmd', null]]],
            'ซ้ำ' => ['header' => ['a'], 'rows' => [['x']]],
        ]);
        [$handle] = $response->stream;
        $path = stream_get_meta_data($handle)['uri'];
        $ss = IOFactory::load($path);

        verify($ss->getSheetCount())->equals(2);
        $s = $ss->getSheet(0);
        verify($s->getTitle())->stringNotContainsString('/');
        verify($s->getTitle())->stringNotContainsString("\t");
        verify($s->getCell('A1')->getValue())->equals('หัวรายงาน');
        verify($s->getCell('A3')->getValue())->equals('ก');
        verify($s->getCell('A4')->getDataType())->equals(DataType::TYPE_STRING);   // not a formula
        verify($s->getCell('A4')->getValue())->equals('=1+1');
        verify($s->getCell('B4')->getValue())->equals(5);
        verify($ss->getSheet(1)->getCell('A2')->getValue())->equals('x');   // no title -> header on row 1

        fclose($handle);
        Yii::$app->response->clear();
        @unlink($path);
    }
}
