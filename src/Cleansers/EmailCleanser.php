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
        'gamil', 'gmial', 'gmai', 'gmal', 'gnail', 'gmaul', 'gmaol', 'gmaiol', 'gmaik', 'gmaio',
        'gmeil', 'gmeel', 'gmel',
        'gmaill', 'gmil', 'ggmail', 'gmmail', 'gmailm',
        'gemail', 'gaiml', 'gail', 'gmailcom', 'gmailcomcom',
    ];

    const KNOWN_DOMAINS = [
        'hotmail', 'hot', 'outlook', 'yahoo', 'live', 'msn',
        'icloud', 'mac', 'protonmail', 'proton', 'zoho',
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
            new SequenceValidator(7, 6, false),
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

        $email = $this->removeDniPrefix($email);

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
        // Check for VTEX hash email first (xxx@ct.vtex.com.br)
        if ($this->isVtexHashEmail($email)) {
            return false;
        }

        // Check for real email with VTEX suffix (user@domain.com-xxx.ct.vtex.com.br)
        if (stripos($email, '.ct.vtex.com') === false) {
            return $email;
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
        $domainPart = $this->getDomainPart($lowerEmail);

        if ($this->hasMixedDomains($domainPart)) {
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

    /**
     * Removes a DNI prefix from VTEX-style emails.
     * Pattern: {7-8 digits}-{user}@{domain}-{suffix}
     * Example: "27204905-mariabiblio1979@gmail.com-264200337326b.c" -> "mariabiblio1979@gmail.com-264200337326b.c"
     *
     * Only applies when there is also a suffix after the domain (the VTEX tracking ID),
     * to avoid false positives with legitimate emails like "12345678-user@gmail.com".
     */
    private function removeDniPrefix(string $email): string
    {
        if (preg_match('/^\d{7,8}-(.+@.+\..+-.+)$/', $email, $matches)) {
            return $matches[1];
        }

        return $email;
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
     * Returns the domain part of an email (from @ onwards), or full string if no @.
     * Used for mixed-domain detection so we only match in the domain, not the user part
     * (e.g. "almacentqv@gmail.com" must not match "mac" in "almacen").
     */
    private function getDomainPart(string $lowerEmail): string
    {
        $atPos = strrpos($lowerEmail, '@');
        return $atPos !== false ? substr($lowerEmail, $atPos) : $lowerEmail;
    }

    /**
     * Checks if the domain part contains mixed domains (Gmail + another provider).
     */
    private function hasMixedDomains(string $domainPart): bool
    {
        $hasGmail = $this->containsGmailDomain($domainPart);

        if (!$hasGmail) {
            return false;
        }

        return $this->containsOtherKnownDomain($domainPart);
    }

    private function containsGmailDomain(string $domainPart): bool
    {
        foreach (self::GMAIL_DOMAINS as $gmailDomain) {
            if (strpos($domainPart, $gmailDomain) !== false) {
                return true;
            }
        }
        return false;
    }

    private function containsOtherKnownDomain(string $domainPart): bool
    {
        foreach (self::KNOWN_DOMAINS as $knownDomain) {
            if (strpos($domainPart, $knownDomain) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts Gmail email parts with typo correction.
     * Removes extra @ symbols from user part.
     * Returns true if Gmail domain was found and extracted.
     *
     * Only searches for Gmail variants in the domain part (after @) to avoid
     * false positives like "gmelano@imagine.com.ar" being detected as Gmail.
     */
    private function extractGmailParts(string $email, string $lowerEmail): bool
    {
        foreach (self::GMAIL_DOMAINS as $gmailVariant) {
            // Primero buscar con @ (ej: @gmail, @gmel)
            $pattern = '@' . $gmailVariant;
            $pos = strpos($lowerEmail, $pattern);

            if ($pos !== false) {
                $userPart = substr($email, 0, $pos);
                $this->emailUser = str_replace('@', '', $userPart);
                $this->emailDomain = '@gmail.com';
                return true;
            }

            // Si no hay @, buscar el dominio directamente (ej: gmail.com, gmel.com)
            $patternNoAt = $gmailVariant . '.com';
            $pos = strpos($lowerEmail, $patternNoAt);

            if ($pos !== false) {
                $this->emailUser = substr($email, 0, $pos);
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