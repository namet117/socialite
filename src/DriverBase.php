<?php
namespace Namet\Socialite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

abstract class DriverBase
{
    protected $config = [];

    public function config($config)
    {
        $this->config = $config;
    }

    protected function get($url, $params = [])
    {
        $url = $this->_buildUrl($url, $params);

        $client = new Client();
        try {
            $response = $client->get($url);
            $response = \GuzzleHttp\json_decode((string)$response->getBody(), true);

            return $response;
        } catch (Exception $e) {
            throw new SocialiteException($e->getMessage());
        }
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
