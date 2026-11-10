<?php

namespace app\components\ai;

use app\models\CreditLedger;
use Yii;

/**
 * กระเป๋าเครดิตของผู้ใช้
 *
 * ทุกการเปลี่ยนยอดทำใน transaction และล็อกแถว credit_wallet (SELECT ... FOR UPDATE)
 * พร้อมบันทึก credit_ledger ทุกครั้ง ยอดคงเหลือไม่มีทางติดลบ
 *
 * การใช้ AI: hold() กันเครดิตตามยอดประมาณก่อนเรียก → settle() หักตามจริงหลังเรียก
 * (คืนส่วนที่กันไว้เกิน หรือหักเพิ่มเท่าที่ยอดคงเหลือมี) / ถ้าเรียกไม่สำเร็จ settle(..., 0) คืนทั้งหมด
 */
final class Credits
{
    public static function balance(int $userId): int
    {
        return (int)Yii::$app->db->createCommand('SELECT balance FROM {{%credit_wallet}} WHERE user_id = :u', [':u' => $userId])
            ->queryScalar();
    }

    /**
     * เติม (+) หรือหัก (-) เครดิตโดยผู้ดูแลระบบ — หักได้ไม่เกินยอดคงเหลือ
     */
    public static function grant(int $userId, int $amount, ?string $note, ?int $byUserId): int
    {
        if ($amount === 0) {
            throw new AiException('จำนวนเครดิตต้องไม่เป็น 0');
        }
        return self::change($userId, function (int $balance) use ($amount) {
            if ($balance + $amount < 0) {
                throw new AiException("หักได้ไม่เกินยอดคงเหลือ ({$balance} เครดิต)");
            }
            return $amount;
        }, CreditLedger::KIND_GRANT, null, $note, $byUserId);
    }

    /**
     * เครดิตต้อนรับผู้ใช้ใหม่ (params ai.welcomeCredits) — ได้ครั้งเดียวต่อบัญชี เรียกซ้ำได้ปลอดภัย
     * @return bool true = แจกครั้งนี้, false = เคยได้แล้วหรือปิดไว้
     */
    public static function welcome(int $userId): bool
    {
        $amount = (int)(Yii::$app->params['ai.welcomeCredits'] ?? 0);
        if ($amount <= 0) {
            return false;
        }
        $given = false;
        self::change($userId, function () use ($userId, $amount, &$given) {
            // ตรวจหลังล็อกแถว wallet แล้ว จึงไม่แจกซ้ำแม้มี request พร้อมกัน
            if (CreditLedger::find()->where(['user_id' => $userId, 'kind' => CreditLedger::KIND_WELCOME])->exists()) {
                return 0;
            }
            $given = true;
            return $amount;
        }, CreditLedger::KIND_WELCOME, null, 'เครดิตต้อนรับผู้ใช้ใหม่', null);
        return $given;
    }

    /**
     * กันเครดิตก่อนเรียก AI
     * @throws AiException เครดิตไม่พอ
     */
    public static function hold(int $userId, int $amount, int $jobId): void
    {
        self::change($userId, function (int $balance) use ($amount) {
            if ($balance < $amount) {
                throw new AiException("เครดิตไม่พอ ต้องใช้ประมาณ {$amount} เครดิต แต่คงเหลือ {$balance} เครดิต");
            }
            return -$amount;
        }, CreditLedger::KIND_USAGE, $jobId, 'กันเครดิตตามยอดประมาณ', $userId);
    }

    /**
     * ปิดยอดงาน AI: คืนส่วนเกินที่กันไว้ หรือหักเพิ่มถ้าใช้จริงเกินยอดประมาณ (หักได้ไม่เกินยอดคงเหลือ)
     * @return int เครดิตที่หักจริงทั้งหมดของงานนี้
     */
    public static function settle(int $userId, int $held, int $actual, int $jobId): int
    {
        $diff = $held - $actual;
        if ($diff === 0) {
            return $actual;
        }
        $charged = $actual;
        self::change($userId, function (int $balance) use ($diff, &$charged, $held) {
            if ($diff > 0) {
                return $diff;
            }
            $extra = min(-$diff, $balance);
            $charged = $held + $extra;
            return -$extra;
        }, $diff > 0 ? CreditLedger::KIND_REFUND : CreditLedger::KIND_USAGE, $jobId,
            $diff > 0 ? 'คืนส่วนที่กันไว้เกิน' : 'ใช้จริงเกินยอดประมาณ', $userId);
        return $charged;
    }

    /**
     * @param callable(int $balance): int $decide คืนค่า delta (โยน exception เพื่อยกเลิก)
     */
    private static function change(int $userId, callable $decide, string $kind, ?int $jobId, ?string $note, ?int $byUserId): int
    {
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            $db->createCommand('INSERT IGNORE INTO {{%credit_wallet}} (user_id, balance, updated_at) VALUES (:u, 0, :t)',
                [':u' => $userId, ':t' => time()])->execute();
            $balance = (int)$db->createCommand('SELECT balance FROM {{%credit_wallet}} WHERE user_id = :u FOR UPDATE',
                [':u' => $userId])->queryScalar();
            $delta = $decide($balance);
            if ($delta === 0) {
                $tx->commit();
                return $balance;
            }
            $after = $balance + $delta;
            $db->createCommand()->update('{{%credit_wallet}}', ['balance' => $after, 'updated_at' => time()], ['user_id' => $userId])->execute();
            $db->createCommand()->insert('{{%credit_ledger}}', [
                'user_id' => $userId,
                'delta' => $delta,
                'kind' => $kind,
                'balance_after' => $after,
                'ai_job_id' => $jobId,
                'note' => $note !== null ? mb_substr($note, 0, 255) : null,
                'created_by' => $byUserId,
                'created_at' => time(),
            ])->execute();
            $tx->commit();
            return $after;
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
