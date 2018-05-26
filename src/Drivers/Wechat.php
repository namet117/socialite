<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Wechat extends DriverBase implements DriverInterface
{
    // oauth_api地址
    protected $_base_url = 'https://api.weixin.qq.com/';
    // scope默认授权
    protected $_scope = 'snsapi_userinfo';
    // 此Driver的名称
    protected $_name = 'wechat';

    /**
     * 跳转到微信用户授权界面
     */
    public function authorize($redirect = true)
    {
        $base_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $params = [
            'appid' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
            'scope' => in_array($this->_scope, ['snsapi_userinfo', 'snsapi_base'])
                ? $this->_scope
                : 'snsapi_userinfo',
            'state' => empty($this->_state) ? 'WECHAT' : $this->_state,
        ];
        $url = $this->_buildUrl($base_url, $params, 'wechat_redirect');

        if ($redirect) {
            header("Location: {$url}");
        } else {
            return $url;
        }
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
            $base_url = 'sns/oauth2/access_token';
            $params = [
                'appid'      => $this->_appid,
                'secret'     => $this->_secret,
                'code'       => $this->getCode(),
                'grant_type' => 'authorization_code',
            ];
            $res = $this->get($base_url, ['query' => $params]);
            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response[ 'token' ] = $res;
            // 将得到的数据赋值到属性
            $this->config($res);
        }

        return $this->_access_token;
    }

    /**
     * @desc 判断微信接口返回的数据是否有错误
     *
     * @param array $res 接口返回数据
     * @param bool $throw 验证失败时是否抛出异常
     *
     * @return bool
     * @throws \Namet\Socialite\SocialiteException
     */
    private function _checkError($res, $throw = true)
    {
        if (!empty($res['errcode'])) {
            if ($throw) {
                throw new SocialiteException($res['errcode'] . ' : ' . $res['errmsg']);
            } else {
                return false;
            }
        }

        return true;
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
        if (!$this->_user_info) {
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
            $res = $this->get($base_url, ['query' => $params]);
            // 记录返回的数据
            $this->_response['user'] = $res;
            // 检查返回值是否有错误
            $this->_checkError($res);
            // 格式化返回信息
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
            'uid' => $this->_response['user']['openid'],
            'uname' => $this->_response['user']['nickname'],
            'avatar' => $this->_response['user']['headimgurl'],
        ];

        return $this->_user_info;
    }

    /**
     * @desc 刷新access_token
     *
     * @return bool
     * @throws \Namet\Socialite\SocialiteException
     */
    public function refreshToken()
    {
        $base_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
        $params = [
            'appid' => $this->_appid,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->_refresh_token,
        ];
        // 获取返回值数组
        $res = $this->get($base_url, ['query' => $params]);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;
        // 检查返回值中是否有错误
        $this->_checkError($res);
        // 更新配置
        $this->config($res);

        return true;
    }

    /**
     * @desc 判断access_token是否有效
     *
     * @param bool $throw 无效时是否抛出异常
     *
     * @return bool
     * @throws \Namet\Socialite\SocialiteException
     */
    public function checkToken($throw = false)
    {
        $base_url = 'https://api.weixin.qq.com/sns/auth';
        $params = [
            'access_token' => $this->_access_token,
            'openid' => $this->_openid,
        ];
        // 获取返回值数组
        $res = $this->get($base_url, ['query' => $params]);
        // 记录返回的数据
        $this->_response[__FUNCTION__] = $res;

        // 检查返回值中是否有错误
        return $this->_checkError($res, $throw);
    }
}
