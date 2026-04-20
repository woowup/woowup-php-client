<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Events extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
    }

    public function create($event)
    {
        $response = $this->post($this->host . '/events', $event);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }
}
