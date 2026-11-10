<?php

namespace tests\unit\models;

use app\components\AuthHandler;
use app\components\UserMerger;
use app\models\Concept;
use app\models\Informant;
use app\models\InformantGroup;
use app\models\Project;
use app\models\ProjectMember;
use app\models\Question;
use app\models\QuestionOption;
use app\models\Response;
use app\models\User;
use app\models\UserAuth;
use app\tests\Support\Factory;
use Yii;

/**
 * flagDel: ทุกตารางลบแบบซ่อน ไม่ลบแถวจริง
 */
class SoftDeleteTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Factory::reset();
    }

    private function rawFlag(string $table, int $id): ?int
    {
        $v = Yii::$app->db->createCommand("SELECT flagDel FROM {{%$table}} WHERE id = :id", [':id' => $id])->queryScalar();
        return $v === false ? null : (int)$v;   // null = row physically gone
    }

    public function testEveryTableHasFlagDel()
    {
        $tables = array_diff(Yii::$app->db->schema->getTableNames('', true), ['migration']);
        verify(count($tables))->equals(18);
        foreach ($tables as $t) {
            verify(Yii::$app->db->getTableSchema($t, true)->getColumn('flagDel'))->notNull("$t.flagDel");
            verify(Yii::$app->db->getTableSchema($t)->getColumn('is_deleted'))->null("$t.is_deleted");
        }
    }

    public function testDeleteKeepsRowAndHidesIt()
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $q = Factory::question($p);
        $inf = Factory::informant($p);
        $r = Factory::response($inf, $q);

        verify($q->delete())->equals(1);
        verify($this->rawFlag('question', $q->id))->equals(1);           // still in the table
        verify(Question::findOne($q->id))->null();                        // hidden from finders
        verify(Question::find()->where(['project_id' => $p->id])->count())->equals(0);
        verify(Question::find()->withDeleted()->where(['id' => $q->id])->exists())->true();
        verify(Project::findOne($p->id)->questions)->empty();             // hidden from relations

        verify(Informant::deleteAll(['id' => $inf->id]))->equals(1);       // deleteAll is soft too
        verify($this->rawFlag('informant', $inf->id))->equals(1);
        verify($this->rawFlag('response', $r->id))->equals(0);           // children untouched
    }

    public function testRestore()
    {
        $c = new Concept(['project_id' => Factory::project(Factory::user())->id, 'name' => 'x']);
        $c->save();
        $c->delete();
        $c->restore();
        verify(Concept::findOne($c->id))->notNull();
    }

    public function testAllModelsUseSoftDelete()
    {
        $owner = Factory::user();
        $p = Factory::project($owner);
        $g = new InformantGroup(['project_id' => $p->id, 'name' => 'g']);
        $g->save();
        $c = new Concept(['project_id' => $p->id, 'name' => 'c']);
        $c->save();
        $q = Factory::question($p, ['answer_type' => Question::TYPE_CHOICE, 'optionsText' => "a\nb"]);
        $opt = QuestionOption::findOne(['question_id' => $q->id, 'label' => 'a']);
        $auth = new UserAuth(['user_id' => $owner->id, 'source' => 'google', 'source_id' => 'g1']);
        $auth->save(false);

        foreach ([['informant_group', $g], ['concept', $c], ['question_option', $opt], ['user_auth', $auth], ['project', $p]] as [$table, $model]) {
            $model->delete();
            verify($this->rawFlag($table, $model->id))->equals(1, $table);
        }
        $member = ProjectMember::findOne(['project_id' => $p->id, 'user_id' => $owner->id]);
        $member->delete();
        verify((int)Yii::$app->db->createCommand('SELECT flagDel FROM project_member WHERE project_id=:p AND user_id=:u',
            [':p' => $p->id, ':u' => $owner->id])->queryScalar())->equals(1);
    }

    public function testChoiceSyncFlagsRemovedOptions()
    {
        $q = Factory::question(Factory::project(Factory::user()), ['answer_type' => Question::TYPE_CHOICE, 'optionsText' => "ชาย\nหญิง"]);
        $male = QuestionOption::findOne(['question_id' => $q->id, 'label' => 'ชาย']);
        $q = Question::findOne($q->id);
        $q->optionsText = 'หญิง';
        $q->save();
        verify($this->rawFlag('question_option', $male->id))->equals(1);
        verify(Question::findOne($q->id)->options)->arrayCount(1);
    }

    public function testDeletedUserFreesEmailAndUsername()
    {
        $u = Factory::user(['username' => 'somsri', 'email' => 'somsri@gmail.test']);
        $u->delete();
        verify($this->rawFlag('user', $u->id))->equals(1);
        $again = Factory::user(['username' => 'somsri', 'email' => 'somsri@gmail.test']);   // unique only among active
        verify($again->id)->notEquals($u->id);
        $dup = new User(['first_name' => 'x', 'email' => 'somsri@gmail.test']);
        verify($dup->validate(['email']))->false();
    }

    public function testRejectedPersonCanRequestAgain()
    {
        Factory::admin();
        $h = new AuthHandler(new \tests\unit\components\StubClient('line', ['id' => 'U1', 'name' => 'A']));
        $h->handle();
        $first = UserAuth::findOne(['source_id' => 'U1'])->user;
        UserAuth::deleteAll(['user_id' => $first->id]);
        $first->delete();                                        // = reject

        $h = new AuthHandler(new \tests\unit\components\StubClient('line', ['id' => 'U1', 'name' => 'A']));
        $h->handle();
        $second = UserAuth::findOne(['source_id' => 'U1'])->user;
        verify($second->id)->notEquals($first->id);
        verify($second->status)->equals(User::STATUS_PENDING);
        verify($this->rawFlag('user', $first->id))->equals(1);   // rejected request kept
        verify(UserAuth::find()->withDeleted()->where(['source_id' => 'U1'])->count())->equals(2);
    }

    public function testMergeFlagsNewAccountAndRevivesMembership()
    {
        $legacy = Factory::user(['username' => 'legacy']);
        $other = Factory::user();
        $p = Factory::project($other);
        Factory::member($p, $legacy);
        ProjectMember::findOne(['project_id' => $p->id, 'user_id' => $legacy->id])->delete();   // legacy was removed earlier
        $pending = Factory::user(['status' => User::STATUS_PENDING, 'email' => 'n@gmail.test']);
        Factory::member($p, $pending);

        (new UserMerger())->merge($pending, $legacy);

        verify($this->rawFlag('user', $pending->id))->equals(1);
        verify(ProjectMember::findOne(['project_id' => $p->id, 'user_id' => $legacy->id]))->notNull();   // revived
        verify(ProjectMember::find()->where(['user_id' => $pending->id])->exists())->false();
    }

    public function testJoinedTablesStillNeedExplicitFilter()
    {
        // documents the one gap: joinWith() does not auto-filter the joined table
        $p = Factory::project(Factory::user());
        $q = Factory::question($p);
        $inf = Factory::informant($p);
        Factory::response($inf, $q);
        $inf->delete();
        $n = Response::find()->alias('r')->innerJoinWith('informant inf', false)->count();
        verify($n)->equals(1);
        $n = Response::find()->alias('r')->innerJoinWith('informant inf', false)->andWhere(['inf.flagDel' => 0])->count();
        verify($n)->equals(0);
    }
}
