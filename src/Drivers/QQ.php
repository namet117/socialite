<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Weibo extends DriverBase implements DriverInterface
{
    // client_id
    private $_appid = null;
    // client_secret
    private $_secret = null;
    // 跳转链接
    private $_redirect_uri = null;
    // 接口返回的原始数据存储
    private $_response = [];
    // 用户授权后，得到的code参数
    private $_code = null;
    // 用户的token
    private $_access_token = null;
    // weibo的oauth_api固定域名
    private $_base_url = 'https://graph.qq.com/';

    /**
     * 跳转到用户授权界面
     */
    public function authorize()
    {
        $params = [
            'client_id' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
        ];
        !empty($this->_state) && $params['state'] = $this->_state;
        !empty($this->_scope) && $params['scope'] = $this->_scope;

        $this->redirect($this->_base_url . 'oauth2.0/authorize', $params);
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
            $params = [
                'client_id' => $this->_appid,
                'client_secret' => $this->_secret,
                'code' => $this->getCode(),
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->_redirect_uri,
            ];
            !empty($this->_state) && $params['state'] = $this->_state;

            $res = $this->get($this->_base_url . 'oauth2.0/token', ['query' => $params]);

            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response[__FUNCTION__] = $res;
            // 将得到的数据赋值到属性
            $this->config($res);
        }

        return $this->_access_token;
    }

    // 获取openid
    public function getOpenId()
    {
        if (!$this->_open_id) {
            $params = [
                'access_token' => $this->getToken(),
            ];
            $res = $this->get($this->_base_url . 'oauth2.0/me', ['query' => $params]);
            // 检查是否有错误
            $this->_checkError($res);

            $this->config($res);
        }

        return $this->_open_id;
    }

    /**
     * @desc 根据key获取接口返回的原始数据的数组
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
     * @desc 判断接口返回的数据是否有错误
     *
     * @param array $res 请求的结果
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    private function _checkError($res)
    {
        if (!empty($res['error_code'])) {
            throw new SocialiteException($res['error_code'] . ' : ' . $res['error']);
        }
    }

    /**
     * @desc 根据access_token获取用户基本信息
     *
     * @param string $lang 语言：zh_CN
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo($lang = 'zh_CN')
    {

        $res = $this->get($this->_base_url . 'user/get_user_info', [
            'query' => [
                'access_token' => $this->getAccessToken(),
                'oauth_consumer_key' => $this->_appid,
                'opneid' => $this->getOpenId(),
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        // 检查返回值是否有错误
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;

        return $res;
    }

    public function refreshToken()
    {
        $params = [
            'appid' => $this->config['appid'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->config['refresh_token'],
        ];
        // 获取返回值数组
        $res = $this->get($this->_base_url . 'oauth2.0/token', ['query' => $params]);
        // 检查返回值中是否有错误
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;
        // 更新配置
        $this->config($res);

        return $this;
    }

    public function checkToken()
    {
        throw new SocialiteException('无此方法: ' . __FUNCTION__);
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
            $this->$k = $v;
        }

        return $this;
    }
}
