<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Banks extends Endpoint
{
    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function getDataFromFirstSixDigits(string $firstSixDigits)
    {
        $response = $this->get($this->host . '/purchases/iin/'.$firstSixDigits);
        if ($response->getStatusCode() != Endpoint::HTTP_OK) {
            return null;
        }

        $data = json_decode($response->getBody());
        return $data->payload ?? null;
    }

    public function getDataFromFirstSixDigitsAsync(string $firstSixDigits)
    {
        return $this->getAsync($this->host . '/purchases/iin/'.$firstSixDigits);
    }
}
