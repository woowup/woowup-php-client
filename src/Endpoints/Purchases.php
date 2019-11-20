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

	public function update($purchase)
	{
		$response = $this->put($this->host.'/purchases', $purchase);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function find($invoiceNumber)
	{
		$response = $this->get($this->host . '/purchases', [
			'invoice_number' => $invoiceNumber,
		]);

		if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
	}

	public function findPayment($firstSixDigits)
	{
		$response = $this->get($this->host . '/purchases/iin/' . $firstSixDigits, []);

		if ($response->getStatusCode() == Endpoint::HTTP_OK) {
			$data = json_decode($response->getBody());

			if (isset($data->payload)) {
				return $data->payload;
			}
		}

		return false;
	}
}

?>