<?php

namespace app\commands;

use app\components\ai\Credits;
use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * จัดการเครดิต AI จาก command line
 */
class CreditsController extends Controller
{
    /**
     * แจกเครดิตต้อนรับ (params ai.welcomeCredits) ให้ผู้ใช้ทุกคนที่ใช้งานอยู่และยังไม่เคยได้
     *
     *   ./yii credits/welcome-existing
     *
     * รันซ้ำได้ปลอดภัย — คนที่ได้แล้วจะไม่ได้ซ้ำ (ผู้ใช้ที่รออนุมัติจะได้ตอนอนุมัติ)
     */
    public function actionWelcomeExisting(): int
    {
        $given = 0;
        $total = 0;
        foreach (User::find()->select('id')->where(['status' => User::STATUS_ACTIVE])->orderBy('id')->column() as $id) {
            $total++;
            if (Credits::welcome((int)$id)) {
                $given++;
            }
        }
        $this->stdout("แจกเครดิตต้อนรับให้ {$given} คน จากผู้ใช้ที่ใช้งานอยู่ {$total} คน\n");
        return ExitCode::OK;
    }
}
