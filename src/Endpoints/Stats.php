<?php
namespace WoowUp\Endpoints;

/**
 * 
 */
class Stats extends Endpoint
{
	public function __construct($host, $apikey)
	{
		parent::__construct($host, $apikey);
	}

	public function create($stats)
	{
		$response = $this->post($this->host . '/integration-stats', $stats);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function search($page = 0, $limit = 25, $from = null, $to = null)
	{
		$query = [
			'page'  => $page,
			'limit' => $limit
		];

		if ($from) {
			$query['from'] = $from;
		}

		if ($to) {
			$query['to'] = $to;
		}

		$response = $this->get($this->host . '/integration-stats', $query);

		if ($response->getStatusCode() == Endpoint::HTTP_OK) {
			$data = json_encode($response->getBody());

			if (isset($data->payload)) {
				return $data->payload;
			}
		}

		return false;
	}
}