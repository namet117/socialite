<?php


namespace Namet\Socialite;

interface DriverInterface
{
    public function authorize();

    public function getCode();

    public function getAccessToken();

    public function refreshAccessToken();

    public function getResponse();

    public function getUserInfo();

}
