<?php
namespace Namet\Socialite;

use Namet\Socialite\Drivers\Baidu;
use Namet\Socialite\Drivers\Github;
use Namet\Socialite\Drivers\QQ;
use Namet\Socialite\Drivers\Wechat;
use Namet\Socialite\Drivers\Weibo;


/**
 * Class OAuth
 *
 * @method authorize
 * @method getCode
 * @method getToken
 * @method getUserInfo
 * @method refreshToken
 * @method checkToken
 * @method getResponse
 *
 * @package Namet\Socialite
 */
class OAuth
{
    // 所有可用驱动
    private static $driver_list = [
        'wechat' => Wechat::class,
        'qq' => QQ::class,
        'weibo' => Weibo::class,
        'baidu' => Baidu::class,
        'github' => Github::class,
    ];
    // 可用驱动实例
    private static $drivers_instance = [];
    // 当前使用的驱动对象
    public $driver = null;

    /**
     * OAuth constructor.
     *
     * @param string $driver
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    public function __construct($driver = '', $config = [])
    {
        if ($driver) {
            $this->_getInstance($driver);
            $config && $this->driver->config($config);
        }
    }

    /**
     * @desc 注册可用驱动
     *
     * @param $name
     * @param $driver
     */
    public function registerDriver($name, $driver)
    {
        self::$driver_list[$name] = $driver;
    }

    /**
     * @desc 创建驱动实例
     *
     * @param $driver
     */
    private function _createInstance($driver)
    {
        $class = self::$driver_list[$driver];
        $instance = new $class;
        if (!$instance instanceof DriverInterface) {
            throw new SocialiteException("{$class} 必须实现DriverInterface接口");
        }

        self::$drivers_instance[$driver] = $instance;
        $this->driver = $instance;
    }

    /**
     * @desc 获取驱动实例，如果已经存在实例则直接返回，若不存在则创建
     *
     * @param $driver
     */
    private function _getInstance($driver)
    {
        if (!isset(self::$driver_list[$driver])) {
            throw new SocialiteException('不存在的驱动：' . $driver);
        }

        if (isset(self::$drivers_instance[$driver])) {
            $this->driver = self::$drivers_instance[$driver];
        } else {
            $this->_createInstance($driver);
        }
    }

    /**
     * @desc 设置驱动
     *
     * @param $driver
     *
     * @return $this
     */
    public function driver($driver)
    {
        $this->_getInstance($driver);

        return $this;
    }

    /**
     * @desc 调用Driver的config方法
     *
     * @param $config
     *
     * @return $this
     */
    public function config($config)
    {
        $this->driver->config($config);

        return $this;
    }

    /**
     * @desc 获取所有可用驱动列表
     *
     * @return array  name => driver
     */
    public function getDriverList()
    {
        return self::$driver_list;
    }

    /**
     * @desc 调用Driver方法
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     * @throws \Namet\Socialite\SocialiteException
     */
    public function __call($name, $arguments)
    {
        if (!$this->driver) {
            throw new SocialiteException('请先设置驱动!');
        }

        return call_user_func_array([$this->driver, $name], $arguments);
    }

    /**
     * @desc 是否记录所有认证服务器返回的数据
     *
     * @param bool $log
     *
     * @return $this
     */
    public function withLog($log = false)
    {
        $this->driver->log($log);

        return $this;
    }

    /**
     * @param \Namet\Socialite\LogInterface $handler
     *
     * @return $this
     */
    public function setLogHandler(LogInterface $handler)
    {
        $this->driver->setLogHandler($handler);

        return $this;
    }

}
