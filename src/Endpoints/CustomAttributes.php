<?php
namespace WoowUp\Endpoints;

class CustomAttributes extends Endpoint
{
	const ENTITIES = [
		'customers' 	=> 'custom-attributes',
		'purchases'		=> 'purchase-custom-attributes',
		'purchase-item' => 'purchase-item-custom-attributes',
		'products'		=> 'product-custom-attributes',
	];

    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }


    public function getCustomAttributesDefinition($entity = 'customers')
    {
        $response = $this->get($this->host . '/account/' . self::ENTITIES[$entity], []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function updateAttributeDefinition($data, $entity)
    {
    	$response = $this->put($this->host . '/account/' . self::ENTITIES[$entity] . '/' . $data->name, $data);

    	return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;

    }

    public function create($customAttribute, $entity)
    {
        try {
            $response = $this->post($this->host.'/account/'.self::ENTITIES[$entity], $customAttribute);
            return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
        } catch (\Exception $e) {
            throw new \Exception("Custom Attribute is not valid", 1);
        }
    }
}

