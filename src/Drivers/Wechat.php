<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;

class Wechat implements DriverInterface
{
    private $code = null;

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
        !$this->code && $this->code = $_GET['code'];
    }

    public function getAccessToken()
    {
        $resource = $this->resource;

        return $this->resource['access_token']
    }

    public function getResource()
    {

        $this->getJson();
    }

    public function getUserInfo()
    {
        // TODO: Implement getUserInfo() method.
    }

    public function refreshToken()
    {
        // TODO: Implement refreshToken() method.
    }
}
