<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Products extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
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

    public function create($product)
    {
        $response = $this->post($this->host . '/products', $product);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function update($sku, $product)
    {
        $response = $this->put($this->host . '/products/' . $this->encode($sku), $product);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            return isset($data->payload) && isset($data->payload->exist) && $data->payload->exist;
        }

        return false;
    }

    protected function encode($sku)
    {
        return urlencode(base64_encode($sku));
    }
}
