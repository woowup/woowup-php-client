<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Account extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
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
