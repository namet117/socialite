<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Github extends DriverBase implements DriverInterface
{
    // oauth_api地址
    protected $_base_url = 'https://github.com/login/oauth/';
    // scope默认授权
    protected $_scope = 'user:email';
    // 此Driver的名称
    protected $_name = 'github';

    /**
     * 跳转到用户授权界面
     */
    public function authorize($redirect = true)
    {
        return $this->redirect('authorize', $redirect);
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
                'redirect_uri' => $this->_redirect_uri,
            ];
            !empty($this->_state) && $params['state'] = $this->_state;

            $res = $this->post('access_token', [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => $params
            ]);

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
        if (!empty($res['error']) || !empty($res['error_code'])) {
            // 百度认证服务器返回数据和获取用户信息接口返回数据格式不一致。
            $msg = isset($res['error'])
                ? ($res['error'] . ' : ' . $res['error_description'])
                : ($res['error_code'] . ' : ' . $res['error_msg']);

            throw new SocialiteException($msg);
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
        if (!$this->_user_info) {
            $res = $this->get('https://api.github.com/user', [
                'query' => [
                    'access_token' => $this->getToken(),
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
            'avatar' => $this->_response['user']['avatar_url'],
        ];

        return $this->_user_info;
    }

    /**
     * @desc 刷新access_token
     *
     * @return $this
     * @throws \Namet\Socialite\SocialiteException
     */
    public function refreshToken()
    {
        throw new SocialiteException('暂未实现');
    }
}
