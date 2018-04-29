<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class QQ extends DriverBase implements DriverInterface
{
    // client_id
    protected $_appid = null;
    // client_secret
    protected $_secret = null;
    // 跳转链接
    protected $_redirect_uri = null;
    // 接口返回的原始数据存储
    protected $_response = [];
    // 用户授权后，得到的code参数
    protected $_code = null;
    // 用户的token
    protected $_access_token = null;
    // oauth_api地址
    protected $_base_url = 'https://graph.qq.com/';
    // 此Driver的名称
    protected $_name = 'qq';

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

    /**
     * @desc 根据access_token获取openid
     *
     * @return mixed
     * @throws \Namet\Socialite\SocialiteException
     */
    public function getOpenId()
    {
        if (!$this->_open_id) {
            $params = [
                'access_token' => $this->getToken(),
            ];
            $res = $this->get($this->_base_url . 'oauth2.0/me', ['query' => $params]);
            // 检查是否有错误
            $this->_checkError($res);
            // 将得到的数据赋值到属性
            $this->config($res);
        }

        return $this->_open_id;
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
     * @param string $lang
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo($lang = '')
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

    /**
     * @desc 刷新access_token
     *
     * @return $this
     * @throws \Namet\Socialite\SocialiteException
     */
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
}
