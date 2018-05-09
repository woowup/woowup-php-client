<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class UserEvents extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function create($event)
    {
        $response = $this->post($this->host . '/user-events', $event);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }
}
