<?php
namespace Namet\Socialite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class DriverBase
 *
 * @package Namet\Socialite
 */
abstract class DriverBase
{
    // guzzlehttp客户端
    private $_client = null;
    // 是否开启日志记录
    protected $_log = false;
    // 处理日志记录的实例
    protected $_log_handler = null;
    /* ----------- 属性 ----------- */
    // 接口返回的原始数据存储
    protected $_response = [];
    // client_id
    protected $_appid = null;
    // client_secret
    protected $_secret = null;
    // 跳转链接
    protected $_redirect_uri = null;
    // 用户授权后，得到的code参数
    protected $_code = null;
    // 用户的token
    protected $_access_token = null;
    // 用户信息
    protected $_user_info = [];
    // oauth_api地址
    protected $_base_url = '';
    // 链接锚点
    protected $_anchor = '';
    // scope
    protected $_scope = '';
    // state
    protected $_state = '';

    /**
     * 获取或跳转到认证链接
     *
     * @param string $uri 认证接口地址
     * @param bool $redirect 跳转/否则返回认证地址
     *
     * @return string
     */
    protected function redirect($uri, $redirect = true)
    {
        $params = [
            'client_id' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
        ];

        !empty($this->_state) && $params['state'] = $this->_state;
        !empty($this->_scope) && $params['scope'] = $this->_scope;

        $url = $this->_buildUrl($this->_base_url . $uri, $params);

        if ($redirect) {
            header("Location: {$url}");
        } else {
            return $url;
        }
    }

    /**
     * @desc 构造url
     *
     * @param string $url 基础url
     * @param array $params 附带的get参数
     * @param string $anchor 附带的锚点
     *
     * @return string
     */
    protected function _buildUrl($url, $params, $anchor = '')
    {
        return "{$url}?" . http_build_query($params) . ($anchor ? ('#' . urlencode($anchor)) : '' );
    }

    /**
     * get方式调用接口
     *
     * @param $url
     * @param $params
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    protected function get()
    {
        $this->_request('get', func_get_args());
    }

    /**
     * post方式调用接口
     *
     * @param $url
     * @param $params
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    protected function post()
    {
        $this->_request('post', func_get_args());
    }

    /**
     * 利用http client发送请求
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed
     * @throws \Namet\Socialite\SocialiteException
     */
    private function _request($method, $arguments)
    {
        try {
            $request_time = time();
            $response = $this->getClient()->$method($arguments[0], $arguments[1]);
            $response_time = time();
            // 如果开启了日志记录
            if ($this->_log) {
                // 如果未指定LogHandler，则使用默认的
                $handler = $this->_log_handler ?: new Log;
                $log_data = [
                    'driver' => $this->getDriver(),
                    'request_time' => $request_time,
                    'response_time' => $response_time,
                    'method' => $method,
                    'params' => print_r($arguments, true),
                    'response' => (string)$response->getBody(),
                ];
                $handler->handle($log_data);
            }
            $data = \GuzzleHttp\json_decode((string)$response->getBody(), true);

            return $data;
        } catch (\Exception $e) {
            throw new SocialiteException($e->getMessage());
        }
    }

    /**
     * 获取当前驱动名称
     *
     * @return mixed
     */
    public function getDriver()
    {
        return $this->_name;
    }

    /**
     * @desc 载入配置信息
     *
     * @param array $config
     *
     * @return $this
     */
    public function config($config)
    {
        foreach ($config as $k => $v) {
            $k = "_{$k}";
            $this->$k = $v;
        }

        return $this;
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
     * 获取所有基本信息
     *
     * @return array
     */
    public function infos()
    {
        $user_info = $this->getUserInfo();
        $extend = [
            'driver' => $this->getDriver(),
            'access_token' => $this->_access_token,
            'expire_time' =>'',
            'refresh_token' => empty($this->_refresh_token) ? '' : $this->_refresh_token,
        ];

        return array_merge($user_info, $extend);
    }

    /**
     * @desc 设置是否开启日志记录
     *
     * @param $flag
     */
    public function log($flag)
    {
        $this->_log = $flag ? 1 : 0;
    }

    /**
     * @desc 设置记录日志的LogHandler
     *
     * @param $handler
     */
    public function setLogHandler($handler)
    {
        $this->_log_hanlder = $handler;
    }

    /**
     * @desc 获取GuzzleHttp的Client实例
     *
     * @return \GuzzleHttp\Client
     */
    protected function getClient()
    {
        !$this->_client && $this->_client = new Client(['base_url' => $this->_base_url]);

        return $this->_client;
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
}
