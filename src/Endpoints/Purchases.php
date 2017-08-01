<?php
namespace WoowUp\Endpoints;
/**
*
*/
class Purchases extends Endpoint
{
	function __construct($host, $apikey)
	{
		parent::__construct($host, $apikey);
	}

	public function bulkCreate($purchases)
	{
		$response = $this->post($this->host.'/purchases/bulk', $purchases);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function create($purchase)
	{
		$response = $this->post($this->host.'/purchases', $purchase);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}
}

?>