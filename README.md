# Socialite
社会化登陆扩展包，当前包含微信、微博、百度、Github、QQ(`暂不可用，因为审核一直不让过，未完成完整测试😂`)。

# 特点
* 可扩展

# 安装
* 前置要求
    > PHP >= 5.5
* 安装方式
    > composer require "namet/socialite" -vvv

# 使用方法
1. 配置信息
```php
<?php

$config = [
    // 必填项
    'appid' => YOUR_CLIENT_ID,
    'secret' => YOUR_SECRET,
    'redirect_uri' => YOUR_CALLBACK_URL,
    // 选填项
    'scope' => 'SCOPE', // 详情请参照各个开放平台的文档
    'state' => STATE,   // 传过去的参数，服务端也会原样返回，用于防止CSRF
];
```
2. 获取实例，并跳转至认证服务器地址，以`微信`登陆为例：
```php
<?php
use Namet\Socialite\OAuth;

// 当前支持的驱动有：wechat(网页微信接入，非扫码登陆)、weibo(新浪微博)、gitub(Github)、baidu(百度)
/* Step1: 获取OAuth实例 */

// 获取实例 方法1:
$oauth = new OAuth('wechat', $config);
// 或者使用 方法2:
$oauth = new OAuth();
$oauth->driver('wechat')->config($config);

/* Step2: 跳转到认证服务器 */
// 直接跳转至认证服务器，传入true或者不传参数
$oauth->authorize(true);
// 输出跳转到认证服务器的地址
echo $oauth->->authorize(false);
```
3. 在回调地址中获取Code，然后换取Access_token，再获取用户信息
```php
<?php
use Namet\Socialite\OAuth;
use Namet\Socialite\SocialiteException;

try {
    // 获取oauth实例
    $oauth = new OAuth('wechat', $config);

    // 是否开启所有请求结果日志记录，⚠️默认是false
    $oauth->log(true);
    // 若要自定义日志记录方法，请实现 \Namet\Socialite\LogInterface接口
    // 调用handler::handle($data)的参数为一个数组，其结构为：
    // array (
    //    'driver' => 驱动名称,
    //    'request_time' => 请求发送时间 (microtime(true) 方法),
    //    'response_time' => 接收到相应时间 (microtime(true) 方法),
    //    'method' => 调用方式 get/post,
    //    'params' => 请求参数,
    //    'response' => 原始返回数据(字符串),
    // )
    $oauth->setLogHandler(new otherHandler());

    // 获取当前驱动名
    $oauth->getDriver(); // 当前结果有：wechat、weibo、github、baidu

    // 一次性获取全部常用信息 (⚠️ 0.4版本及之后新增)，格式为:
    // array (
    //    'uid' => 该平台的唯一id 可以此来区分用户,
    //    'uname' => 用户昵称,
    //    'avatar' => 头像url,
    //    'access_token' => access_token值,
    //    'expire_time' => token过期时间 (有的接口没有该字段，默认为''),
    //    'refresh_token' => refresh_token值 (有的接口没有该字段，默认为''),
    // )

    // 直接获取用户信息数组, 格式为:
    // array (
    //    'uid' => 该平台的唯一id 可以此来区分用户,
    //    'uname' => 用户昵称,
    //    'avatar' => 头像url,
    // )
    $user = $oauth->getUserInfo();

    // 获取用户access_token(返回值是string类型)
    $access_token = $oauth->getToken();

    // 获取认真服务器返回的code(返回值是string类型)
    $code = $oauth->getCode();

    // 刷新access_token ⚠️当前只有wechat、baidu有该接口
    $oauth->refreshToken();
    $new_token = $oauth->getToken();

    // 验证access_token是否有效。若已无效，当传入参数为false时，返回结果为false；反之则会抛出异常
    $bool = $oauth->checkToken(false); // ⚠️只有当driver为wechat的时候才可以使用此方法

    // 获取请求服务端返回的原始数据信息（数组形式）
    // 当不传入$key参数时, 获取所有返回数据(二维数组)
    // $key可以：token(换取access_token时返回数据)、
    //          user(获取用户信息时返回数据)、
    //          refresh(刷新token时返回数据)、
    //          check(验证oken时返回数据，`⚠️目前仅有wechat提供接口，其他不可用`)
    // 当$key对应数据不存在时，返回的是空数组
    $response = $oauth->getResponse($key);

} catch(SocialiteException $e) {
    // 所有报错信息都将抛出异常，捕获后可进行处理
    echo $e->getMessage();
}
```
# 扩展驱动
1. 首先需要自定义驱动实现`\Namet\Socialite\DriverInterface`接口
2. 在获取OAuth实例之后进行驱动注册。(获取新OAuth实例时不需要再次注册。😊)
```php
<?php
use Namet\Socialite\OAuth;

$oauth = new OAuth;
// 注册驱动
$oauth->registerDriver('new_driver', \NAMESPACE\TO\NEW_DRIVER::class);
// 使用注册后的驱动
$oauth->driver('new_driver')->config($new_config);
...
```

# LICENSE
MIT
