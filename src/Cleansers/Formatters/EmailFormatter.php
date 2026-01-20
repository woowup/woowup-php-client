<?php

namespace WoowUp\Cleansers\Formatters;

use WoowUp\Cleansers\CharacterCleanser;

class
EmailFormatter
{

    /**
     * @var CharacterCleanser
     */
    private $characterCleanser;
    public function __construct()
    {
        $this->characterCleanser = new CharacterCleanser();
    }

    /**
     * Cleans and normalizes the user part of an email.
     * 
     * Removes accents, trims symbols, normalizes consecutive symbols,
     * and filters to email-safe characters only.
     * 
     * @param string $email The user part to clean
     * @return string The cleaned email or 'noemail@noemail.com' if invalid characters found
     */
    public function clean(string $email): string
    {
        if ($this->hasInvalidSpanishChars($email)) {
            return 'noemail@noemail.com';
        }

        $email = $this->characterCleanser->removeAccents($email);
        $email = $this->trimSymbols($email);
        $email = $this->trimSymbolsAroundAt($email);
        $email = $this->normalizeConsecutiveSymbols($email);
        return $this->filterEmailCharacters($email);
    }

    /**
     * Keep only email-safe characters: letters, digits, dots, hyphens, underscores, plus
     *
     * @param string $input Input string
     * @return string String with only allowed characters
     *
     */
    public function filterEmailCharacters(string $input): string
    {
        return preg_replace('/[^a-zA-Z0-9.\-_+]/', '', $input);
    }

    /**
     * Remove symbols from the start and end of a string
     *
     * @param string $input Input string
     * @param string $symbols Symbols to remove (default: .-_+)
     * @return string String without leading/trailing symbols
     */
    public function trimSymbols(string $input, string $symbols = '.-_+'): string
    {
        return trim($input, $symbols);
    }

    /**
     * Replace consecutive symbols with a single instance of each
     *
     * @param string $input Input string
     * @param string $symbols Symbols to normalize (default: .-_+)
     * @return string String with consecutive symbols replaced by single instance
     */
    public function normalizeConsecutiveSymbols(string $input, string $symbols = '.\-_+'): string
    {
        $pattern = '/([' . $symbols . '])\1+/';
        return preg_replace($pattern, '$1', $input);
    }

    /**
     * Remove symbols adjacent to @ (before and after)
     *
     * @param string $email Email string
     * @param string $symbols Symbols to remove (default: .-_+)
     * @return string Email with symbols removed around @
     */
    public function trimSymbolsAroundAt(string $email, string $symbols = '.-_+'): string
    {
        $pattern = '/[' . preg_quote($symbols, '/') . ']+@|@[' . preg_quote($symbols, '/') . ']+/';
        return preg_replace($pattern, '@', $email);
    }

    /**
     * Checks if the string contains invalid characters for email addresses.
     * 
     * Currently only detects 'ñ', which is invalid in email addresses.
     * 
     * @param string $input The string to check
     * @return bool true if invalid characters found, false otherwise
     */
    private function hasInvalidSpanishChars(string $input): bool
    {
        return (strpos($input, 'ñ') !== false);
    }
}
