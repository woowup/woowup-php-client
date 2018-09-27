<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Branches extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function update($branchId, $branch)
    {
        $response = $this->put($this->host . '/branches/' . $branchId, $branch);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function create($branch)
    {
        $response = $this->post($this->host . '/branches', $branch);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function getBranch($branchId)
    {
        $response = $this->get($this->host . '/branches/' . $branchId, []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function getBranches($page = 0, $limit = 10)
    {
        $response = $this->get($this->host . '/branches/', [
            'page'   => $page,
            'limit'  => $limit,
        ]);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

}