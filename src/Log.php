<?php
namespace Namet\Socialite;

class Log implements LogInterface
{
    public function handle($data)
    {
        file_put_contents('log1.log', date('Y-m-d H:i:s') . '：' .  print_r($data, true) . "\n\n", FILE_APPEND);
    }
}