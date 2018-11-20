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

    public function update($branchName, $branch)
    {
        $response = $this->put($this->host . '/branches/' . base64_encode($branchName), $branch);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function create($branch)
    {
        $response = $this->post($this->host . '/branches', $branch);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function find($branchId)
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

    public function search($page = 0, $limit = 10)
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