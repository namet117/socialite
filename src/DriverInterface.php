<?php


namespace Namet\Socialite;

interface DriverInterface
{
    public function authorize();

    public function getCode();

    public function getToken();

    public function refreshToken();

    public function getResponse();

    public function getUserInfo();

}
