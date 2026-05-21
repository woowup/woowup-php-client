<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Products extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
    }

    public function exist($sku)
    {
        $response = $this->get($this->host.'/products/'.$this->encode($sku).'/exist', []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            return isset($data->payload) && isset($data->payload->exist) && $data->payload->exist;
        }

        return false;
    }

    public function existAsync($sku) // returns promise
    {
        return $this->getAsync($this->host.'/products/'.$this->encode($sku).'/exist', []);
    }

    public function find($sku)
    {
        $response = $this->get($this->host . '/products/' . $this->encode($sku), []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function create($product)
    {
        $product  = $this->normalizeCustomAttributeKeys($product);
        $response = $this->post($this->host . '/products', $product);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function createAsync($product) // returns promise
    {
        $product = $this->normalizeCustomAttributeKeys($product);
        return $this->postAsync($this->host.'/products', $product);
    }

    public function update($sku, $product)
    {
        $product  = $this->normalizeCustomAttributeKeys($product);
        $response = $this->put($this->host . '/products/' . $this->encode($sku), $product);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            return isset($data->payload) && isset($data->payload->exist) && $data->payload->exist;
        }

        return false;
    }

    public function updateAsync($sku, $product) // returns promise
    {
        $product = $this->normalizeCustomAttributeKeys($product);
        return $this->putAsync($this->host.'/products/'.$this->encode($sku), $product);
    }

    private function normalizeCustomAttributeKeys(array $product): array
    {
        if (empty($product['custom_attributes']) || !is_array($product['custom_attributes'])) {
            return $product;
        }

        $product['custom_attributes'] = $this->cleanser->customAttributes->normalizeKeys($product['custom_attributes']);

        return $product;
    }

    public function search($search = [], $page = 0, $limit = 100)
    {
        $params = array_merge($search, ['page' => $page, 'limit' => $limit]);

        $response = $this->get($this->host . '/products', $params);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            return isset($data->payload) ? $data->payload : [];
        }

        return false;
    }

    protected function encode($sku)
    {
        return urlencode(base64_encode($sku));
    }
}
