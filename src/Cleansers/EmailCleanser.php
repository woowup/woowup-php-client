<?php

namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Formatters\EmailFormatter;
use WoowUp\Cleansers\Validators\GenericEmailValidator;
use WoowUp\Cleansers\Validators\LengthValidator;
use WoowUp\Cleansers\Validators\RepeatedValidator;
use WoowUp\Cleansers\Validators\SequenceValidator;

/**
 * Email sanitizer and validator
 *
 * Process flow:
 * 1. Type validation and normalization
 * 2. Clean VTEX platform emails
 * 3. Extract and validate email parts (user and domain)
 * 4. Apply Gmail-specific cleaning for Gmail domains
 * 5. Return sanitized email or false if invalid
 */
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

    const INVALID_EMAIL = 'noemail@noemail.com';

    private $formatter;
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
     */
    public function sanitize($email)
    {
        if (!$this->isValidInput($email)) {
            return false;
        }

        $email = $this->normalizeInput($email);
        $email = $this->cleanVtexEmail($email);

        if ($email === false) {
            return false;
        }

        $this->extractEmailParts($email);

        if (!$this->hasValidParts()) {
            return false;
        }

        return $this->isGmailDomain()
            ? $this->sanitizeGmailEmail()
            : $this->prettify($email);
    }

    /**
     * Validates if an email has a valid RFC format.
     */
    public function validate($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Normalizes an email: converts to lowercase, trims spaces and encodes UTF-8.
     */
    public function prettify($email)
    {
        return utf8_encode(mb_strtolower(trim($email)));
    }

    /**
     * Cleans emails from VTEX platform.
     */
    protected function cleanVtexEmail($email)
    {
        if (stripos($email, '.ct.vtex.com') === false) {
            return $email;
        }

        if ($this->isVtexHashEmail($email)) {
            return false;
        }

        return $this->removeVtexSuffix($email);
    }

    /**
     * Builds a list of popular TLDs by combining generic and geographic ones.
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
     * Handles multiple @ symbols and Gmail typo detection.
     */
    protected function extractEmailParts(string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            $this->resetEmailParts();
            return;
        }

        $lowerEmail = mb_strtolower($email);

        if ($this->hasMixedDomains($lowerEmail)) {
            $this->resetEmailParts();
            return;
        }

        // First check for Gmail domains (with typo correction)
        if ($this->extractGmailParts($email, $lowerEmail)) {
            return;
        }

        // Standard email processing
        $cleanedEmail = $this->removeExtraAtSymbols($email);
        $this->extractStandardParts($cleanedEmail);
    }

    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    public function getEmailDomain(): ?string
    {
        return $this->emailDomain;
    }

    // Private helper methods

    private function isValidInput($email): bool
    {
        return is_string($email) || is_numeric($email);
    }

    private function normalizeInput($email): string
    {
        return trim((string) $email);
    }

    private function hasValidParts(): bool
    {
        return $this->emailUser !== null && $this->emailDomain !== null;
    }

    private function isGmailDomain(): bool
    {
        return $this->emailDomain === '@gmail.com';
    }

    private function sanitizeGmailEmail()
    {
        $this->emailUser = mb_strtolower(trim($this->emailUser));
        $cleanedUserEmail = $this->formatter->clean($this->emailUser);

        if ($cleanedUserEmail === self::INVALID_EMAIL) {
            return $cleanedUserEmail;
        }

        if (!$this->validateGmailUser($cleanedUserEmail)) {
            return false;
        }

        $this->emailUser = $cleanedUserEmail;
        return $cleanedUserEmail . '@gmail.com';
    }

    private function validateGmailUser(string $userEmail): bool
    {
        foreach ($this->validators as $validator) {
            if (!$validator->validate($userEmail)) {
                return false;
            }
        }
        return true;
    }

    private function isVtexHashEmail(string $email): bool
    {
        return preg_match('/^[a-f0-9]{20,}@ct\.vtex\.com\.br$/i', $email) === 1;
    }

    private function removeVtexSuffix(string $email): string
    {
        $dashPos = strpos($email, '-');
        return $dashPos !== false ? substr($email, 0, $dashPos) : $email;
    }

    private function resetEmailParts(): void
    {
        $this->emailUser = null;
        $this->emailDomain = null;
    }

    /**
     * Removes extra @ symbols keeping only the last valid one for the domain.
     * Example: user@name@domain.com -> username@domain.com
     */
    private function removeExtraAtSymbols(string $email): string
    {
        $lastAtPos = strrpos($email, '@');

        if ($lastAtPos === false) {
            return $email;
        }

        $userPart = substr($email, 0, $lastAtPos);
        $domainPart = substr($email, $lastAtPos);

        // Remove all @ symbols from user part
        $cleanUserPart = str_replace('@', '', $userPart);

        return $cleanUserPart . $domainPart;
    }

    /**
     * Checks if email contains mixed domains (Gmail + another provider).
     */
    private function hasMixedDomains(string $lowerEmail): bool
    {
        $hasGmail = $this->containsGmailDomain($lowerEmail);

        if (!$hasGmail) {
            return false;
        }

        return $this->containsOtherKnownDomain($lowerEmail);
    }

    private function containsGmailDomain(string $lowerEmail): bool
    {
        foreach (self::GMAIL_DOMAINS as $gmailDomain) {
            if (strpos($lowerEmail, $gmailDomain) !== false) {
                return true;
            }
        }
        return false;
    }

    private function containsOtherKnownDomain(string $lowerEmail): bool
    {
        foreach (self::KNOWN_DOMAINS as $knownDomain) {
            if (strpos($lowerEmail, $knownDomain) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts Gmail email parts with typo correction.
     * Removes extra @ symbols from user part.
     * Returns true if Gmail domain was found and extracted.
     */
    private function extractGmailParts(string $email, string $lowerEmail): bool
    {
        foreach (self::GMAIL_DOMAINS as $gmailVariant) {
            $pos = strpos($lowerEmail, $gmailVariant);

            if ($pos !== false) {
                // Extract everything before the Gmail domain and remove @ symbols
                $userPart = substr($email, 0, $pos);
                $this->emailUser = str_replace('@', '', $userPart);
                $this->emailDomain = '@gmail.com';
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts standard email parts (non-Gmail).
     */
    private function extractStandardParts(string $email): void
    {
        $atPos = strpos($email, '@');

        if ($atPos === false) {
            $this->resetEmailParts();
            return;
        }

        $this->emailUser = substr($email, 0, $atPos);
        $this->emailDomain = substr($email, $atPos);
    }
}
