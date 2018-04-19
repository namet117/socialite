<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Wechat extends DriverBase implements DriverInterface
{
    private $_code = null;

    private $_response = null;

    private $access_token = null;

    // 跳转到微信认证系统
    public function authorize()
    {
        $base_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $params = [
            'appid' => $this->config['appid'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => in_array($this->config['scope'], ['snsapi_userinfo', 'snsapi_base'])
                ? $this->config['scope'] : 'snsapi_userinfo',
            'state' => $this->config['state'] ?? 'WECHAT',
        ];

        $this->redirect($base_url, $params, 'wechat_redirect');
    }

    public function getCode()
    {
        !$this->_code && $this->_code = $_GET['code'];

        return $this->_code;
    }

    public function getAccessToken()
    {
        $_response = $this->getResponse();

        return $this->_response['access_token'];
    }

    public function getResponse()
    {
        if (!$this->_response) {
            $base_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
            $params = [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
                'code' => $this->_code,
                'grant_type' => 'authorization_code',
            ];

            $res = $this->get($base_url, $params);
            $this->_checkError($res);
            $this->_access_token = $res['access_token'];
            $this->_response = $res;
        }

        return $this->_reponse;
    }

    private function _checkError($res)
    {
        if (!empty($res['errcode'])) {
            throw new SocialiteException($res['errcode'] . ' : ' . $res['errmsg']);
        }
    }

    /**
     * @param string $lang
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo($lang = 'zh_CN')
    {
        if (!in_array($lang, ['zh_CN', 'zh_TW', 'en'])) {
            throw new SocialiteException('unsupported language :' . $lang);
        }
//        https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
        $base_url = 'https://api.weixin.qq.com/sns/userinfo';
        $params = [
            'access_token' => $this->_access_token,
            'openid' => $this->_openid,
            'lang' => $lang
        ];
        $res = $this->get($base_url, $params);
        $this->_checkError($res);

        return $res;
    }

    public function refreshAccessToken()
    {
        // TODO: Implement refreshToken() method.
    }
}
