<?php
namespace WoowUp\Endpoints;

/**
 * Product categories of an account. They are never created through this endpoint: they appear as a
 * side effect of uploading products, so only reading the tree and deleting it are available.
 */
class Categories extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
    }

    /**
     * The whole category tree of the account, flattened, with `id`, `path` and `name`. It is not
     * paginated: one call brings every node of every level.
     *
     * @return array|bool the categories, or false when the API did not answer them
     */
    public function list()
    {
        $response = $this->get($this->host . '/categories', []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode((string) $response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    /**
     * Queues the deletion of EVERY category of the account: the endpoint takes no filter, and the
     * API answers as soon as it takes the request, so a 200 means it was accepted, never that the
     * catalog is already without categories. A worker then clears the category of each product —
     * the products stay— and soft deletes the categories. The body carries the `request_id` of that
     * work, which is why this returns it instead of a bool, and `notify_to` is the only way anyone
     * learns it finished: with an account apikey the API has no other recipient.
     *
     * @param string|null $notifyTo email the API writes to when the deletion finishes
     * @return array the decoded payload, `['request_id' => int]` when it was accepted
     */
    public function deleteBulk($notifyTo = null)
    {
        $body = empty($notifyTo) ? (object) [] : ['notify_to' => $notifyTo];

        $response = $this->deleteJson($this->host . '/categories/bulk', $body);

        if ($response->getStatusCode() != Endpoint::HTTP_OK) {
            return [];
        }

        $data = json_decode((string) $response->getBody(), true);

        return isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [];
    }
}
