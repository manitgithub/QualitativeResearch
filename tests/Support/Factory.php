<?php

namespace app\tests\Support;

use app\models\Informant;
use app\models\Project;
use app\models\ProjectMember;
use app\models\Question;
use app\models\Response;
use app\models\User;
use Yii;

/**
 * สร้างข้อมูลทดสอบ และล้างฐานข้อมูลทดสอบก่อนแต่ละ test
 */
final class Factory
{
    private const TABLES = [
        'report_section', 'ai_suggestion', 'credit_ledger', 'ai_job', 'credit_wallet',
        'interpretation', 'response', 'informant', 'question_option', 'question', 'concept',
        'informant_group', 'project_member', 'project', 'login_log', 'user_auth', 'user',
    ];

    public static function reset(): void
    {
        $db = Yii::$app->db;
        if (!str_contains($db->dsn, '_test')) {
            throw new \RuntimeException('Refusing to reset a non-test database: ' . $db->dsn);
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach (self::TABLES as $t) {
            $db->createCommand()->truncateTable($t)->execute();
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
        if (Yii::$app->has('user', true) && Yii::$app->user instanceof \yii\web\User) {
            Yii::$app->user->logout(false);
        }
    }

    public static function user(array $attrs = []): User
    {
        static $n = 0;
        $n++;
        $u = new User(array_merge([
            'username' => "user{$n}",
            'first_name' => "ผู้ใช้{$n}",
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ], $attrs));
        if (!$u->save()) {
            throw new \RuntimeException('user: ' . json_encode($u->errors, JSON_UNESCAPED_UNICODE));
        }
        return $u;
    }

    public static function admin(array $attrs = []): User
    {
        return self::user(array_merge(['role' => User::ROLE_ADMIN], $attrs));
    }

    public static function project(User $owner, array $attrs = []): Project
    {
        $p = new Project(array_merge(['title' => 'โครงการทดสอบ', 'owner_id' => $owner->id], $attrs));
        if (!$p->save()) {
            throw new \RuntimeException('project: ' . json_encode($p->errors, JSON_UNESCAPED_UNICODE));
        }
        return $p;
    }

    public static function member(Project $p, User $u, string $role = ProjectMember::ROLE_MEMBER): void
    {
        (new ProjectMember(['project_id' => $p->id, 'user_id' => $u->id, 'role' => $role]))->save(false);
    }

    public static function question(Project $p, array $attrs = []): Question
    {
        $q = new Question(array_merge([
            'project_id' => $p->id,
            'category' => Question::CATEGORY_RESEARCH,
            'answer_type' => Question::TYPE_TEXT,
            'title' => 'คำถามทดสอบ',
        ], $attrs));
        if (!$q->save()) {
            throw new \RuntimeException('question: ' . json_encode($q->errors, JSON_UNESCAPED_UNICODE));
        }
        return $q;
    }

    public static function informant(Project $p, string $code = 'P01', array $attrs = []): Informant
    {
        $i = new Informant(array_merge(['project_id' => $p->id, 'code' => $code], $attrs));
        if (!$i->save()) {
            throw new \RuntimeException('informant: ' . json_encode($i->errors, JSON_UNESCAPED_UNICODE));
        }
        return $i;
    }

    public static function response(Informant $i, Question $q, ?string $text = 'คำตอบ', ?int $optionId = null): Response
    {
        $r = new Response(['informant_id' => $i->id, 'question_id' => $q->id, 'value_text' => $text, 'option_id' => $optionId]);
        $r->save(false);
        return $r;
    }
}
