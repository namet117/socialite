<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;

class Wechat implements DriverInterface
{
    private $_code = null;

    private $_response = null;

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

        header("Location: {$base_url}?" . http_build_query($params) . '#wechat_redirect');
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
                'appid' => $this->config['']
            ];
        }

        return $this->_reponse;
    }

    public function getUserInfo()
    {
        // TODO: Implement getUserInfo() method.
    }

    public function refreshAccessToken()
    {
        // TODO: Implement refreshToken() method.
    }
}
