<?php
namespace WoowUp\Endpoints;

class Multiusers extends Endpoint
{
    protected static $DEFAULT_IDENTITY = [
        'document'    => '',
        'email'       => '',
        'service_uid' => '',
        'telephone'   => '',
    ];

    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);

        $this->enableSanitization = true;
        $this->sanitizationCallables = [
            [
                'path' => ['street'],
                'callable' => fn($v) => $this->cleanser->street->truncate($v),
            ],
        ];
    }

    public function update($user)
    {
        $response = $this->put($this->host . '/multiusers', $user);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function updateAsync($user) // returns promise
    {
        return $this->putAsync($this->host.'/multiusers', $user);
    }

    public function exist($identity)
    {
        $identity = array_merge(self::$DEFAULT_IDENTITY, $identity);

        $response = $this->get($this->host . '/multiusers/exist', $identity);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            return isset($data->payload) && isset($data->payload->exist) && $data->payload->exist;
        }

        return false;
    }

    public function existAsync($identity) // returns promise
    {
        $identity = array_merge(self::$DEFAULT_IDENTITY, $identity);

        return $this->getAsync($this->host.'/multiusers/exist', $identity);
    }

    public function find($identity)
    {
        $identity = array_merge(self::$DEFAULT_IDENTITY, $identity);
        $response = $this->get($this->host . '/multiusers/find', $identity);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function getUserTransactions($identity, $concept = '')
    {
        $identity = array_merge(self::$DEFAULT_IDENTITY, $identity);
        $params   = array_merge($identity, [
            'concept' => $concept,
        ]);

        $response = $this->get($this->host . '/multiusers/transactions', $params);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
            $data = json_decode($response->getBody());

            if (isset($data->payload)) {
                return $data->payload;
            }
        }

        return false;
    }

    public function addPoints($identity, $concept, $points, $description)
    {
        $identity = array_merge(self::$DEFAULT_IDENTITY, $identity);
        $params   = array_merge($identity, [
            'concept'     => $concept,
            'points'      => $points,
            'description' => $description,
        ]);

        $response = $this->post($this->host . '/multiusers/points', $params);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function createAbandonedCart($cart)
    {
        $response = $this->post($this->host . '/multiusers/abandoned-cart', $cart);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    protected function cleanTelephone($data){
        $originalTelephone = $data['telephone'] ?? null;

        if (!$originalTelephone) {
            return $data;
        }

        $sanitizedTelephone = $this->cleanser->telephone->sanitize($originalTelephone);

        if ($sanitizedTelephone === false) {
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', 'telephone_rejected');
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', 'telephone_cleaned');

            $data['whatsapp_enabled'] = 'disabled';
            $data['whatsapp_enabled_reason'] = 'other';
            $data['sms_enabled'] = 'disabled';
            $data['sms_enabled_reason'] = 'other';
           return $data;
        }

        $data['telephone'] = $sanitizedTelephone;
        $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', 'telephone_cleaned');
        $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', 'telephone_rejected');

        return $data;
    }
}
