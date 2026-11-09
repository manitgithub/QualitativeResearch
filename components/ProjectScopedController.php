<?php

namespace app\components;

use app\models\Project;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Controller พื้นฐานสำหรับหน้าที่อยู่ภายใต้โครงการ ตรวจสิทธิ์สมาชิกให้อัตโนมัติ
 */
abstract class ProjectScopedController extends Controller
{
    public const ACCESS_VIEW = 'view';
    public const ACCESS_EDIT = 'edit';
    public const ACCESS_MANAGE = 'manage';

    /** @var Project|null โครงการปัจจุบัน (ใช้ใน layout แสดงเมนูย่อย) */
    public ?Project $project = null;

    /** @var string[] action ที่ต้องเป็น POST */
    protected array $postActions = ['delete'];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => array_fill_keys($this->postActions, ['POST']),
            ],
        ];
    }

    protected function currentUser(): User
    {
        /** @var User $u */
        $u = Yii::$app->user->identity;
        return $u;
    }

    protected function findProject($id, string $access = self::ACCESS_VIEW): Project
    {
        $project = Project::findOne((int)$id);
        if (!$project) {
            throw new NotFoundHttpException('ไม่พบโครงการ');
        }
        $user = $this->currentUser();
        $allowed = match ($access) {
            self::ACCESS_MANAGE => $project->canManage($user),
            self::ACCESS_EDIT => $project->canEdit($user),
            default => $project->canView($user),
        };
        if (!$allowed) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ในโครงการนี้');
        }
        if ($project->is_archived && $access !== self::ACCESS_VIEW && !$user->isAdmin()
            && $this->action->id !== 'restore') {
            throw new ForbiddenHttpException('โครงการนี้ถูกเก็บเข้าคลังแล้ว ต้องนำออกจากคลังก่อนแก้ไข');
        }
        $this->project = $project;
        $this->view->params['project'] = $project;
        return $project;
    }

    protected function goBackOr(array $fallback)
    {
        $ref = Yii::$app->request->post('returnUrl') ?: Yii::$app->request->referrer;
        // ป้องกัน open redirect: อนุญาตเฉพาะ URL ภายในโฮสต์เดียวกัน
        if ($ref && (str_starts_with($ref, '/') && !str_starts_with($ref, '//')
                || parse_url($ref, PHP_URL_HOST) === Yii::$app->request->hostName)) {
            return $this->redirect($ref);
        }
        return $this->redirect($fallback);
    }
}
