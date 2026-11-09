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
            $lastCol = $sheet->getHighestColumn();
            $sheet->getStyle("A{$headerRow}:{$lastCol}" . ($row - 1))
                ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->freezePane('A' . ($headerRow + 1));
        }
        $ss->setActiveSheetIndex(0);

        $path = Yii::getAlias('@runtime') . '/export-' . Yii::$app->security->generateRandomString(8) . '.xlsx';
        IOFactory::createWriter($ss, 'Xlsx')->save($path);

        $response = Yii::$app->response->sendFile($path, $filename . '.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, fn() => @unlink($path));
        return $response;
    }

    private static function safeSheetName(string $name, array &$used): string
    {
        $name = trim(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $name)) ?: 'Sheet';
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
