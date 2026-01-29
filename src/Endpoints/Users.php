<?php
namespace WoowUp\Endpoints;

/**
 *
 */
class Users extends Endpoint
{
    protected const TELEPHONE_CLEANED = 'telephone_cleaned';
    protected const TELEPHONE_REJECTED = 'telephone_rejected';
    protected const TELEPHONE_VALIDATED = 'telephone_validated';
    protected const EMAIL_CLEANED = 'email_cleaned';
    protected const EMAIL_REJECTED = 'email_rejected';
    protected const EMAIL_VALIDATED = 'email_validated';

    public function __construct($host, $apikey)
    {
        parent::__construct($host, $apikey);

        $this->enableSanitization = true;
        $this->sanitizationCallables = [
            [
                'path' => ['street'],
                'callable' => fn($v) => $this->cleanser->street->truncate($v),
            ],
            [
                'path' => ['gender'],
                'callable' => fn($v) => $this->cleanser->gender->sanitize($v),
            ],
            [
                'path' => ['birthdate'],
                'callable' => fn($v) => $this->cleanser->birthdate->sanitize($v),
            ],
        ];
    }

    public function update($serviceUid, $user)
    {
        $user = $this->cleanTelephone($user);
        $response = $this->put($this->host . '/users/' . $this->encode($serviceUid), $user);

        return $response->getStatusCode() == Endpoint::HTTP_OK || $response->getStatusCode() == Endpoint::HTTP_CREATED;
    }

    public function updateAsync($user) // should not be used
    {
        return $this->put($this->host.'/users/'.$this->encode($user['service_uid']), $user);
    }

    public function create($user)
    {
        $user = $this->cleanTelephone($user);
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

    public function removeTags($userAppId, $tags)
    {
        if (is_array($tags)) {
            $tags = implode(',', $tags);
        }

        $params = [
            'remove_tags' => $tags
        ];

        $response = $this->put($this->host . '/users/' . $userAppId, $params);

        return $response->getStatusCode() == Endpoint::HTTP_OK;
    }

    public function search($page = 0, $limit = 25, $search = '', $include = [], $exclude = [], $segmentId = '')
    {
        $response = $this->get($this->host . '/users/', [
            'page'    => $page,
            'limit'   => $limit,
            'search'  => $search,
            'include' => json_encode($include),
            'exclude' => json_encode($exclude),
            'segment_id' => $segmentId,
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

    protected function cleanTelephone($data){
        $originalTelephone = $data['telephone'] ?? null;

        if (!$originalTelephone) {
            return $data;
        }

        if ($this->cleanser->telephone->hasApiRejectedPatterns($originalTelephone)) {
            unset($data['telephone']);
            return $data;
        }

        $sanitizedTelephone = $this->cleanser->telephone->sanitize($originalTelephone);

        if ($sanitizedTelephone === false) {
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::TELEPHONE_REJECTED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::TELEPHONE_CLEANED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::TELEPHONE_VALIDATED);

            $data['whatsapp_enabled'] = 'disabled';
            $data['whatsapp_enabled_reason'] = 'other';
            $data['sms_enabled'] = 'disabled';
            $data['sms_enabled_reason'] = 'other';
            return $data;
        }

        $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::TELEPHONE_VALIDATED);
        $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::TELEPHONE_REJECTED);

        if ($originalTelephone !== $sanitizedTelephone) {
            $data['telephone'] = $sanitizedTelephone;
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::TELEPHONE_CLEANED);
        } else {
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::TELEPHONE_CLEANED);
        }

        return $data;
    }

    protected function cleanEmail($data)
    {
        $originalEmail = $data['email'] ?? null;

        if (!$originalEmail) {
            return $data;
        }

        $sanitizedEmail = $this->cleanser->email->sanitize($originalEmail);

        if ($sanitizedEmail === false) {
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_REJECTED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_CLEANED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_VALIDATED);
            return $data;
        }

        $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_VALIDATED);
        $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_REJECTED);

        if ($originalEmail !== $sanitizedEmail) {
            $data['email'] = $sanitizedEmail;
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_CLEANED);
        } else {
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_CLEANED);
        }

        return $data;
    }
}
