<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Users extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function update($serviceUid, $user)
    {
        $response = $this->put($this->host . '/users/' . $this->encode($serviceUid), $user);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function create($user)
    {
        $response = $this->post($this->host . '/users', $user);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function exist($serviceUid)
    {
        $response = $this->get($this->host . '/users/' . $this->encode($serviceUid) . '/exist', []);

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

    public function find($serviceUid)
    {
        $response = $this->get($this->host . '/users/' . $this->encode($serviceUid), []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function search($page = 0, $limit = 25, $search = '')
    {
        $response = $this->get($this->host . '/users/', [
            'page'   => $page,
            'limit'  => $limit,
            'search' => $search,
        ]);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function getUserTransactions($serviceUid, $concept = '')
    {
        $response = $this->get($this->host . '/users/' . $this->encode($serviceUid) . '/transactions/', [
            'concept' => $concept,
        ]);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function addPoints($serviceUid, $concept, $points, $description)
    {
        $response = $this->post($this->host . '/users/' . $this->encode($serviceUid) . '/points', [
            'concept'     => $concept,
            'points'      => $points,
            'description' => $description,
        ]);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function list($page = 0, $limit = 25, $include = [], $exclude = [])
    {
        $response = $this->get($this->host . '/users/', [
            'page' => $page,
            'limit' => $limit,
            'include' => json_encode($include),
            'exclude' => json_encode($exclude),
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
