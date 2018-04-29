<?php

namespace Namet\Socialite;

/**
 * Interface DriverInterface
 *
 * @package Namet\Socialite
 */
interface DriverInterface
{
    /**
     * @desc 跳转到用户权限授权界面
     */
    public function authorize();

    /**
     * @desc 用户授权后，获取返回的code数据
     */
    public function getCode();

    /**
     * @desc 获取access_token
     */
    public function getToken();

    /**
     * @desc 使用refresh_token刷新access_token
     */
    public function refreshToken();

    /**
     * @desc 使用access_token 获取用户数据
     *
     * @param string $lang 返回数据的语言
     */
    public function getUserInfo($lang);

}
