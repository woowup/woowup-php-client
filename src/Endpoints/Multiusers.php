<?php
namespace WoowUp\Endpoints;

class Multiusers extends Endpoint
{
    const INVALID_EMAIL = 'noemail@noemail.com';
    protected static $DEFAULT_IDENTITY = [
        'document'    => '',
        'email'       => '',
        'service_uid' => '',
        'telephone'   => '',
    ];

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

    protected function cleanEmail($data){
        $originalEmail = $data['email'] ?? null;
        if (!$originalEmail) {
            return $data;
        }


        $sanitizedEmail = $this->cleanser->email->sanitize($originalEmail);

        if ($sanitizedEmail === false || $sanitizedEmail === self::INVALID_EMAIL) {
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_REJECTED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_CLEANED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_VALIDATED);

            $data['mailing_enabled'] = 'disabled';
            $data['mailing_enabled_reason'] = 'other';

            if ($sanitizedEmail === self::INVALID_EMAIL) {
                $localPart =
                    ($data['document']    ?? null) ?:
                        ($data['service_uid'] ?? null) ?:
                            ($data['telefono']    ?? null);

                $data['email'] = $localPart
                    ? $localPart . '@noemail.com'
                    : $sanitizedEmail;
            }

            return $data;
        }

        $emailDomain = $this->cleanser->email->getEmailDomain();
        $isGmail = ($emailDomain === '@gmail.com');

        if (!$isGmail) {
            if ($originalEmail !== $sanitizedEmail) {
                $data['email'] = $sanitizedEmail;
            }
            return $data;
        }

        $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_REJECTED);

        if ($originalEmail !== $sanitizedEmail) {
            $data['email'] = $sanitizedEmail;
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_CLEANED);
        } else {
            $data['tags'] = $this->cleanser->tags->addTag($data['tags'] ?? '', self::EMAIL_VALIDATED);
            $data['tags'] = $this->cleanser->tags->removeTag($data['tags'] ?? '', self::EMAIL_CLEANED);
        }

        return $data;
    }
}
