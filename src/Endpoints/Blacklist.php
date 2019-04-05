<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Blacklist extends Endpoint
{
    const ACTION_CREATE        = 'create';
    const ACTION_DELETE_CREATE = 'delete-create';

    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);
    }

    public function create($file, $type)
    {
        $response = $this->postFile($this->host . '/account/blacklist', $file, [
            'type'      => self::ACTION_CREATE,
            'blacklist' => $type,
        ]);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function deleteCreate($file, $type)
    {
        $response = $this->postFile($this->host . '/account/blacklist', $file, [
            'type'      => self::ACTION_DELETE_CREATE,
            'blacklist' => $type,
        ]);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function delete($type)
    {
        $response = parent::delete($this->host . '/account/blacklist?type=' . $type);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }
}
