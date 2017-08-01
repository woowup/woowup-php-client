<?php
namespace WoowUp\Endpoints;

/**
*
*/
class Endpoint
{
	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_TOO_MANY_REQUEST = 429;
	const HTTP_BAD_REQUEST = 403;
	const HTTP_NOT_FOUND = 404;

	protected $host;
	protected $apikey;
	protected $http;

	function __construct($host, $apikey)
	{
		$this->host = $host;
		$this->apikey = $apikey;
		$this->http = new \GuzzleHttp\Client();
	}

	protected function get($url, $params)
	{
		return $this->http->request('GET', $url, [
			'query' => $params,
			'headers' => [
				'Authorization' => 'Basic '.$this->apikey,
				'Accept' => 'application/json'
			]
		]);
	}

	protected function post($url, $data)
	{
		return $this->http->request('POST', $url, [
			'json' => $data,
			'headers' => [
				'Authorization' => 'Basic '.$this->apikey,
				'Accept' => 'application/json'
			]
		]);
	}

	protected function postForm($url, $params)
	{
		return $this->http->request('POST', $url, [
			'form_params' => $params,
			'headers' => [
				'Authorization' => 'Basic '.$this->apikey,
				'Accept' => 'application/json'
			]
		]);
	}

	protected function put($url, $data)
	{
		return $this->http->request('PUT', $url, [
			'json' => $data,
			'headers' => [
				'Authorization' => 'Basic '.$this->apikey,
				'Accept' => 'application/json'
			]
		]);
	}

	protected function delete($url)
	{
		return $this->http->request('DELETE', $url, [
			'headers' => [
				'Authorization' => 'Basic '.$this->apikey,
				'Accept' => 'application/json'
			]
		]);
	}

}

?>