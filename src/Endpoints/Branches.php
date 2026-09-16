<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Branches extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
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

    /**
     * Resolves a branch by name the same way the API does when a purchase carries `branch_name`,
     * so the id returned is the one that purchase writes and deletes act on. The API answers 200
     * with an empty payload when the branch does not exist.
     */
    public function findByName($branchName)
    {
        $response = $this->get($this->host . '/branches/' . $this->encode($branchName), []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (!empty($data->payload->id)) {
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