<?php


namespace Namet\Socialite;

interface DriverInterface
{
    public function setConfig();

    public function authorize();

    public function getCode();

    public function getAccessToken();

    public function refreshToken();

    public function getResource();

    public function getUserInfo();

}
