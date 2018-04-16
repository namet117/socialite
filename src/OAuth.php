<?php
namespace Namet\Socialite;

use Namet\Socialite\Drivers\QQ;
use Namet\Socialite\Drivers\Wechat;
use Namet\Socialite\Drivers\Weibo;

class OAuth
{
    // 所有可用驱动
    private static $driver_list = [
        'wechat' => Wechat::class,
        'qq' => QQ::class,
        'weibo' => Weibo::class,
    ];
    // 可用驱动实例
    private static $drivers_instance = [];
    // 当前使用的驱动对象
    private $driver = null;

    /**
     * OAuth constructor.
     *
     * @param string $driver
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    public function __construct($driver = '')
    {
        if ($driver) {
            $this->getInstance($driver);
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
    private function createInstance($driver)
    {
        $class = self::$driver_list[$driver];
        $instance = new $class;
        if (! $instance instanceof DriverInterface) {
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
    private function getInstance($driver)
    {
        if (!isset(self::$driver_list[$driver])) {
            throw new SocialiteException('不存在的驱动：' . $driver);
        }

        if (isset(self::$drivers_instance[$driver])) {
            $this->driver = self::$drivers_instance[$driver];
        } else {
            $this->createInstance($driver);
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
        $this->createInstance($driver);

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

}
