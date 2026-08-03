<?php

namespace WoowUp\Cleansers\Tld;

class TldCorrector
{
    // Providers where only one canonical TLD is valid regardless of what IANA says.
    const DEFAULT_SINGLE_DOMAIN_PROVIDERS = [
        'gmail'  => 'com',
        'icloud' => 'com',
    ];

    // Providers that operate in multiple regions; IANA is the default but a short denylist applies.
    const DEFAULT_MULTI_REGION_PROVIDERS = [
        'hotmail', 'outlook', 'yahoo', 'live', 'msn', 'aol', 'proton', 'protonmail', 'zoho',
    ];

    // Valid IANA TLDs that known providers never use standalone (e.g. yahoo.co is not real).
    const DEFAULT_DENYLIST = ['co'];

    // Ordered list of target TLDs for edit-distance and prefix corrections.
    const DEFAULT_TARGET_TLDS = [
        'com', 'ar', 'es', 'net', 'org', 'br', 'io', 'co', 'lat', 'mx', 'cl', 'pe', 'uy', 'edu', 'gov',
    ];

    // When the label right before the broken TLD is already a generic second-level
    // label (gov.XX, com.XX, mil.XX, edu.XX), XX is expected to be a country code,
    // not another generic TLD — otherwise "gov.go" resolves to "gov.io" instead of
    // "gov.co", and "com.con" resolves to "com.com" instead of "com.co".
    const TWO_LEVEL_CONTEXT_LABELS = ['com', 'net', 'org', 'edu', 'gov', 'mil'];
    const DEFAULT_COUNTRY_CODE_PARTNERS = ['co', 'ar', 'es', 'br', 'mx', 'cl', 'pe', 'uy'];

    /** @var IanaTldProvider */
    private $iana;
    /** @var array */
    private $singleDomainProviders;
    /** @var array */
    private $multiRegionProviders;
    /** @var array */
    private $denylist;
    /** @var array */
    private $targetTlds;
    /** @var array */
    private $countryCodePartners;
    /** @var string|null */
    private $newSuffixLog;

    public function __construct(IanaTldProvider $iana, array $config = [])
    {
        $this->iana                  = $iana;
        $this->singleDomainProviders = $config['single_domain_providers'] ?? self::DEFAULT_SINGLE_DOMAIN_PROVIDERS;
        $this->multiRegionProviders  = $config['multi_region_providers']  ?? self::DEFAULT_MULTI_REGION_PROVIDERS;
        $this->denylist              = $config['denylist']                ?? self::DEFAULT_DENYLIST;
        $this->targetTlds            = $config['target_tlds']             ?? self::DEFAULT_TARGET_TLDS;
        $this->countryCodePartners   = $config['country_code_partners']   ?? self::DEFAULT_COUNTRY_CODE_PARTNERS;
        $this->newSuffixLog          = $config['new_suffix_log']          ?? null;
    }

    /**
     * Receives the domain portion as stored in EmailCleanser (e.g. "@hotmail.comm").
     * Returns a TldCorrectionResult with the corrected "@domain.tld" or irrecoverable flag.
     */
    public function correct(string $emailDomain): TldCorrectionResult
    {
        // Strip leading "@" for processing, restore it in the result.
        $domain = ltrim($emailDomain, '@');
        list($name, $tld) = $this->splitAtLastDot($domain);

        if ($tld === '') {
            return TldCorrectionResult::irrecoverable();
        }

        $providerType = $this->detectProviderType($name);

        if ($providerType === 'single') {
            return $this->correctSingleDomain($name, $tld);
        }

        if ($providerType === 'multi_region') {
            return $this->correctMultiRegionDomain($name, $tld);
        }

        return $this->correctCustomDomain($name, $tld);
    }

    // -------------------------------------------------------------------------
    // Provider type detection
    // -------------------------------------------------------------------------

    private function detectProviderType(string $name): string
    {
        // Name may be "yahoo.com" for "yahoo.com.arr" — extract the first label.
        $firstLabel = explode('.', $name)[0];

        if (isset($this->singleDomainProviders[$firstLabel])) {
            return 'single';
        }

        if (in_array($firstLabel, $this->multiRegionProviders, true)) {
            return 'multi_region';
        }

        return 'custom';
    }

    // -------------------------------------------------------------------------
    // Correction strategies
    // -------------------------------------------------------------------------

    private function correctSingleDomain(string $name, string $tld): TldCorrectionResult
    {
        $firstLabel    = explode('.', $name)[0];
        $canonicalTld  = $this->singleDomainProviders[$firstLabel];
        $canonicalFull = $firstLabel . '.' . $canonicalTld;

        if ($tld === $canonicalTld && $name === $firstLabel) {
            return TldCorrectionResult::unchanged('@' . $name . '.' . $tld);
        }

        return TldCorrectionResult::corrected('@' . $canonicalFull);
    }

    private function correctMultiRegionDomain(string $name, string $tld): TldCorrectionResult
    {
        if ($this->iana->isValid($tld)) {
            // Denylist only applies to a bare suffix (yahoo.co), never to a legitimate
            // two-level regional suffix (yahoo.com.co) — otherwise "yahoo.com.co" would
            // be corrected to "yahoo.com.com".
            $firstLabel = explode('.', $name)[0];
            $isBare = $name === $firstLabel;
            if ($isBare && in_array($tld, $this->denylist, true)) {
                return TldCorrectionResult::corrected('@' . $name . '.com');
            }
            $this->logNewSuffix($name, $tld);
            return TldCorrectionResult::unchanged('@' . $name . '.' . $tld);
        }

        $corrected = $this->correctInvalidTld($name, $tld);
        if ($corrected === null) {
            return TldCorrectionResult::irrecoverable();
        }

        return TldCorrectionResult::corrected('@' . $name . '.' . $corrected);
    }

    private function correctCustomDomain(string $name, string $tld): TldCorrectionResult
    {
        if ($this->iana->isValid($tld)) {
            return TldCorrectionResult::unchanged('@' . $name . '.' . $tld);
        }

        $corrected = $this->correctInvalidTld($name, $tld);
        if ($corrected === null) {
            return TldCorrectionResult::irrecoverable();
        }

        return TldCorrectionResult::corrected('@' . $name . '.' . $corrected);
    }

    // -------------------------------------------------------------------------
    // Core correction: edit distance → prefix (order is critical)
    // -------------------------------------------------------------------------

    /**
     * Returns the corrected TLD string (may be compound like "com.ar") or null if irrecoverable.
     * The order edit-distance → prefix is critical: without it, "con" would match prefix "co"
     * instead of being corrected to "com" via edit distance 1.
     */
    /** @return string|null */
    private function correctInvalidTld(string $name, string $tld)
    {
        // Pre-process IDN punycode: xn--com-9ma → extract leading alpha root → "com".
        // This handles cases where the TLD is a punycode-encoded form of a common TLD.
        if (strpos($tld, 'xn--') === 0) {
            $stripped = substr($tld, 4);
            preg_match('/^[a-z]+/', $stripped, $m);
            if (!empty($m[0]) && $this->iana->isValid($m[0])) {
                return $m[0];
            }
            $tld = $stripped;
        }

        // Two-level context: "...com.XX" / "...gov.XX" / "...mil.XX" etc already
        // has a generic label before the broken one, so XX is expected to be a
        // country code — resolve against country candidates first.
        $lastNameLabel = strpos($name, '.') !== false ? substr($name, strrpos($name, '.') + 1) : null;
        if ($lastNameLabel !== null && in_array($lastNameLabel, self::TWO_LEVEL_CONTEXT_LABELS, true)) {
            $countryMatch = $this->findByEditDistance($tld, 1, $this->countryCodePartners);
            if ($countryMatch !== null) {
                return $countryMatch;
            }
        }

        // Step 1: edit distance = 1 against target TLDs (ordered by frequency).
        $editMatch = $this->findByEditDistance($tld, 1);
        if ($editMatch !== null) {
            return $editMatch;
        }

        // Step 2: prefix — find the longest target TLD that is a prefix of the invalid label.
        return $this->findByPrefix($name, $tld);
    }

    /** @return string|null */
    private function findByEditDistance(string $tld, int $maxDistance, array $candidates = null)
    {
        foreach ($candidates ?? $this->targetTlds as $target) {
            if (levenshtein($tld, $target) <= $maxDistance) {
                return $target;
            }
        }
        return null;
    }

    /** @return string|null */
    private function findByPrefix(string $name, string $tld)
    {
        // Try longest matching prefix first to prefer "com.ar" over "com" for "comar".
        $candidates = $this->targetTlds;
        usort($candidates, function ($a, $b) { return strlen($b) - strlen($a); });

        foreach ($candidates as $target) {
            if (strpos($tld, $target) === 0) {
                $tail = substr($tld, strlen($target));

                if ($tail === '') {
                    return $target;
                }

                // If tail is a valid 2-letter ccTLD and doesn't duplicate the last label of $name,
                // insert it as a second-level label.
                if (strlen($tail) === 2 && $this->iana->isValid($tail)) {
                    $nameParts = explode('.', $name);
                    $lastNameLabel = end($nameParts);
                    if ($lastNameLabel !== $tail) {
                        return $target . '.' . $tail;
                    }
                }

                // Tail is noise — return only the prefix.
                return $target;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Splits "hotmail.comm" into ["hotmail", "comm"].
     * Splits "yahoo.com.arr" into ["yahoo.com", "arr"].
     */
    private function splitAtLastDot(string $domain): array
    {
        $pos = strrpos($domain, '.');
        if ($pos === false) {
            return [$domain, ''];
        }
        return [substr($domain, 0, $pos), substr($domain, $pos + 1)];
    }

    /** @return void */
    private function logNewSuffix(string $providerName, string $tld)
    {
        if ($this->newSuffixLog === null) {
            return;
        }

        $firstLabel = explode('.', $providerName)[0];
        $line = date('Y-m-d H:i:s') . "\t" . $firstLabel . "\t" . $tld . "\n";
        @file_put_contents($this->newSuffixLog, $line, FILE_APPEND | LOCK_EX);
    }
}
