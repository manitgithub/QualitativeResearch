<?php

namespace tests\unit\commands;

use app\commands\LegacyController;
use app\models\Informant;
use app\models\Interpretation;
use app\models\LoginLog;
use app\models\Project;
use app\models\ProjectMember;
use app\models\Question;
use app\models\Response;
use app\models\User;
use app\tests\Support\Factory;
use Yii;
use yii\db\Connection;

/**
 * ทดสอบการย้ายข้อมูลจากระบบเดิม ด้วยฐานข้อมูลเดิมจำลองขนาดเล็ก (qualitative_legacy_test)
 * ที่ใส่กรณีพิเศษที่พบในข้อมูลจริงไว้
 */
class LegacyImportTest extends \Codeception\Test\Unit
{
    private const LEGACY_DB = 'qualitative_legacy_test';

    protected function _before()
    {
        Factory::reset();
        $root = new Connection(['dsn' => 'mysql:host=127.0.0.1', 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4']);
        $root->createCommand('DROP DATABASE IF EXISTS ' . self::LEGACY_DB)->execute();
        $root->createCommand('CREATE DATABASE ' . self::LEGACY_DB . ' CHARACTER SET utf8mb4')->execute();
        Yii::$app->set('legacyDb', [
            'class' => Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=' . self::LEGACY_DB,
            'username' => 'root', 'password' => '', 'charset' => 'utf8mb4',
        ]);
        $this->seedLegacy(Yii::$app->get('legacyDb'));
    }

    protected function _after()
    {
        Yii::$app->get('legacyDb')->createCommand('DROP DATABASE IF EXISTS ' . self::LEGACY_DB)->execute();
    }

    private function seedLegacy(Connection $old): void
    {
        $sql = <<<'SQL'
CREATE TABLE dt_userkey (UserID int primary key, PrefixID int, Name varchar(255), Surname varchar(255), LoginName varchar(255), PWD varchar(255), DivID int, PositionID int, `Group` varchar(255), LastUpdate varchar(30), LastUser varchar(255), pic varchar(255), Stats int);
INSERT INTO dt_userkey (UserID, Name, LoginName, PWD, `Group`, Stats) VALUES
 (1, 'มานิต', 'manit', 'adminpw', '1', 1),
 (2, 'สมศรี', 'Somsri', 'pw2', NULL, 1),
 (3, 'สมศรี ซ้ำ', 'somsri', 'pw3', NULL, 1),
 (4, 'สมศรี มีวรรณยุกต์', '๋่Somsri', 'pw4', NULL, 1),
 (5, 'ไทย', 'มานิตย์', 'thai', NULL, 1),
 (6, 'ปิด', 'closed', 'x', NULL, 0);
CREATE TABLE dt_log (id int primary key, user int, logtime varchar(50), ip varchar(30));
INSERT INTO dt_log VALUES (1,3,'2020-01-01 10:00:00','1.1.1.1'),(2,3,'2020-01-02 10:00:00','1.1.1.1'),(3,2,'2020-01-03 10:00:00','1.1.1.1'),(4,99,'2020-01-03 10:00:00','x');
CREATE TABLE dt_main (id int primary key, name varchar(200), date varchar(30), detail text, userkey int, disable int NOT NULL DEFAULT 0);
INSERT INTO dt_main VALUES (10,'โครงการ &#3585;','2019-01-08','รายละเอียด',2,0),(11,'ไม่มีเจ้าของ','2020-09-01',NULL,40,1);
CREATE TABLE dt_join (id int primary key, mainid int, userid int);
INSERT INTO dt_join VALUES (1,10,5),(2,10,4);
CREATE TABLE dt_information (id int primary key, name varchar(200));
INSERT INTO dt_information VALUES (1,'สัมภาษณ์');
CREATE TABLE dt_group (id int primary key, name varchar(255), main_id int);
CREATE TABLE dt_qution (id int primary key, name varchar(255), detail varchar(255), datekey varchar(30), userkey int, type_id int, main_id int, types int, status varchar(2) NOT NULL);
INSERT INTO dt_qution VALUES (100,'คำถามข้อความ',NULL,'2019-01-08',2,1,10,1,''),(101,'เพศ',NULL,NULL,2,2,10,2,''),(102,'เคยอบรม',NULL,NULL,2,3,10,2,''),(103,'ถูกลบ',NULL,NULL,2,1,10,1,'D'),(104,'ไม่มีโครงการ',NULL,NULL,2,1,999,1,'');
CREATE TABLE dt_anwer (id int primary key, qution_id int, name text, value int);
INSERT INTO dt_anwer VALUES (500,101,'ชาย',1),(501,101,'หญิง',2),(502,999,'กำพร้า',1);
CREATE TABLE dt_people (id int primary key, date varchar(30), name varchar(255), age int, group_id int, main_id int, information_id int);
INSERT INTO dt_people VALUES (1000,'2019-06-18','P01',45,NULL,10,1),(1001,'2019-06-18','P01',300,NULL,10,NULL),(1002,NULL,'P99',NULL,NULL,999,NULL);
CREATE TABLE dt_interview (id int primary key, peopleid int, qid int, value text, status varchar(2) NOT NULL);
INSERT INTO dt_interview VALUES (2000,1000,100,'คำตอบ &#3612;&#3641;&#3657;',''),(2001,1000,101,'501',''),(2002,1000,102,'on',''),(2003,1001,102,'',''),(2004,1001,100,'ลบแล้ว','D'),(2005,1000,104,'ไม่มีคำถาม',''),(2006,1001,101,'ไม่ใช่รหัส','');
CREATE TABLE dt_interpret (id int primary key, name text, types int, anwer_id int, qid int, channel int, status varchar(2) NOT NULL);
INSERT INTO dt_interpret VALUES (3000,'ประเด็นแรก',2,2000,100,1,''),(3001,'ยกประเด็น',1,2000,100,2,''),(3002,'สังเคราะห์',3,0,100,4,''),(3003,'ช่องว่าง',NULL,2000,100,NULL,''),(3004,'คำตอบหาย',1,7777,100,1,''),(3005,'   ',1,2000,100,1,''),(3006,'ลบ',1,2000,100,1,'D');
CREATE TABLE dt_conceptual (id int primary key, name text, main_id int, input int, process int, output int, problem int, success int, datekey datetime);
INSERT INTO dt_conceptual VALUES (1,'แนวคิด',10,1,0,1,0,1,'2019-01-01'),(2,'',10,1,0,0,0,0,NULL);
SQL;
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
            $old->createCommand($stmt)->execute();
        }
    }

    private function runImport(bool $fresh = false): int
    {
        $c = new LegacyController('legacy', Yii::$app);
        $c->fresh = $fresh;
        ob_start();
        $code = $c->actionImport();
        ob_end_clean();
        return $code;
    }

    public function testFullImport()
    {
        verify($this->runImport())->equals(0);

        // users: Somsri/somsri/๋่Somsri collapse into one account (most logins = UserID 3)
        verify(User::find()->count())->equals(4);
        $somsri = User::findOne(['legacy_id' => 3]);
        verify($somsri)->notNull();
        verify(User::find()->where(['legacy_id' => [2, 4]])->count())->equals(0);
        verify($somsri->username)->equals('somsri');
        verify(User::findOne(['username' => 'มานิตย์']))->notNull();
        verify(array_key_exists('legacy_password', $somsri->attributes))->false();   // old passwords are not imported
        verify(User::findOne(['legacy_id' => 1])->role)->equals(User::ROLE_ADMIN);
        verify(User::findOne(['legacy_id' => 6])->status)->equals(User::STATUS_INACTIVE);

        // projects: owner of #10 was UserID 2 -> merged account; HTML entities decoded
        $p = Project::findOne(['legacy_id' => 10]);
        verify($p->title)->equals('โครงการ ก');
        verify($p->owner_id)->equals($somsri->id);
        $p11 = Project::findOne(['legacy_id' => 11]);
        verify($p11->owner_id)->equals(User::findOne(['legacy_id' => 1])->id);   // missing owner -> admin
        verify((bool)$p11->is_archived)->true();
        // dt_join userid 4 maps to the merged owner; no duplicate membership
        verify(ProjectMember::find()->where(['project_id' => $p->id])->count())->equals(2);

        // questions: category/type mapped, deleted flag kept, orphan skipped
        verify(Question::find()->count())->equals(4);
        verify(Question::findOne(['legacy_id' => 101])->answer_type)->equals('choice');
        verify(Question::findOne(['legacy_id' => 101])->category)->equals('demographic');
        verify(Question::findOne(['legacy_id' => 102])->answer_type)->equals('boolean');
        verify((int)Question::findOne(['legacy_id' => 103])->is_deleted)->equals(1);

        // informants: duplicate codes kept, impossible age dropped, orphan skipped
        verify(Informant::find()->count())->equals(2);
        verify(Informant::findOne(['legacy_id' => 1001])->age)->null();

        // responses
        $r = fn($id) => Response::findOne(['legacy_id' => $id]);
        verify($r(2000)->value_text)->equals('คำตอบ ผู้');
        verify($r(2001)->option_id)->notNull();
        verify($r(2001)->value_text)->null();
        verify($r(2002)->value_text)->equals('1');
        verify($r(2003)->value_text)->equals('0');
        verify((int)$r(2004)->is_deleted)->equals(1);
        verify($r(2005))->null();                         // question has no project
        verify($r(2006)->value_text)->equals('ไม่ใช่รหัส');  // unmapped choice kept as text

        // interpretations
        $i = fn($id) => Interpretation::findOne(['legacy_id' => $id]);
        verify($i(3000)->round)->equals(1);
        verify($i(3000)->ipo)->equals('process');
        verify($i(3001)->round)->equals(2);
        verify($i(3002)->response_id)->null();            // question-level synthesis
        verify($i(3002)->round)->equals(4);
        verify($i(3003)->round)->equals(1);               // NULL channel -> round 1
        verify($i(3004))->null();                         // answer missing
        verify($i(3005))->null();                         // blank text
        verify((int)$i(3006)->is_deleted)->equals(1);

        // concepts and login history
        verify(\app\models\Concept::find()->count())->equals(1);
        verify(LoginLog::find()->where(['method' => 'legacy'])->count())->equals(3);
        verify(LoginLog::find()->where(['user_id' => $somsri->id])->count())->equals(3);
    }

    public function testImportIsIdempotent()
    {
        $this->runImport();
        $counts = fn() => [User::find()->count(), Project::find()->count(), Response::find()->count(), Interpretation::find()->count(), LoginLog::find()->count()];
        $before = $counts();
        verify($this->runImport())->equals(0);
        verify($counts())->equals($before);
    }

    public function testFreshKeepsUserAccounts()
    {
        $this->runImport();
        $u = User::findOne(['legacy_id' => 1]);
        $u->updateAttributes(['email' => 'manit@gmail.test']);
        (new \app\models\UserAuth(['user_id' => $u->id, 'source' => 'google', 'source_id' => 'g-manit']))->save(false);

        verify($this->runImport(true))->equals(0);
        $after = User::findOne(['legacy_id' => 1]);
        verify($after->id)->equals($u->id);
        verify($after->email)->equals('manit@gmail.test');
        verify(\app\models\UserAuth::findOne(['source_id' => 'g-manit'])->user_id)->equals($u->id);
        verify(Project::find()->count())->equals(2);
        verify(LoginLog::find()->where(['method' => 'legacy'])->count())->equals(3);
    }

    public function testRefusesIncompleteDump()
    {
        Yii::$app->get('legacyDb')->createCommand('DROP TABLE dt_main')->execute();
        verify($this->runImport())->notEquals(0);
        verify(User::find()->count())->equals(0);
    }

    public function testFailureRollsBackEverything()
    {
        // question.title is TEXT (64KB) in the new schema: a 70KB legacy title forces a DB error midway,
        // after users and projects were already inserted in the same transaction
        Yii::$app->get('legacyDb')->createCommand('ALTER TABLE dt_qution MODIFY name LONGTEXT')->execute();
        Yii::$app->get('legacyDb')->createCommand("UPDATE dt_qution SET name = REPEAT('x', 70000) WHERE id = 100")->execute();
        $code = $this->runImport();
        verify($code)->notEquals(0);
        verify(User::find()->count())->equals(0);
        verify(Project::find()->count())->equals(0);
    }
}
