<?php
namespace WoowUp\Endpoints;
/**
*
*/
class Purchases extends Endpoint
{
	public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
	{
		parent::__construct($host, $apikey, $http);

        $this->enableSanitization = true;
        $this->sanitizationCallables = [
            [
                'path' => ['customer','street'],
                'callable' => fn($v) => $this->cleanser->street->truncate($v),
            ]
        ];
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

	public function createAsync($purchase) // returns promise
	{
		return $this->postAsync($this->host.'/purchases', $purchase);
	}

	public function update($purchase)
	{
		$response = $this->put($this->host.'/purchases', $purchase);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function updateAsync($purchase) // returns promise
	{
		return $this->putAsync($this->host.'/purchases', $purchase);
	}

	/**
	 * The API answers at most one purchase per invoice number. When the same invoice exists in
	 * several branches, pass `branch_id` in $params to get the one from that branch.
	 */
	public function find($invoiceNumber, $params = [])
	{
		$params = array_merge([
			'invoice_number' => $invoiceNumber,
		], $params);

		$response = $this->get($this->host . '/purchases', $params);

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

	/**
	 * Soft deletes a purchase. Without a branch name the API resolves the invoice number alone and
	 * deletes the first match from any branch, so callers that know the branch must pass it.
	 * Deleting an already deleted purchase responds 404, which surfaces as a RequestException.
	 *
	 * @param string|int  $invoiceNumber
	 * @param string|null $branchName
	 * @return bool
	 */
	public function delete($invoiceNumber, $branchName = null)
	{
		$body = ['invoice_number' => $invoiceNumber];

		if (!empty($branchName)) {
			$body['branch_name'] = $branchName;
		}

		$response = $this->deleteJson($this->host . '/purchases', $body);

		return $response->getStatusCode() == Endpoint::HTTP_OK;
	}

    protected function cleanTelephone($data){
        $originalTelephone = $data['telephone'] ?? null;

        if (!$originalTelephone) {
            return $data;
        }

        if ($this->cleanser->telephone->hasApiRejectedPatterns($originalTelephone)) {
            unset($data['telephone']);
            return $data;
        }

        $sanitizedTelephone = $this->cleanser->telephone->sanitize($originalTelephone);

        if ($sanitizedTelephone === false) {
            return $data;
        }

        $data['telephone'] = $sanitizedTelephone;

        return $data;
    }

    protected function cleanEmail($data)
    {
        $originalEmail = $data['email'] ?? null;

        if (!$originalEmail) {
            return $data;
        }

        $sanitizedEmail = $this->cleanser->email->sanitize($originalEmail);

        if ($sanitizedEmail !== false) {
            $data['email'] = $sanitizedEmail;
        }

        return $data;
    }
}