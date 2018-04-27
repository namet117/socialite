<?php
namespace Namet\Socialite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

abstract class DriverBase
{
    protected $client = null;

    public function __call($name, $arguments)
    {
        try {
            $response = call_user_func_array([$this->getClient(), 'get'], $arguments);
             // $this->getClient()->get($url, $arguments);
            $response = \GuzzleHttp\json_decode((string)$response->getBody(), true);

            return $response;
        } catch (\Exception $e) {
            throw new SocialiteException($e->getMessage());
        }
    }

    protected function getClient()
    {
        !$this->client && $this->client = new Client();

        return $this->client;
    }

    protected function redirect($url, $params = [], $href = '')
    {
        $url = $this->_buildUrl($url, $params, $href);

        header("Location: {$url}");
    }

    private function _buildUrl($url, $params, $href = '')
    {
        return "{$url}?" . http_build_query($params) . ($href ? ('#' . urlencode($href)) : '' );
    }
}
