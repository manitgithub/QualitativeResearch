<?php

namespace app\components\authclient;

use yii\authclient\OAuth2;

/**
 * LINE Login v2.1 (OAuth 2.0 + OpenID Connect)
 *
 * ข้อมูลที่คืนมาใน getUserAttributes():
 *  - id          LINE userId
 *  - name        displayName
 *  - picture     pictureUrl
 *  - email       (มีเฉพาะเมื่อ channel ได้สิทธิ์ email และผู้ใช้ยินยอม)
 *
 * @see https://developers.line.biz/en/docs/line-login/integrate-line-login/
 */
class Line extends OAuth2
{
    public $authUrl = 'https://access.line.me/oauth2/v2.1/authorize';
    public $tokenUrl = 'https://api.line.me/oauth2/v2.1/token';
    public $apiBaseUrl = 'https://api.line.me/v2';

    /** @var string URL สำหรับตรวจสอบ id_token */
    public $verifyUrl = 'https://api.line.me/oauth2/v2.1/verify';

    public function init()
    {
        parent::init();
        if ($this->scope === null) {
            $this->scope = 'profile openid email';
        }
    }

    public function buildAuthUrl(array $params = [])
    {
        // ให้ผู้ใช้เลือกบัญชี/ยืนยันทุกครั้ง ป้องกันการล็อกอินผิดบัญชีบนเครื่องที่ใช้ร่วมกัน
        $params += ['prompt' => 'consent', 'bot_prompt' => 'normal'];
        return parent::buildAuthUrl($params);
    }

    protected function initUserAttributes()
    {
        $profile = $this->api('profile', 'GET');

        $attributes = [
            'id' => $profile['userId'] ?? null,
            'name' => $profile['displayName'] ?? null,
            'picture' => $profile['pictureUrl'] ?? null,
            'email' => null,
        ];

        $idToken = $this->getAccessToken()->getParam('id_token');
        if ($idToken) {
            $claims = $this->verifyIdToken($idToken);
            if (!empty($claims['email'])) {
                $attributes['email'] = $claims['email'];
            }
        }

        return $attributes;
    }

    /**
     * ตรวจ id_token กับเซิร์ฟเวอร์ของ LINE (ตรวจลายเซ็น, aud, exp ให้)
     */
    protected function verifyIdToken(string $idToken): array
    {
        try {
            $response = $this->createRequest()
                ->setMethod('POST')
                ->setUrl($this->verifyUrl)
                ->setData(['id_token' => $idToken, 'client_id' => $this->clientId])
                ->send();
            return $response->isOk ? (array)$response->data : [];
        } catch (\Throwable $e) {
            \Yii::warning('LINE id_token verify failed: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    protected function defaultName()
    {
        return 'line';
    }

    protected function defaultTitle()
    {
        return 'LINE';
    }
}
