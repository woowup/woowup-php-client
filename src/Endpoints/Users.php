<?php
namespace WoowUp\Endpoints;
/**
*
*/
class Users extends Endpoint
{
	function __construct($host, $apikey)
	{
		parent::__construct($host, $apikey);
	}

	public function update($serviceUid, $user)
	{
		$response = $this->put($this->host.'/users/'.$this->encode($serviceUid), $user);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function create($user)
	{
		$response = $this->post($this->host.'/users', $user);

		return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
	}

	public function exist($serviceUid)
	{
		$response = $this->get($this->host.'/users/'.$this->encode($serviceUid).'/exist', []);

		if ($response->getStatusCode() == Endpoint::HTTP_OK) {
			$data = json_decode($response->getBody());

			return isset($data->payload) && isset($data->payload->exist) && $data->payload->exist;
		}

		return false;
	}

	protected function encode($uid)
	{
		return urlencode(base64_encode($uid));
	}
}

?>