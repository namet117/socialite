<?php
namespace Namet\Socialite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class DriverBase
 *
 * @method get
 * @method post
 *
 * @package Namet\Socialite
 */
abstract class DriverBase
{
    private $_client = null;

    protected $_log = true;

    protected $_log_handler = null;

    public function __call($name, $arguments)
    {
        try {
            // 暂只允许GET，POST这两个方法
            if (!in_array($name, ['get', 'post'])) {
                throw new \Exception("不可用的方法：{$name} !");
            }
            $request_time = time();
            $response = call_user_func_array([$this->getClient(), $name], $arguments);
            $response_time = time();
            // 如果开启了日志记录
            if ($this->_log) {
                // 如果未指定LogHandler，则使用默认的
                $handler = $this->_log_handler ?: new Log;
                $log_data = [
                    'driver' => $this->_name,
                    'request_time' => $request_time,
                    'response_time' => $response_time,
                    'method' => $name,
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
        !$this->_client && $this->_client = new Client();

        return $this->_client;
    }

    /**
     * @desc 跳转到指定链接
     *
     * @param string $url
     * @param array  $params
     * @param string $href
     */
    protected function redirect($url, $params = [], $href = '')
    {
        $url = $this->_buildUrl($url, $params, $href);

        header("Location: {$url}");
    }

    /**
     * @desc 构造url
     *
     * @param string $url 基础url
     * @param array $params 附带的get参数
     * @param string $href 附带的锚点
     *
     * @return string
     */
    private function _buildUrl($url, $params, $href = '')
    {
        return "{$url}?" . http_build_query($params) . ($href ? ('#' . urlencode($href)) : '' );
    }
}
