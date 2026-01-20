<?php

namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Formatters\EmailFormatter;
use WoowUp\Cleansers\Validators\GenericEmailValidator;
use WoowUp\Cleansers\Validators\LengthValidator;
use WoowUp\Cleansers\Validators\RepeatedValidator;
use WoowUp\Cleansers\Validators\SequenceValidator;

class EmailCleanser
{
    const GENERIC_TLDS    = ["com", "net", "org", "info", "edu", "gov", "mil"];
    const GEOGRAPHIC_TLDS = ["ar", "es", "co", "pe", "bo", "br", "fr", "do", "co.uk"];

    const GMAIL_DOMAINS = [
        'gmail',
        'gamil', 'gmial', 'gmai', 'gmal', 'gnail', 'gmaul', 'gmaol', 'gmaik', 'gmaio',
        'gmeil', 'gmeel', 'gmel',
        'gmaill', 'gmil', 'ggmail', 'gmmail', 'gmailm',
        'gemail', 'gaiml', 'gail', 'gmailcom', 'gmailcomcom',
    ];

    const KNOWN_DOMAINS = [
        'hotmail', 'hot', 'outlook', 'yahoo', 'live', 'msn', 'aol',
        'icloud', 'me', 'mac', 'protonmail', 'proton', 'zoho',
    ];

    /**
     * @var EmailFormatter
     */
    private $formatter;

    /**
     * @var array Array of ValidatorInterface instances
     */
    private $validators;
    private $emailUser;
    private $emailDomain;

    public function __construct()
    {
        $this->formatter = new EmailFormatter();
        $this->validators = [
            new LengthValidator(6, 30),
            new RepeatedValidator(6, false),
            new SequenceValidator(4, false),
            new GenericEmailValidator(),
        ];
        $this->emailDomain = null;
        $this->emailUser   = null;
    }

    /**
     * Sanitizes an email: cleans, normalizes and validates.
     * 
     * Cleans VTEX emails, detects Gmail typos, applies deep cleaning for Gmail,
     * and rejects emails with mixed domains (e.g., gmailhotmail.com).
     * 
     * @param string|numeric $email The email to sanitize
     * @return string|false The sanitized email or false if invalid
     */
    public function sanitize($email)
    {
        if (!is_string($email) && !is_numeric($email)) {
            return false;
        }

        $email = (string) $email;
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $vtexResult = $this->cleanVtexEmail($email);
        if ($vtexResult === false) {
            return false;
        }
        $email = $vtexResult;

        $this->extractEmailParts($email);

        if ($this->emailUser === null || $this->emailDomain === null) {
            return false;
        }

        $isGmail = ($this->emailDomain === '@gmail.com');

        if ($isGmail) {
            $this->emailUser = mb_strtolower(trim((string) $this->emailUser));
            $cleanedUserEmail = $this->formatter->clean((string) $this->emailUser);

            if ($cleanedUserEmail == 'noemail@noemail.com') {
                return $cleanedUserEmail;
            }

            $this->emailUser = $cleanedUserEmail;

            foreach ($this->validators as $validator) {
                if (!$validator->validate($cleanedUserEmail)) {
                    return false;
                }
            }

            $sanitizedEmail = $cleanedUserEmail . '@gmail.com';
        } else {
            $sanitizedEmail = $this->prettify($email);
        }

        return $sanitizedEmail;
    }

    /**
     * Validates if an email has a valid RFC format.
     * 
     * @param string $email The email to validate
     * @return bool true if format is valid, false otherwise
     */
    public function validate($email)
    {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Normalizes an email: converts to lowercase, trims spaces and encodes UTF-8.
     * 
     * @param string $email The email to normalize
     * @return string The normalized email
     */
    public function prettify($email)
    {
        return utf8_encode(mb_strtolower(trim($email)));
    }

    /**
     * Cleans emails from VTEX platform.
     * 
     * Removes VTEX suffixes (e.g., user@gmail.com-123b.ct.vtex.com.br → user@gmail.com)
     * and rejects VTEX hash emails (e.g., 6f62ac8dfa6549b8b8907ed34dfbdb79@ct.vtex.com.br).
     * 
     * @param string $email The email to clean
     * @return string|false The cleaned email or false if invalid VTEX hash
     */
    protected function cleanVtexEmail($email)
    {
        if (stripos($email, '.ct.vtex.com') === false) {
            return $email;
        }

        if (preg_match('/^[a-f0-9]{20,}@ct\.vtex\.com\.br$/i', $email)) {
            return false;
        }

        $dashPos = strpos($email, '-');
        if ($dashPos !== false) {
            return substr($email, 0, $dashPos);
        }

        if (preg_match('/^[a-f0-9]{20,}@ct\.vtex\.com\.br$/i', $email)) {
            return false;
        }

        return $email;
    }

    /**
     * Builds a list of popular TLDs by combining generic and geographic ones.
     * 
     * Generates combinations like: com.ar, net.es, org.co, etc.
     * 
     * @return array List of popular TLDs
     */
    protected function buildPopularTlds()
    {
        $popularTlds = array_merge(self::GENERIC_TLDS, self::GEOGRAPHIC_TLDS);
        foreach (self::GENERIC_TLDS as $genericTld) {
            foreach (self::GEOGRAPHIC_TLDS as $geoTld) {
                $popularTlds[] = $genericTld . "." . $geoTld;
            }
        }

        return $popularTlds;
    }

    /**
     * Extracts user and domain parts from an email.
     * 
     * Detects Gmail typos (gmial, gamil, etc.) and normalizes to @gmail.com.
     * Rejects emails with mixed domains (e.g., gmailhotmail.com).
     * Results stored in $this->emailUser and $this->emailDomain.
     * 
     * @param string $email The email to extract parts from
     * @return void
     */
    protected function extractEmailParts(string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            $this->emailUser   = null;
            $this->emailDomain = null;
            return;
        }

        $lowerEmail = mb_strtolower($email);
        $atPos = strpos($email, '@');

        $domainPart = ($atPos !== false) ? substr($lowerEmail, $atPos) : $lowerEmail;

        $foundGmail = false;
        $foundOtherDomain = false;

        foreach (self::GMAIL_DOMAINS as $gmailDomain) {
            if (strpos($domainPart, $gmailDomain) !== false) {
                $foundGmail = true;
                break;
            }
        }

        if ($foundGmail) {
            foreach (self::KNOWN_DOMAINS as $knownDomain) {
                if (strpos($domainPart, $knownDomain) !== false) {
                    $foundOtherDomain = true;
                    break;
                }
            }
        }

        if ($foundGmail && $foundOtherDomain) {
            $this->emailUser   = null;
            $this->emailDomain = null;
            return;
        }

        foreach (self::GMAIL_DOMAINS as $knownDomain) {
            $pos = strpos($lowerEmail, $knownDomain);
            if ($pos !== false) {
                if ($atPos !== false && $atPos < $pos) {
                    $this->emailUser = substr($email, 0, $atPos);
                } else {
                    $this->emailUser = substr($email, 0, $pos);
                }

                $this->emailDomain = '@gmail.com';
                return;
            }
        }

        if ($atPos !== false) {
            $this->emailUser   = substr($email, 0, $atPos);
            $this->emailDomain = substr($email, $atPos);
            return;
        }

        $this->emailUser   = null;
        $this->emailDomain = null;
    }

    /**
     * Gets the user part of the extracted email.
     * 
     * Only available after calling sanitize() or extractEmailParts().
     * 
     * @return string|null The user part or null if extraction failed
     */
    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    /**
     * Gets the domain part of the extracted email.
     * 
     * Only available after calling sanitize() or extractEmailParts().
     * For normalized Gmail emails, always returns '@gmail.com'.
     * 
     * @return string|null The domain part (including @) or null if extraction failed
     */
    public function getEmailDomain(): ?string
    {
        return $this->emailDomain;
    }
}
