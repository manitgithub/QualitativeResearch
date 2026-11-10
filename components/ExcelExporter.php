<?php

namespace app\components;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Yii;

/**
 * สร้างไฟล์ .xlsx จากหลาย sheet แล้วส่งให้ดาวน์โหลด
 *
 * $sheets = ['ชื่อ sheet' => ['title' => 'หัวรายงาน', 'header' => [...], 'rows' => [[...], ...], 'widths' => [...]]]
 */
class ExcelExporter
{
    public static function send(string $filename, array $sheets): \yii\web\Response
    {
        // รายงานของโครงการใหญ่ (ข้อความสัมภาษณ์ยาวหลายพันแถว) ใช้หน่วยความจำมากกว่าค่าเริ่มต้น 128M
        if (self::memoryLimitBytes() < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
        $ss = new Spreadsheet();
        $ss->removeSheetByIndex(0);

        $used = [];
        foreach ($sheets as $name => $def) {
            $sheet = $ss->createSheet();
            $sheet->setTitle(self::safeSheetName((string)$name, $used));
            $row = 1;
            if (!empty($def['title'])) {
                $sheet->setCellValue('A1', $def['title']);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $row = 3;
            }
            $sheet->fromArray($def['header'], null, 'A' . $row);
            $sheet->getStyle($row . ':' . $row)->getFont()->setBold(true);
            $headerRow = $row;
            $row++;
            foreach ($def['rows'] as $r) {
                foreach (array_values($r) as $c => $v) {
                    $cell = Coordinate::stringFromColumnIndex($c + 1) . $row;
                    if (is_int($v) || is_float($v)) {
                        $sheet->setCellValue($cell, $v);
                    } elseif ($v !== null && $v !== '') {
                        // บันทึกเป็นข้อความเสมอ ป้องกันข้อความขึ้นต้นด้วย = + - @ ถูกตีความเป็นสูตร
                        $sheet->setCellValueExplicit($cell, (string)$v, DataType::TYPE_STRING);
                    }
                }
                $row++;
            }
            foreach (array_values($def['widths'] ?? []) as $i => $w) {
                $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
            }
            // ตั้งรูปแบบทั้งคอลัมน์ (ไม่ใช่ทีละเซลล์) เพื่อประหยัดหน่วยความจำเมื่อข้อมูลมาก
            $lastCol = $sheet->getHighestColumn();
            $sheet->getStyle("A:{$lastCol}")
                ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->freezePane('A' . ($headerRow + 1));
        }
        $ss->setActiveSheetIndex(0);

        $path = Yii::getAlias('@runtime') . '/export-' . Yii::$app->security->generateRandomString(8) . '.xlsx';
        $writer = IOFactory::createWriter($ss, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $ss->disconnectWorksheets();

        $response = Yii::$app->response->sendFile($path, $filename . '.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, fn() => @unlink($path));
        return $response;
    }

    private static function memoryLimitBytes(): int
    {
        $v = trim((string)ini_get('memory_limit'));
        if ($v === '-1') {
            return PHP_INT_MAX;
        }
        $n = (int)$v;
        return match (strtolower(substr($v, -1))) { 'g' => $n << 30, 'm' => $n << 20, 'k' => $n << 10, default => $n };
    }

    private static function safeSheetName(string $name, array &$used): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name)) ?: 'Sheet';   // tabs/newlines from question titles
        $name = mb_substr($name, 0, 28);
        $base = $name;
        $i = 2;
        while (isset($used[$name])) {
            $name = mb_substr($base, 0, 25) . ' ' . $i++;
        }
        $used[$name] = true;
        return $name;
    }
}
