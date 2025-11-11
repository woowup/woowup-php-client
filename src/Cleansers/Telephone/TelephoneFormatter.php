<?php

namespace WoowUp\Cleansers\Telephone;

use WoowUp\Cleansers\CharacterCleanser;

/**
 * Telephone-specific formatting and normalization
 *
 * Handles business logic specific to telephone numbers:
 * - Extract first number from multiple numbers
 * - Argentina-specific formatting (remove redundant "15" prefix)
 * - Orchestrate cleaning process using CharacterCleanser
 */
class TelephoneFormatter
{
    /**
     * @var CharacterCleanser
     */
    private $characterCleanser;

    const COUNTRY_CODE = [
        '54', '56', '57', '52', '51', '598', '595', '593', '58', '55', '591'
    ];

    public function __construct()
    {
        $this->characterCleanser = new CharacterCleanser();
    }

    /**
     * Cleans and normalizes a telephone number.
     *
     * Process:
     * 1. Extract the first valid number (handles separators, double numbers, etc.)
     * 2. Remove extensions (x101, ext 34, etc.)
     * 3. Convert "00" prefix to "+" (international dialing format)
     * 4. Remove duplicate country codes (e.g., +5656 → +56)
     * 5. Remove Argentina-specific "15" prefix after area codes
     * 6. Remove country-specific mobile prefixes (Argentina 9, Mexico 1)
     * 7. Keep only digits (and "+" if applicable)
     *
     * @param string $telephone Raw telephone input
     * @return string Normalized telephone (digits only)
     */
    public function clean(string $telephone): string
    {
        if ($telephone === '') {
            return $telephone;
        }

        $telephone = $this->extractFirstValidNumber($telephone);
        $telephone = $this->removeExtension($telephone);
        $telephone = $this->removeDoubleZero($telephone);
        $telephone = $this->removeDuplicateCountryCode($telephone);
        $telephone = $this->removeArgentina15Prefix($telephone);

        return $this->normalizeWithInternationalPrefix($telephone);
    }

    /**
     * Extracts the first valid telephone number from a given string.
     *
     * This method handles multiple numbers in a single string, separated
     * by common delimiters such as '/', ',', ';', '|', ' y ', or multiple spaces.
     * If two sequences of digits (8-15 digits each) appear without a clear separator,
     * only the first sequence is returned.
     *
     * @param string $telephone The input string potentially containing one or more phone numbers.
     * @return string The first valid telephone number found in the input.
     */
    private function extractFirstValidNumber(string $telephone): string
    {
        $telephone = trim($telephone);
        $parts = preg_split('/[\/,;|]|(\sy\s)|\s{2,}/', $telephone);
        $first = trim($parts[0] ?? $telephone);

        if (preg_match('/^(\d{8,15})\s+(\d{8,15})$/', $first, $m)) {
            return $m[1];
        }
        return $first;
    }

    /**
     * Removes duplicated country codes from international phone numbers.
     *
     * Examples:
     * +5454... → +54...
     * 5454... → 54...
     * +5656... → +56...
     * 5656... → 56...
     *
     * @param string $telephone Telephone with possible duplicate country code
     * @return string Telephone with duplicate removed
     */
    private function removeDuplicateCountryCode(string $telephone): string
    {
        if (empty($telephone)) return $telephone;

        $hasPlus = $telephone[0] === '+';
        $cleanNumber = $hasPlus ? substr($telephone, 1) : $telephone;

        foreach (self::COUNTRY_CODE as $code) {
            if (strpos($cleanNumber, $code . $code) === 0) {
                $cleanNumber = substr($cleanNumber, strlen($code));
                return $hasPlus ? '+' . $cleanNumber : $cleanNumber;
            }
        }

        return $telephone;
    }


    /**
     * Remove Argentina-specific "15" prefix and replace with "11"
     *
     * @param string $telephone Original telephone number
     * @return string Telephone with "15" replaced by "11" or removed
     */
    public function removeArgentina15Prefix(string $telephone): string
    {
        return preg_replace('/(\d{2,4})[\s\-\(\)]*15[\s\-\(\)]+/', '$1', $telephone);
    }

    /**
     * Remove telephone extensions from the number
     *
     * Extensions are commonly written as:
     * - "x101", "x 101", "X101"
     * - "ext 34", "ext. 34", "extension 34"
     * - "#101", "# 101"
     *
     * Examples:
     * - "5712345678x101" → "5712345678"
     * - "5712345678 ext 34" → "5712345678"
     * - "5712345678 extension 101" → "5712345678"
     * - "5712345678#101" → "5712345678"
     * - "5712345678 x 101" → "5712345678"
     *
     * @param string $telephone Telephone with possible extension
     * @return string Telephone without extension
     */
    private function removeExtension(string $telephone): string
    {
        return preg_replace('/[\s\-]*(x|X|ext\.?|extension|#)[\s\-\.]*\d+$/i', '', $telephone);
    }


    /**
     * Removes leading "00" from international numbers if valid.
     *
     * Converts numbers starting with "00" to omit the prefix when
     * there are at least 10 digits, standardizing the format.
     *
     * Examples:
     * - "005491112345678" → "5491112345678"
     * - "00 54 911 1234 5678" → "54 911 1234 5678"
     *
     * @param string $telephone Input number
     * @return string Number without "00" prefix when applicable
     */
    private function removeDoubleZero(string $telephone): string
    {
        $telephone = trim($telephone);

        if (strpos($telephone, '00') !== 0) {
            return $telephone;
        }

        $digitsOnly = preg_replace('/[^0-9]/', '', $telephone);

        if (strlen($digitsOnly) >= 10) {
            return substr($telephone, 2);
        }

        return $telephone;
    }

    /**
     * Normalize telephone keeping international prefix (+)
     *
     * Useful when you need to preserve country codes.
     * Adds "+" prefix if the number starts with a valid country code.
     *
     * @param string $telephone Telephone to normalize
     * @return string Normalized telephone (digits and optional +)
     */
    public function normalizeWithInternationalPrefix(string $telephone): string
    {
        $telephone = trim($telephone);

        if (strpos($telephone, '+') === 0) return $telephone;

        $telephone = $this->characterCleanser->removeFormatting($telephone);
        $telephone = $this->characterCleanser->keepDigitsAndPlus($telephone);

        foreach (self::COUNTRY_CODE as $countryCode) {
            if (strpos($telephone, $countryCode) === 0) {
                return '+' . $telephone;
            }
        }

        return $telephone;
    }

    /**
     * Remove country-specific mobile prefixes after country code
     *
     * Some countries add a mobile prefix after the country code that should be removed
     * for proper normalization:
     * - Argentina (+54): Remove the "9" after country code (e.g., +54 9 11... → +54 11...)
     * - Mexico (+52): Remove the "1" after country code (e.g., +52 1 55... → +52 55...)
     *
     * These prefixes are used for mobile numbers when dialing internationally but
     * should be removed when storing normalized numbers.
     *
     * Examples:
     * - "+54 9 11 1234-5678" → "+54 11 1234-5678" (Argentina)
     * - "+549111234567" → "+5411123456" (Argentina without spaces)
     * - "54 9 11 12345678" → "54 11 12345678" (Argentina without +)
     * - "+52 1 55 1234 5678" → "+52 55 1234 5678" (Mexico)
     * - "+521551234567" → "+52551234567" (Mexico without spaces)
     * - "52 1 55 12345678" → "52 55 12345678" (Mexico without +)
     * - "+56 9 1234 5678" → "+56 9 1234 5678" (Chile, no change - 9 is part of number)
     *
     * @param string $telephone Telephone with possible mobile prefix
     * @return string Telephone without mobile prefix
     */
    private function removeCountryMobilePrefixes(string $telephone): string
    {
        // Argentina: remove "9" after country code 54
        // +54 9, +549, 54 9, 549
        $telephone = preg_replace('/^(\+?54)[\s\-\(\)]*9[\s\-\(\)]*/', '$1', $telephone);

        // Mexico: remove "1" after country code 52
        // +52 1, +521, 52 1, 521
        $telephone = preg_replace('/^(\+?52)[\s\-\(\)]*1[\s\-\(\)]*/', '$1', $telephone);

        return $telephone;
    }
}

