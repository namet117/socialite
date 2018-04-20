<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Wechat extends DriverBase implements DriverInterface
{
    private $_code = null;

    private $_response = null;

    private $_access_token = null;

    private $_openid = null;

    // 跳转到微信认证系统
    public function authorize()
    {
        $base_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $params = [
            'appid' => $this->config['appid'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => (
                    isset($this->config['scope'])
                    && in_array($this->config['scope'], ['snsapi_userinfo', 'snsapi_base'])
                )
                ? $this->config['scope']
                : 'snsapi_userinfo',
            'state' => empty($this->config['state']) ? 'WECHAT' : $this->config['state'],
        ];

        $this->redirect($base_url, $params, 'wechat_redirect');
    }

    public function getCode()
    {
        $this->_code = $this->_code ?: $_GET['code'];

        return $this->_code;
    }

    public function getToken()
    {
        if (!$this->_access_token) {
            $this->getResponse();
        }

        return $this->_access_token;
    }

    public function getResponse()
    {
        if (!$this->_response) {
            $base_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
            $params = [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
                'code' => $this->getCode(),
                'grant_type' => 'authorization_code',
            ];

            $res = $this->get($base_url, $params);
            $this->_checkError($res);
            $this->_access_token = $res['access_token'];
            $this->_openid = $res['openid'];
            $this->_response = $res;
echo '<hr>get access_token<hr>';
print_r($res);
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
        $base_url = 'https://api.weixin.qq.com/sns/userinfo';
        $params = [
            'access_token' => $this->getToken(),
            'openid' => $this->_openid,
            'lang' => $lang
        ];
        $res = $this->get($base_url, $params);
        $this->_checkError($res);

        return $res;
    }

    public function refreshToken()
    {
        $base_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
        $params = [
            'appid' => $this->config['appid'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->config['refresh_token'],
        ];

        $res = $this->get($base_url, $params);
        $this->_checkError($res);
// TODO 设置类的属性.
        return $res;
    }

    public function checkToken()
    {
        $base_url = 'https://api.weixin.qq.com/sns/auth';
        $params = [
            'access_token' => $this->_access_token,
            'openid' => $this->_openid,
        ];

        $res = $this->get($base_url, $params);
        $this->_checkError($res);
// TODO 已失效情况下的返回数据待验证.
        return true;
    }
}
