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
    private $_base_url = 'https://api.weibo.com/';

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

        $this->redirect($this->_base_url . 'oauth2/authorize', $params);
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
            $res = $this->post($this->_base_url . 'oauth2/access_token', $params);
            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response[__FUNCTION__] = $res;
            // 将得到的access_token赋值到属性
            $this->_access_token = $res['access_token'];
            // 保存uid
            $this->_uid = $res['uid'];
        }

        return $this->_access_token;
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
        if (!in_array($lang, ['zh_CN'])) {
            throw new SocialiteException('unsupported language :' . $lang);
        }

        $params = [
            'access_token' => $this->getToken(),
            'openid' => $this->_openid,
            'lang' => $lang
        ];
        // 获取数组
        // $res = $this->get($this->_base_url . 'oauth2/get_token_info', $params);

        $res = $this->get($this->_base_url.'/2/users/show.json', [
            'query' => [
                'uid' => $this->_uid,
                'access_token' => $this->_access_token,
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
        throw new SocialiteException('不存在的接口:' . __FUNCTION__);
    }

    public function checkToken()
    {
        throw new SocialiteException('不存在的接口:' . __FUNCTION__);
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
