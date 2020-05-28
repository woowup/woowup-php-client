<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Endpoint
{
    const HTTP_OK               = 200;
    const HTTP_CREATED          = 201;
    const HTTP_TOO_MANY_REQUEST = 429;
    const HTTP_BAD_REQUEST      = 403;
    const HTTP_NOT_FOUND        = 404;

    const MAX_ATTEMPTS = 5;

    protected static $retryResponses = [self::HTTP_TOO_MANY_REQUEST];

    protected $host;
    protected $apikey;
    protected $http;

    public function __construct($host, $apikey)
    {
        $this->host   = $host;
        $this->apikey = $apikey;
        $this->http   = new \GuzzleHttp\Client();
    }

    protected function get($url, $params = [])
    {
        return $this->request('GET', $url, [
            'query'   => $params,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function getAsync($url, $params = [])
    {
        return $this->requestAsync('GET', $url, [
            'query'   => $params,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function post($url, $data)
    {
        return $this->request('POST', $url, [
            'json'    => $data,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function postAsync($url, $data)
    {
        return $this->requestAsync('POST', $url, [
            'json'    => $data,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function postForm($url, $params)
    {
        return $this->request('POST', $url, [
            'form_params' => $params,
            'headers'     => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function postFile($url, $file, $params)
    {
        $multipart = [
            [
                'name'     => 'file',
                'contents' => is_resource($file) ? $file : fopen($file, 'r'),
            ],
        ];
        foreach ($params as $key => $value) {
            $multipart[] = [
                'name'     => $key,
                'contents' => $value,
            ];
        }

        return $this->request('POST', $url, [
            'multipart' => $multipart,
            'headers'   => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function put($url, $data)
    {
        return $this->request('PUT', $url, [
            'json'    => $data,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function putAsync($url, $data)
    {
        return $this->requestAsync('PUT', $url, [
            'json'    => $data,
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function delete($url)
    {
        return $this->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $this->apikey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    protected function request($verb, $url, $params)
    {
        $attempts = 0;
        while ($attempts < self::MAX_ATTEMPTS) {
            try {
                return $this->http->request($verb, $url, $params);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($e->hasResponse() && in_array($e->getResponse()->getStatusCode(), self::$retryResponses) && $attempts <= self::MAX_ATTEMPTS) {
                    // sleep 1, 2, 4, 8, ... seconds
                    sleep(pow(2, $attempts));
                    $attempts++;
                } else {
                    throw $e;
                }
            }
        }

        throw new \Exception("Max request attempts reached");
    }

    protected function requestAsync($verb, $url, $params)
    {
        return $this->http->requestAsync($verb, $url, $params);
    }

    protected function encode($string)
    {
        return urlencode(base64_encode($string));
    }
}
