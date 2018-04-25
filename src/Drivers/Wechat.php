<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Wechat extends DriverBase implements DriverInterface
{
    // 微信接口返回的原始数据存储
    private $_response = [];
    // 微信用户授权后，得到的code参数
    private $_code = null;
    // 用户的token
    private $_access_token = null;
    // openid
    private $_openid = null;
    // 刷新token
    private $_resfresh_token = null;

    /**
     * 跳转到微信用户授权界面
     */
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

    /**
     * @desc 获取连接中的code参数
     *
     * @return string
     */
    public function getCode()
    {
        $this->_code = $this->_code ?: $_GET['code'];

        return $this->_code;
    }

    /**
     * @desc 获取access token
     *
     * @return string Access Token
     * @throws \Namet\Socialite\SocialiteException
     */
    public function getToken()
    {
        if (!$this->_access_token) {
            $base_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
            $params = [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
                'code' => $this->getCode(),
                'grant_type' => 'authorization_code',
            ];
            $res = $this->get($base_url, $params);
            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response[__FUNCTION__] = $res;
            // 将得到的access_token赋值到属性
            $this->_access_token = $res['access_token'];
            // 将得到的openid赋值到属性
            $this->_openid = $res['openid'];
        }

        return $this->_access_token;
    }

    /**
     * @desc 根据key获取微信接口返回的原始数据的数组
     *
     * @param string $key getToken/getUserInfo/refreshToken/checkToken
     *
     * @return array|mixed
     * @throws \Namet\Socialite\SocialiteException
     */
    public function getResponse($key = '')
    {
        if ($key) {
            if (!isset($this->_response[$key])) {
                throw new SocialiteException("undefined key {$key} in response array");
            }

            return $this->_response[$key];
        } else {
            return $this->_response;
        }
    }

    /**
     * @desc 判断微信接口返回的数据是否有错误
     *
     * @param array $res 请求的结果
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    private function _checkError($res)
    {
        if (!empty($res['errcode'])) {
            throw new SocialiteException($res['errcode'] . ' : ' . $res['errmsg']);
        }
    }

    /**
     * @desc 根据access_token获取用户基本信息
     *
     * @param string $lang 语言：zh_CN/zh_TW/en
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
        // 获取数组
        $res = $this->get($base_url, $params);
        // 检查返回值是否有错误
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;

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
        // 获取返回值数组
        $res = $this->get($base_url, $params);
        // 检查返回值中是否有错误
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;
        // 更新配置
        $this->setConfig($res);

        return $this;
    }

    public function checkToken()
    {
        $base_url = 'https://api.weixin.qq.com/sns/auth';
        $params = [
            'access_token' => $this->_access_token,
            'openid' => $this->_openid,
        ];
        // 获取返回值数组
        $res = $this->get($base_url, $params);
        // 检查返回值中是否有错误  TODO 已失效情况下的返回数据待验证.
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;

        return true;
    }

    /**
     * @desc 根据类中的属性赋值配置值
     *
     * @param array $config
     */
    public function config($config)
    {
        foreach ($config as $k => $v) {
            $k = "_{$k}";
            if (isset($this->$k)) {
                $this->$k = $v;
            }
        }

        return $this;
    }
}
