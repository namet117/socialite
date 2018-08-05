<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

/**
 * Class Oschina
 *
 * @link https://www.oschina.net/openapi
 *
 * @package Namet\Socialite\Drivers
 */
class Oschina extends DriverBase implements DriverInterface
{
    // oauth_api地址
    protected $_base_url = 'https://www.oschina.net/action/';
    // 此Driver的名称
    protected $_name = 'oschina';
    // scope默认授权
    protected $_scope = '';
    // 错误提示信息
    private $_errorMap = [
        'invalid_request' => '无效请求（缺少必要参数）',
        'invalid_client' => 'client_id无效',
        'invalid_grant' => '授权方式无效',
        'unauthorized_client' => '应用未授权',
        'unsupported_grant_type' => '不支持的授权方式',
    ];

    // 跳转认证服务器
    public function authorize($redirect = true)
    {
        return $this->redirect('oauth2/authorize', $redirect);
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
                'dataType' => 'json',
            ];
            !empty($this->_state) && $params['state'] = $this->_state;

            $res = $this->get('openapi/token', [
                'query' => $params,
                'headers' => [
                    'User-Agent' => 'nameT-Blog-Server',
                ]
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
        if (!empty($res['error_code']) || !empty($res['error'])) {
            $error = !empty($res['error_code']) ? $res['error_code'] : $res['error'];
            // 认证服务器返回数据和获取用户信息接口返回数据格式不一致。
            $msg = $error . ': ' .
                (isset($this->_errorMap[$error])
                    ? $this->_errorMap[$error]
                    : (empty($res['error_description']) ? '' : $res['error_description']));

            throw new SocialiteException($msg);
        }
    }

    /**
     * 检查认证服务器返回code时是否有错误
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    public function checkCode()
    {
        $this->_checkError($_GET);
    }

    /**
     * @desc 根据access_token获取用户基本信息
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo()
    {
        if (!$this->_user_info) {
            $res = $this->get('openapi/user', [
                'query' => [
                    'access_token' => $this->getToken(),
                    'dataType' => 'json',
                ],
                'headers' => [
                    'User-Agent' => 'nameT-Blog-Server',
                ],
            ]);
            // 检查返回值是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response['user'] = $res;

            return $this->_formatUserInfo();
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
            'avatar' => $this->_response['user']['avatar'],
            'email' => $this->_response['user']['email'],
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
        throw new SocialiteException('api does not exist');
    }
}
