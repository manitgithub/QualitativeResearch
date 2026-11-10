<?php

namespace tests\unit\components;

use Yii;

/**
 * returnUrl / Referer ต้องพากลับได้เฉพาะภายในเว็บเดียวกัน (กัน open redirect)
 */
class RedirectTest extends \Codeception\Test\Unit
{
    private function locationFor(?string $returnUrl, ?string $referrer = null): string
    {
        $req = Yii::$app->request;
        $req->setBodyParams($returnUrl === null ? [] : ['returnUrl' => $returnUrl]);
        $req->headers->remove('Referer');
        if ($referrer) {
            $req->headers->set('Referer', $referrer);
        }
        $req->setHostInfo('http://app.local');
        Yii::$app->response->headers->removeAll();
        $probe = new class('probe', Yii::$app) extends \app\components\ProjectScopedController {
            public function back(array $fallback)
            {
                return $this->goBackOr($fallback);
            }
        };
        $probe->back(['/site/index']);
        return (string)Yii::$app->response->headers->get('location');
    }

    public function testLocalPathsAllowed()
    {
        verify($this->locationFor('/project/5/report/question#r1'))->equals('http://app.local/project/5/report/question#r1');
        verify($this->locationFor(null, 'http://app.local/project/5'))->equals('http://app.local/project/5');
    }

    public function testExternalTargetsFallBack()
    {
        foreach (['https://evil.example/x', '//evil.example/x', 'javascript:alert(1)', 'http://app.local.evil.example/'] as $bad) {
            verify($this->locationFor($bad))->stringNotContainsString('evil');
            verify($this->locationFor($bad))->stringNotContainsString('javascript');
        }
        verify($this->locationFor(null, 'https://evil.example/'))->stringNotContainsString('evil');
    }
}
