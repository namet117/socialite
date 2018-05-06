<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Wechat extends DriverBase implements DriverInterface
{
    // 微信接口返回的原始数据存储
    protected $_response = [];
    // 微信用户授权后，得到的code参数
    protected $_code = null;
    // 用户的token
    protected $_access_token = null;
    // openid
    protected $_openid = null;
    // 刷新token
    protected $_resfresh_token = null;
    // 此Driver的名称
    protected $_name = 'wechat';

    /**
     * 跳转到微信用户授权界面
     */
    public function authorize()
    {
        $base_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $params = [
            'appid' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
            'scope' => (
                    isset($this->_scope)
                    && in_array($this->_scope, ['snsapi_userinfo', 'snsapi_base'])
                )
                ? $this->_scope
                : 'snsapi_userinfo',
            'state' => empty($this->_state) ? 'WECHAT' : $this->_state,
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
                'appid'      => $this->_appid,
                'secret'     => $this->_secret,
                'code'       => $this->getCode(),
                'grant_type' => 'authorization_code',
            ];
            $res = $this->get($base_url, ['query' => $params]);
            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response[ __FUNCTION__ ] = $res;
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
        $this->_response[__FUNCTION__] = $res;
        // 检查返回值是否有错误
        $this->_checkError($res);

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

        return $this;
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
