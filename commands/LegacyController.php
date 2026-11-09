<?php

namespace app\commands;

use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\Console;

/**
 * ย้ายข้อมูลจากระบบเดิม (Yii1, ตาราง dt_*) เข้าโครงสร้างใหม่
 *
 * ขั้นตอน:
 *   1. นำเข้า SQL dump ของระบบเดิมลงฐานข้อมูลแยก เช่น
 *        mysql -uroot -e "CREATE DATABASE nurseapp_legacy CHARACTER SET utf8mb4"
 *        mysql -uroot nurseapp_legacy < nurseapp.sql
 *   2. ตั้งค่า params['legacyDb'] ใน config/params-local.php
 *   3. ./yii legacy/check     ตรวจว่ามีตารางครบหรือไม่
 *   4. ./yii legacy/import    นำเข้า (รันซ้ำได้ ข้ามรายการที่เคยนำเข้าแล้วด้วย legacy_id)
 *      ./yii legacy/import --fresh=1   ลบข้อมูลวิจัยที่เคยนำเข้าแล้วนำเข้าใหม่ (บัญชีผู้ใช้คงไว้)
 */
class LegacyController extends Controller
{
    /** @var bool ลบข้อมูลที่มาจากระบบเดิม (legacy_id ไม่ว่าง) ก่อนนำเข้า */
    public $fresh = false;

    private const TABLES = [
        'dt_userkey', 'dt_main', 'dt_join', 'dt_information', 'dt_group', 'dt_qution',
        'dt_anwer', 'dt_people', 'dt_interview', 'dt_interpret', 'dt_conceptual', 'dt_log',
    ];

    private Connection $old;
    private Connection $db;
    private array $warnings = [];

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'import' ? ['fresh'] : []);
    }

    public function init()
    {
        parent::init();
        $this->old = Yii::$app->get('legacyDb');
        $this->db = Yii::$app->db;
    }

    /**
     * ตรวจตารางและจำนวนแถวในฐานข้อมูลเดิม
     */
    public function actionCheck(): int
    {
        $existing = $this->old->schema->getTableNames();
        foreach (self::TABLES as $t) {
            if (in_array($t, $existing, true)) {
                $n = (new Query())->from($t)->count('*', $this->old);
                $this->stdout(sprintf("  %-16s %8d แถว\n", $t, $n), Console::FG_GREEN);
            } else {
                $this->stdout(sprintf("  %-16s ไม่พบ\n", $t), Console::FG_RED);
            }
        }
        if (in_array('dt_interpret', $existing, true) && in_array('dt_interview', $existing, true)) {
            $orphans = (new Query())->from(['i' => 'dt_interpret'])
                ->leftJoin(['v' => 'dt_interview'], 'v.id = i.anwer_id')
                ->where(['>', 'i.anwer_id', 0])->andWhere(['v.id' => null])
                ->count('*', $this->old);
            if ($orphans) {
                $this->stdout("\n  ! dt_interpret อ้างถึงคำตอบที่ไม่มีใน dt_interview {$orphans} แถว (dump อาจไม่ครบ)\n", Console::FG_YELLOW);
            }
        }
        return ExitCode::OK;
    }

    public function actionImport(): int
    {
        $existing = $this->old->schema->getTableNames();
        $missing = array_diff(['dt_userkey', 'dt_main', 'dt_qution', 'dt_people', 'dt_interview'], $existing);
        if ($missing) {
            $this->stderr('ขาดตารางหลัก: ' . implode(', ', $missing) . "\nกรุณานำเข้า SQL dump ฉบับเต็มก่อน (ดู ./yii legacy/check)\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        // ข้อมูลสัมภาษณ์บางรายการยาวมาก: เพิ่มหน่วยความจำและปิดการเก็บ log ทุก query ระหว่างนำเข้า
        ini_set('memory_limit', '1024M');
        foreach ([$this->db, $this->old] as $c) {
            $c->enableLogging = false;
            $c->enableProfiling = false;
        }

        $start = microtime(true);
        $tx = $this->db->beginTransaction();
        try {
            if ($this->fresh) {
                $this->purge();
            }
            $users = $this->importUsers();
            $methods = $this->importMethods($existing);
            $projects = $this->importProjects($users);
            $this->importMembers($existing, $projects, $users);
            $groups = $this->importGroups($existing, $projects);
            [$questions, $qTypes] = $this->importQuestions($projects, $users);
            $options = $this->importOptions($existing, $questions);
            $informants = $this->importInformants($projects, $groups, $methods);
            [$responses, $respQuestion] = $this->importResponses($informants, $questions, $qTypes, $options);
            $this->importInterpretations($existing, $questions, $responses, $respQuestion);
            $this->importConcepts($existing, $projects);
            $this->importLogs($existing, $users);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            // ไม่แสดง SQL ที่ล้มเหลว เพราะอาจมีรหัสผ่านเดิม (plain text) อยู่ในค่าที่ insert
            $msg = $e instanceof \yii\db\Exception
                ? 'SQLSTATE ' . implode(' ', array_filter((array)$e->errorInfo, 'is_scalar'))
                : strtok($e->getMessage(), "\n");
            $this->stderr("\nยกเลิกการนำเข้าทั้งหมด (ไม่มีข้อมูลถูกบันทึก): {$msg}\n", Console::FG_RED);
            $this->stderr(get_class($e) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n");
            return ExitCode::SOFTWARE;
        }

        foreach (array_unique($this->warnings) as $w) {
            $this->stdout("  ! {$w}\n", Console::FG_YELLOW);
        }
        $this->stdout(sprintf("\nเสร็จใน %.1f วินาที\n", microtime(true) - $start), Console::FG_GREEN);
        return ExitCode::OK;
    }

    // ------------------------------------------------------------------

    private function purge(): void
    {
        $this->stdout("ลบข้อมูลที่เคยนำเข้า...\n");
        // ลำดับลูก → แม่; ตารางลูกส่วนใหญ่ลบตาม CASCADE อยู่แล้ว
        foreach (['interpretation', 'response', 'informant', 'question_option', 'question', 'concept',
                     'informant_group', 'project', 'collection_method'] as $t) {
            $this->db->createCommand()->delete('{{%' . $t . '}}', ['not', ['legacy_id' => null]])->execute();
        }
        // บัญชีผู้ใช้ไม่ถูกลบ: เก็บรหัสผ่านใหม่และบัญชี Google/LINE ที่ผูกไว้แล้ว (จับคู่ใหม่ด้วย legacy_id)
        $this->db->createCommand()->delete('{{%login_log}}', ['method' => 'legacy'])->execute();
    }

    /**
     * อ่านทีละชุดจากตารางเดิม ข้ามแถวที่เคยนำเข้าแล้ว แล้ว insert แบบ batch
     *
     * @param callable $map  fn(array $row): ?array  คืน null เพื่อข้ามแถว
     * @return array<int,int> legacy_id => new id (รวมแถวที่เคยนำเข้าไว้ก่อน)
     */
    private function copy(string $label, string $oldTable, string $newTable, callable $map, ?callable $filter = null): array
    {
        $done = (new Query())->select(['id', 'legacy_id'])->from($newTable)
            ->where(['not', ['legacy_id' => null]])->indexBy('legacy_id')->column($this->db);

        $total = (new Query())->from($oldTable)->count('*', $this->old);
        $this->stdout(sprintf("%-28s", $label));
        $inserted = $skipped = 0;
        $batch = [];
        $cols = null;
        $q = (new Query())->from($oldTable)->orderBy('id');
        if ($filter) {
            $filter($q);
        }
        foreach ($q->each(500, $this->old) as $row) {
            if (isset($done[$row['id']])) {
                continue;
            }
            $data = $map($row);
            if ($data === null) {
                $skipped++;
                continue;
            }
            $data['legacy_id'] = (int)$row['id'];
            $cols ??= array_keys($data);
            $batch[] = array_values(array_merge(array_fill_keys($cols, null), $data));
            if (count($batch) >= 500) {
                $this->db->createCommand()->batchInsert($newTable, $cols, $batch)->execute();
                $inserted += count($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $this->db->createCommand()->batchInsert($newTable, $cols, $batch)->execute();
            $inserted += count($batch);
        }
        $this->stdout(sprintf("%7d/%-7d", $inserted, $total), Console::FG_GREEN);
        $this->stdout($skipped ? "  ข้าม {$skipped}\n" : "\n", Console::FG_YELLOW);

        return (new Query())->select(['id', 'legacy_id'])->from($newTable)
            ->where(['not', ['legacy_id' => null]])->indexBy('legacy_id')->column($this->db);
    }

    private function ts($value, ?int $fallback = null): int
    {
        if ($value && ($t = strtotime((string)$value)) && $t > 0) {
            return $t;
        }
        return $fallback ?? time();
    }

    private function str($v): ?string
    {
        if ($v === null) {
            return null;
        }
        // ระบบเดิมเก็บข้อความบางส่วนเป็น HTML entity (เช่น &#3612;) จึงถอดกลับเป็นตัวอักษรก่อน
        $v = trim(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $v === '' ? null : $v;
    }

    /**
     * ระบบเดิมมีหลายแถวที่ใช้ LoginName เดียวกัน (สมัครซ้ำ) แต่เข้าระบบได้จริงแค่แถวเดียว
     * จึงรวมเป็นบัญชีเดียว: ใช้แถวที่เข้าระบบบ่อยที่สุด (เท่ากันใช้ UserID น้อยสุด) เป็นหลัก
     * แล้วแมป UserID ของแถวอื่นทั้งหมดมาที่บัญชีนั้น ข้อมูลโครงการ/ทีม/ประวัติจึงไม่หาย
     *
     * @return array<int,int> legacy UserID (ทุกแถว) => primary legacy UserID
     */
    private function userAliases(): array
    {
        $logins = in_array('dt_log', $this->old->schema->getTableNames(), true)
            ? (new Query())->select(['n' => 'COUNT(*)', 'user'])->from('dt_log')->groupBy('user')->indexBy('user')->column($this->old)
            : [];
        $groups = [];
        // จัดกลุ่มด้วย collation เดียวกับ unique index ของตาราง user (utf8mb4_unicode_ci ไม่สนตัวพิมพ์และวรรณยุกต์ไทย)
        foreach ($this->loginKeys() as $id => $key) {
            $groups[$key ?? ('#' . $id)][] = $id;
        }
        $alias = [];
        foreach ($groups as $ids) {
            usort($ids, fn($a, $b) => [-(int)($logins[$a] ?? 0), $a] <=> [-(int)($logins[$b] ?? 0), $b]);
            foreach ($ids as $id) {
                $alias[$id] = $ids[0];
            }
        }
        return $alias;
    }

    /**
     * @return array<int,?string> legacy UserID => คีย์เปรียบเทียบชื่อผู้ใช้ตาม collation ของ MySQL (null = ไม่มีชื่อ)
     */
    private function loginKeys(): array
    {
        static $keys = null;
        if ($keys !== null) {
            return $keys;
        }
        $rows = $this->old->createCommand(
            "SELECT UserID, IF(TRIM(LoginName) = '' OR LoginName IS NULL, NULL,
                HEX(WEIGHT_STRING(CONVERT(TRIM(LoginName) USING utf8mb4) COLLATE utf8mb4_unicode_ci))) k
             FROM dt_userkey ORDER BY UserID"
        )->queryAll();
        $keys = [];
        foreach ($rows as $r) {
            $keys[(int)$r['UserID']] = $r['k'];
        }
        return $keys;
    }

    private function usernameKey(string $username): string
    {
        return (string)$this->db->createCommand(
            'SELECT HEX(WEIGHT_STRING(CONVERT(:u USING utf8mb4) COLLATE utf8mb4_unicode_ci))', [':u' => $username]
        )->queryScalar();
    }

    private function importUsers(): array
    {
        $now = time();
        $alias = $this->userAliases();
        $taken = [];
        foreach ((new Query())->select('username')->from('{{%user}}')->where(['legacy_id' => null])
                     ->andWhere(['not', ['username' => null]])->column($this->db) as $u) {
            $taken[$this->usernameKey($u)] = true;
        }

        $merged = 0;
        $map = $this->copy('ผู้ใช้ (dt_userkey)', 'dt_userkey', '{{%user}}', function ($r) use ($now, &$taken, $alias, &$merged) {
            if ($alias[(int)$r['UserID']] !== (int)$r['UserID']) {
                $merged++;
                return null;    // แถวซ้ำ: รวมเข้ากับบัญชีหลัก
            }
            $username = $this->str($r['LoginName'] ?? null);
            if ($username !== null) {
                $base = mb_substr(preg_replace('/[^\p{L}\p{M}\p{N}_.\-@ ]/u', '', $username), 0, 64) ?: 'user' . $r['UserID'];
                $username = $base;
                $i = 2;
                while (isset($taken[$this->usernameKey($username)])) {
                    $username = $base . '_' . $i++;
                    $this->warnings[] = "ชื่อผู้ใช้ {$base} มีอยู่แล้วในระบบใหม่ เปลี่ยนเป็น {$username}";
                }
                $taken[$this->usernameKey($username)] = true;
            }
            $prefix = $this->str($r['PrefixID'] ?? null);
            return [
                'username' => $username,
                'prefix' => $prefix !== null && !ctype_digit($prefix) ? mb_substr($prefix, 0, 50) : null,
                'first_name' => mb_substr($this->str($r['Name'] ?? null) ?? ($username ?? 'ผู้ใช้'), 0, 100),
                'last_name' => mb_substr((string)$this->str($r['Surname'] ?? null), 0, 100) ?: null,
                'legacy_password' => $this->str($r['PWD'] ?? null),
                'auth_key' => Yii::$app->security->generateRandomString(),
                'role' => (string)($r['Group'] ?? '') === '1' ? User::ROLE_ADMIN : User::ROLE_USER,
                'status' => (string)($r['Stats'] ?? '1') === '0' ? User::STATUS_INACTIVE : User::STATUS_ACTIVE,
                'created_at' => $this->ts($r['LastUpdate'] ?? null, $now),
                'updated_at' => $now,
            ];
        }, fn(Query $q) => $q->orderBy('UserID')->select(['*', 'id' => 'UserID']));

        // แมปแถวซ้ำไปยังบัญชีหลัก
        foreach ($alias as $legacyId => $primary) {
            if (!isset($map[$legacyId]) && isset($map[$primary])) {
                $map[$legacyId] = $map[$primary];
            }
        }
        $dupes = count(array_filter($alias, fn($p, $id) => $p !== $id, ARRAY_FILTER_USE_BOTH));
        if ($dupes) {
            $this->stdout(sprintf("  รวมบัญชีที่ใช้ชื่อผู้ใช้ซ้ำ %d แถว เข้ากับบัญชีหลัก\n", $dupes), Console::FG_YELLOW);
        }
        return $map;
    }

    private function importMethods(array $existing): array
    {
        if (!in_array('dt_information', $existing, true)) {
            return [];
        }
        $map = [];
        foreach ((new Query())->from('dt_information')->all($this->old) as $r) {
            $name = $this->str($r['name']) ?? ('วิธีที่ ' . $r['id']);
            $id = (new Query())->select('id')->from('{{%collection_method}}')
                ->where(['or', ['legacy_id' => $r['id']], ['name' => $name]])->scalar($this->db);
            if (!$id) {
                $this->db->createCommand()->insert('{{%collection_method}}', [
                    'name' => $name, 'sort_order' => 100 + (int)$r['id'], 'legacy_id' => $r['id'],
                ])->execute();
                $id = $this->db->getLastInsertID();
            } else {
                $this->db->createCommand()->update('{{%collection_method}}', ['legacy_id' => $r['id']], ['id' => $id])->execute();
            }
            $map[(int)$r['id']] = (int)$id;
        }
        $this->stdout(sprintf("%-28s%7d\n", 'วิธีเก็บข้อมูล', count($map)), Console::FG_GREEN);
        return $map;
    }

    private function fallbackOwner(array $users): int
    {
        static $id = null;
        if ($id) {
            return $id;
        }
        $id = (int)(new Query())->select('id')->from('{{%user}}')->where(['role' => User::ROLE_ADMIN])->orderBy('id')->scalar($this->db);
        if (!$id) {
            $id = (int)reset($users);
        }
        return $id;
    }

    private function importProjects(array $users): array
    {
        $now = time();
        return $this->copy('โครงการ (dt_main)', 'dt_main', '{{%project}}', function ($r) use ($users, $now) {
            $owner = $users[(int)($r['userkey'] ?? 0)] ?? null;
            if (!$owner) {
                $owner = $this->fallbackOwner($users);
                $this->warnings[] = "โครงการ #{$r['id']} ไม่พบเจ้าของ (userkey={$r['userkey']}) กำหนดให้ผู้ดูแลระบบ";
            }
            $created = $this->ts($r['date'] ?? null, $now);
            return [
                'title' => $this->str($r['name']) ?? ('โครงการ #' . $r['id']),
                'description' => $this->str($r['detail'] ?? null),
                'started_on' => !empty($r['date']) && strtotime($r['date']) > 0 ? date('Y-m-d', strtotime($r['date'])) : null,
                'owner_id' => $owner,
                'is_archived' => (int)($r['disable'] ?? 0) ? 1 : 0,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        });
    }

    private function importMembers(array $existing, array $projects, array $users): void
    {
        $now = time();
        $rows = [];
        // เจ้าของโครงการ
        foreach ((new Query())->select(['id', 'owner_id'])->from('{{%project}}')->where(['id' => array_values($projects)])->all($this->db) as $p) {
            $rows[$p['id'] . ':' . $p['owner_id']] = [$p['id'], $p['owner_id'], 'owner', $now];
        }
        if (in_array('dt_join', $existing, true)) {
            foreach ((new Query())->from('dt_join')->all($this->old) as $j) {
                $pid = $projects[(int)$j['mainid']] ?? null;
                $uid = $users[(int)$j['userid']] ?? null;
                if ($pid && $uid && !isset($rows["$pid:$uid"])) {
                    $rows["$pid:$uid"] = [$pid, $uid, 'member', $now];
                }
            }
        }
        $current = (new Query())->select(["CONCAT(project_id, ':', user_id)"])->from('{{%project_member}}')->column($this->db);
        $rows = array_diff_key($rows, array_flip($current));
        if ($rows) {
            $this->db->createCommand()->batchInsert('{{%project_member}}', ['project_id', 'user_id', 'role', 'created_at'], array_values($rows))->execute();
        }
        $this->stdout(sprintf("%-28s%7d\n", 'ทีมงาน (dt_join)', count($rows)), Console::FG_GREEN);
    }

    private function importGroups(array $existing, array $projects): array
    {
        if (!in_array('dt_group', $existing, true)) {
            return [];
        }
        return $this->copy('กลุ่ม (dt_group)', 'dt_group', '{{%informant_group}}', function ($r) use ($projects) {
            $pid = $projects[(int)$r['main_id']] ?? null;
            return $pid ? ['project_id' => $pid, 'name' => $this->str($r['name']) ?? ('กลุ่ม ' . $r['id'])] : null;
        });
    }

    /**
     * @return array{0:array<int,int>,1:array<int,string>} [legacy qid => new id, new id => answer_type]
     */
    private function importQuestions(array $projects, array $users): array
    {
        $now = time();
        $map = $this->copy('คำถาม (dt_qution)', 'dt_qution', '{{%question}}', function ($r) use ($projects, $users, $now) {
            $pid = $projects[(int)$r['main_id']] ?? null;
            if (!$pid) {
                return null;
            }
            $created = $this->ts($r['datekey'] ?? null, $now);
            return [
                'project_id' => $pid,
                'category' => (string)($r['types'] ?? '1') === '2' ? 'demographic' : 'research',
                'answer_type' => match ((string)($r['type_id'] ?? '1')) { '2' => 'choice', '3' => 'boolean', default => 'text' },
                'title' => $this->str($r['name']) ?? ('คำถาม #' . $r['id']),
                'detail' => $this->str($r['detail'] ?? null),
                'sort_order' => (int)$r['id'],
                'is_deleted' => ($r['status'] ?? '') === 'D' ? 1 : 0,
                'created_by' => $users[(int)($r['userkey'] ?? 0)] ?? null,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        });
        $types = (new Query())->select(['answer_type', 'id'])->from('{{%question}}')
            ->where(['id' => array_values($map)])->indexBy('id')->column($this->db);
        return [$map, $types];
    }

    private function importOptions(array $existing, array $questions): array
    {
        if (!in_array('dt_anwer', $existing, true)) {
            return [];
        }
        return $this->copy('ตัวเลือก (dt_anwer)', 'dt_anwer', '{{%question_option}}', function ($r) use ($questions) {
            $qid = $questions[(int)$r['qution_id']] ?? null;
            return $qid ? [
                'question_id' => $qid,
                'label' => mb_substr($this->str($r['name']) ?? ('ตัวเลือก ' . $r['id']), 0, 500),
                'sort_order' => (int)($r['value'] ?? 0) ?: (int)$r['id'],
            ] : null;
        });
    }

    private function importInformants(array $projects, array $groups, array $methods): array
    {
        $now = time();
        return $this->copy('ผู้ให้ข้อมูล (dt_people)', 'dt_people', '{{%informant}}', function ($r) use ($projects, $groups, $methods, $now) {
            $pid = $projects[(int)$r['main_id']] ?? null;
            if (!$pid) {
                return null;
            }
            $created = $this->ts($r['date'] ?? null, $now);
            $age = isset($r['age']) && is_numeric($r['age']) && $r['age'] > 0 && $r['age'] < 150 ? (int)$r['age'] : null;
            return [
                'project_id' => $pid,
                'code' => mb_substr($this->str($r['name']) ?? ('#' . $r['id']), 0, 100),
                'age' => $age,
                'group_id' => $groups[(int)($r['group_id'] ?? 0)] ?? null,
                'collection_method_id' => $methods[(int)($r['information_id'] ?? 0)] ?? null,
                'interviewed_on' => !empty($r['date']) && strtotime($r['date']) > 0 ? date('Y-m-d', strtotime($r['date'])) : null,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        });
    }

    /**
     * @return array{0:array<int,int>,1:array<int,int>} [legacy interview id => new response id, new response id => question id]
     */
    private function importResponses(array $informants, array $questions, array $qTypes, array $options): array
    {
        $now = time();
        $map = $this->copy('คำตอบ (dt_interview)', 'dt_interview', '{{%response}}',
            function ($r) use ($informants, $questions, $qTypes, $options, $now) {
                $inf = $informants[(int)$r['peopleid']] ?? null;
                $qid = $questions[(int)$r['qid']] ?? null;
                if (!$inf || !$qid) {
                    return null;
                }
                $raw = (string)$this->str($r['value'] ?? '');
                $value = $raw;
                $optionId = null;
                switch ($qTypes[$qid] ?? 'text') {
                    case 'choice':
                        if (ctype_digit(trim($raw)) && isset($options[(int)$raw])) {
                            $optionId = $options[(int)$raw];
                            $value = null;
                        }
                        break;
                    case 'boolean':
                        $value = in_array(mb_strtolower(trim($raw)), ['on', '1', 'ใช่', 'มี', 'yes'], true) ? '1' : '0';
                        break;
                }
                return [
                    'informant_id' => $inf,
                    'question_id' => $qid,
                    'value_text' => $value,
                    'option_id' => $optionId,
                    'is_deleted' => ($r['status'] ?? '') === 'D' ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });
        $respQuestion = (new Query())->select(['question_id', 'id'])->from('{{%response}}')
            ->where(['not', ['legacy_id' => null]])->indexBy('id')->column($this->db);
        return [$map, $respQuestion];
    }

    private function importInterpretations(array $existing, array $questions, array $responses, array $respQuestion): void
    {
        if (!in_array('dt_interpret', $existing, true)) {
            return;
        }
        $now = time();
        $orphans = 0;
        $this->copy('ประเด็น (dt_interpret)', 'dt_interpret', '{{%interpretation}}',
            function ($r) use ($questions, $responses, $respQuestion, $now, &$orphans) {
                $text = $this->str($r['name']);
                if ($text === null) {
                    return null;
                }
                $responseId = null;
                if ((int)$r['anwer_id'] > 0) {
                    $responseId = $responses[(int)$r['anwer_id']] ?? null;
                    if (!$responseId) {
                        $orphans++;
                        return null;    // คำตอบต้นทางไม่มีแล้ว
                    }
                }
                $qid = $responseId ? $respQuestion[$responseId] : ($questions[(int)$r['qid']] ?? null);
                if (!$qid) {
                    return null;
                }
                return [
                    'question_id' => $qid,
                    'response_id' => $responseId,
                    'round' => in_array((int)$r['channel'], [1, 2, 3, 4], true) ? (int)$r['channel'] : 1,
                    'ipo' => match ((string)$r['types']) { '1' => 'input', '2' => 'process', '3' => 'output', default => null },
                    'text' => $text,
                    'is_deleted' => ($r['status'] ?? '') === 'D' ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });
        if ($orphans) {
            $this->warnings[] = "ข้ามประเด็น {$orphans} รายการที่อ้างถึงคำตอบซึ่งไม่มีใน dt_interview";
        }
    }

    private function importConcepts(array $existing, array $projects): void
    {
        if (!in_array('dt_conceptual', $existing, true)) {
            return;
        }
        $now = time();
        $this->copy('Conceptual (dt_conceptual)', 'dt_conceptual', '{{%concept}}', function ($r) use ($projects, $now) {
            $pid = $projects[(int)$r['main_id']] ?? null;
            $name = $this->str($r['name']);
            if (!$pid || $name === null) {
                return null;
            }
            $created = $this->ts($r['datekey'] ?? null, $now);
            return [
                'project_id' => $pid,
                'name' => $name,
                'is_input' => (int)$r['input'] === 1 ? 1 : 0,
                'is_process' => (int)$r['process'] === 1 ? 1 : 0,
                'is_output' => (int)$r['output'] === 1 ? 1 : 0,
                'is_problem' => (int)$r['problem'] === 1 ? 1 : 0,
                'is_success' => (int)$r['success'] === 1 ? 1 : 0,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        });
    }

    private function importLogs(array $existing, array $users): void
    {
        if (!in_array('dt_log', $existing, true)) {
            return;
        }
        if ((new Query())->from('{{%login_log}}')->where(['method' => 'legacy'])->exists($this->db)) {
            $this->stdout(sprintf("%-28s%s\n", 'ประวัติเข้าระบบ (dt_log)', 'นำเข้าแล้ว'), Console::FG_GREEN);
            return;
        }
        $batch = [];
        $n = 0;
        foreach ((new Query())->from('dt_log')->each(2000, $this->old) as $r) {
            $uid = $users[(int)($r['user'] ?? 0)] ?? null;
            if (!$uid) {
                continue;
            }
            $batch[] = [$uid, 'legacy', mb_substr((string)($r['ip'] ?? ''), 0, 45), $this->ts($r['logtime'] ?? null)];
            if (count($batch) >= 2000) {
                $this->db->createCommand()->batchInsert('{{%login_log}}', ['user_id', 'method', 'ip', 'created_at'], $batch)->execute();
                $n += count($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $this->db->createCommand()->batchInsert('{{%login_log}}', ['user_id', 'method', 'ip', 'created_at'], $batch)->execute();
            $n += count($batch);
        }
        $this->stdout(sprintf("%-28s%7d\n", 'ประวัติเข้าระบบ (dt_log)', $n), Console::FG_GREEN);
    }
}
