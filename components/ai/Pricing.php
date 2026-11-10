<?php

namespace app\components\ai;

use Yii;

/**
 * แปลงค่าใช้จ่าย AI (USD) เป็นเครดิต
 *
 *   เครดิต = ceil(ค่าใช้จ่าย USD × ai.usdToThb × ai.markup ÷ ai.bahtPerCredit)
 *
 * ค่าทั้งหมดตั้งใน config/params.php (ปรับใน params-local.php ได้)
 */
final class Pricing
{
    /** ราคา USD ต่อ 1 ล้าน token [input, output] ของโมเดล */
    public static function prices(string $model): array
    {
        $prices = Yii::$app->params['ai.prices'] ?? [];
        if (!isset($prices[$model])) {
            throw new AiException("ยังไม่ได้ตั้งราคาโมเดล {$model} ใน ai.prices");
        }
        return $prices[$model];
    }

    public static function costUsd(string $model, int $inputTokens, int $outputTokens): float
    {
        [$in, $out] = self::prices($model);
        return ($inputTokens * $in + $outputTokens * $out) / 1_000_000;
    }

    public static function creditsForUsd(float $usd): int
    {
        $p = Yii::$app->params;
        $baht = $usd * (float)$p['ai.usdToThb'] * (float)$p['ai.markup'];
        return max(1, (int)ceil($baht / (float)$p['ai.bahtPerCredit'] - 1e-9));
    }

    /**
     * ประมาณเครดิตก่อนเรียก (ไว้กันยอด) — ภาษาไทยคิด ~1 token ต่อ 1.5 ตัวอักษร (เผื่อสูงไว้)
     */
    public static function estimateCredits(string $model, array $messages, int $expectedOutputTokens): int
    {
        $chars = 0;
        foreach ($messages as $m) {
            $chars += mb_strlen((string)$m['content']);
        }
        $inputTokens = (int)ceil($chars / 1.5) + 20 * count($messages);
        return self::creditsForUsd(self::costUsd($model, $inputTokens, $expectedOutputTokens));
    }

    public static function bahtPerCredit(): float
    {
        return (float)Yii::$app->params['ai.bahtPerCredit'];
    }
}
