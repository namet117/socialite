<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Weibo extends DriverBase implements DriverInterface
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
    // 用户信息
    protected $_user_info = [];
    // weibo的oauth_api固定域名
    protected $_base_url = 'https://api.weibo.com/';
    // 此Driver的名称
    protected $_name = 'weibo';

    /**
     * 跳转到用户授权界面
     */
    public function authorize()
    {
        $params = [
            'client_id' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
            'scope' => !empty($this->_scope) ? $this->_scope : 'email',
        ];

        !empty($this->_state) && $params['state'] = $this->_state;

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

            $res = $this->post(
                $this->_base_url . 'oauth2/access_token',
                ['form_params' => $params, 'headers' => ['Accept' => 'application/json']]
            );

            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response['token'] = $res;
            // 将得到的数据赋值到属性
            $this->config($res);
        }

        return $this->_access_token;
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
     * @param string $lang 语言：
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo($lang = '')
    {
        if (!$this->_user_info) {
            $res = $this->get($this->_base_url.'2/users/show.json', [
                'query' => [
                    'access_token' => $this->getToken(),
                    'uid' => $this->_uid,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
            // 检查返回值是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response['user'] = $res;

            $this->_formatUserInfo();
        }

        return $this->_user_info;
    }

    /**
     * 格式化用户数据
     *
     * @return array
     */
    private function _formatUserInfo()
    {
        $this->_user_info = [
            'uid' => $this->_response['user']['id'],
            'uname' => $this->_response['user']['name'],
            'avatar' => $this->_response['user']['avatar_large'],
        ];

        return $this->_user_info;
    }

    /**
     * @desc 貌似微博开放平台没有提供此方法
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    public function refreshToken()
    {
        throw new SocialiteException('暂未实现此方法');
    }
}
