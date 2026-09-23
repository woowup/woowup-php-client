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

    /**
     * Queues the deletion of a branch AND of every purchase in it: the API answers as soon as it
     * takes the request and a worker does the work later, so a 200 here means the request was
     * accepted, never that the branch is gone. The body carries the `request_id` that identifies
     * that work, which is why this returns it instead of a bool, and `notify_to` is the only way
     * anyone learns it finished: with an account apikey the API has no other recipient.
     *
     * The branch is identified by id in the body, not in the URL, and the id must be the one the
     * account owns: the API answers 404 otherwise.
     *
     * @param int         $branchId
     * @param string|null $notifyTo email the API writes to when the deletion finishes
     * @return array the decoded payload, `['request_id' => int]` when it was accepted
     */
    public function delete($branchId, $notifyTo = null)
    {
        $body = ['id' => (int) $branchId];

        if (!empty($notifyTo)) {
            $body['notify_to'] = $notifyTo;
        }

        $response = $this->deleteJson($this->host . '/branches', $body);

        if ($response->getStatusCode() != Endpoint::HTTP_OK) {
            return [];
        }

        $data = json_decode((string) $response->getBody(), true);

        return isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [];
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