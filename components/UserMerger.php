<?php

namespace app\components;

use app\models\User;
use app\models\UserAuth;
use Yii;
use yii\base\UserException;

/**
 * รวมบัญชีที่สมัครใหม่ด้วย Google/LINE (รออนุมัติ) เข้ากับบัญชีเดิมของคนเดียวกัน
 * ใช้ตอนผู้ดูแลอนุมัติ: ผู้ใช้เดิมจากระบบเก่าไม่มีอีเมล จึงจับคู่อัตโนมัติไม่ได้
 *
 * ย้ายการผูก Google/LINE, ประวัติเข้าระบบ และข้อมูลทุกอย่างที่บัญชีใหม่สร้างไว้ ไปที่บัญชีเดิม
 * แล้วลบบัญชีใหม่ทิ้ง บัญชีเดิมถูกเปิดใช้งาน
 */
class UserMerger
{
    /** ตารางและคอลัมน์ที่อ้างถึงผู้ใช้ (นอกจาก user_auth / project_member ที่ต้องจัดการเอง) */
    private const REFERENCES = [
        ['login_log', 'user_id'],
        ['project', 'owner_id'],
        ['question', 'created_by'],
        ['informant', 'created_by'],
        ['response', 'created_by'],
        ['interpretation', 'created_by'],
    ];

    public function merge(User $from, User $into): User
    {
        if ((int)$from->id === (int)$into->id) {
            throw new UserException('เลือกบัญชีเดียวกัน');
        }
        $fromSources = UserAuth::find()->select('source')->where(['user_id' => $from->id])->column();
        $clash = UserAuth::find()->where(['user_id' => $into->id, 'source' => $fromSources])->one();
        if ($clash) {
            $label = UserAuth::sourceLabels()[$clash->source] ?? $clash->source;
            throw new UserException("บัญชีปลายทางผูก {$label} ไว้แล้ว (คนละบัญชีกัน) — ยกเลิกการผูกเดิมก่อน");
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            $db->createCommand()->update('{{%user_auth}}', ['user_id' => $into->id], ['user_id' => $from->id])->execute();
            foreach (self::REFERENCES as [$table, $col]) {
                $db->createCommand()->update('{{%' . $table . '}}', [$col => $into->id], [$col => $from->id])->execute();
            }
            // สมาชิกโครงการ: ย้ายเฉพาะโครงการที่บัญชีเดิมยังไม่เป็นสมาชิก
            $db->createCommand(
                'UPDATE {{%project_member}} SET user_id = :into WHERE user_id = :from AND project_id NOT IN '
                . '(SELECT project_id FROM (SELECT project_id FROM {{%project_member}} WHERE user_id = :into2) t)',
                [':into' => $into->id, ':from' => $from->id, ':into2' => $into->id]
            )->execute();
            $db->createCommand()->delete('{{%project_member}}', ['user_id' => $from->id])->execute();

            $patch = ['status' => User::STATUS_ACTIVE, 'updated_at' => time()];
            if (!$into->email && $from->email) {
                $patch['email'] = $from->email;
            }
            if (!$into->avatar_url && $from->avatar_url) {
                $patch['avatar_url'] = $from->avatar_url;
            }
            if ($from->last_login_at > $into->last_login_at) {
                $patch['last_login_at'] = $from->last_login_at;
            }
            // ลบบัญชีใหม่ก่อน เพื่อให้อีเมลไม่ชน unique index ตอนย้ายไปบัญชีเดิม
            $db->createCommand()->delete('{{%user}}', ['id' => $from->id])->execute();
            $db->createCommand()->update('{{%user}}', $patch, ['id' => $into->id])->execute();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        $into->refresh();
        return $into;
    }

    /**
     * บัญชีเดิมที่น่าจะเป็นคนเดียวกับบัญชีใหม่ (ชื่อคล้ายกัน) เรียงจากคล้ายที่สุด
     *
     * @return array<int,string> user id => ชื่อที่แสดง
     */
    public static function suggestions(User $pending, int $limit = 5): array
    {
        $needle = self::normalize($pending->getFullName() . ' ' . $pending->email);
        if ($needle === '') {
            return [];
        }
        $scores = [];
        foreach (User::find()->where(['<>', 'id', $pending->id])->andWhere(['<>', 'status', User::STATUS_PENDING])->all() as $u) {
            $hay = self::normalize($u->getFullName() . ' ' . $u->username . ' ' . $u->email);
            similar_text($needle, $hay, $pct);
            if (str_contains($hay, self::normalize($pending->first_name)) && mb_strlen($pending->first_name) >= 3) {
                $pct += 40;
            }
            if ($pct >= 45) {
                $scores[$u->id] = $pct;
            }
        }
        arsort($scores);
        $out = [];
        foreach (array_slice($scores, 0, $limit, true) as $id => $_) {
            $u = User::findOne($id);
            $out[$id] = $u->getFullName() . ($u->username ? " ({$u->username})" : '');
        }
        return $out;
    }

    private static function normalize(string $s): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower($s));
    }
}
