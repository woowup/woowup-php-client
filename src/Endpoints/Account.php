<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Account extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
    }

    public function customAttributes()
    {
        $response = $this->get($this->host . '/account/custom-attributes', []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
        	$data = json_decode($response->getBody());

        	return $data->payload;
        }
    }
}
