<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class AbandonedCarts extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function create($serviceUid, $cart)
    {
        $response = $this->post($this->host . '/users/'.$this->encode($serviceUid).'/abandoned-cart', $cart);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }
}
