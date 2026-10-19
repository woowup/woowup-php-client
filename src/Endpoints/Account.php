<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Account extends Endpoint
{
    public function __construct($host, $apikey, \GuzzleHttp\ClientInterface $http = null)
    {
        parent::__construct($host, $apikey, $http);
    }

    public function customAttributes()
    {
        $response = $this->get($this->host . '/account/custom-attributes', []);

        if ($response->getStatusCode() == Endpoint::HTTP_OK) {
        	$data = json_decode($response->getBody());

        	return $data->payload;
        }
    }

    /**
     * Identifiers enabled in the account's multi-id, as GET /account returns them in
     * `payload.multidentificator_settings`: one key per enabled identifier (document, email,
     * telephone, service_uid) with its type, e.g. ['document' => ['type' => 'hard'], ...].
     * Disabled identifiers are not in the object.
     *
     * Never throws: returns null when the settings cannot be read (the request fails, the status
     * is not 200, or the payload has no non-empty `multidentificator_settings` object), so the
     * caller can tell "unknown" apart from a real configuration.
     *
     * @return array|null
     */
    public function multidentificatorSettings()
    {
        try {
            $response = $this->get($this->host . '/account', []);
        } catch (\Throwable $e) {
            return null;
        }

        if ($response->getStatusCode() != Endpoint::HTTP_OK) {
            return null;
        }

        $data     = json_decode((string) $response->getBody(), true);
        $settings = $data['payload']['multidentificator_settings'] ?? null;

        return is_array($settings) && !empty($settings) ? $settings : null;
    }
}
