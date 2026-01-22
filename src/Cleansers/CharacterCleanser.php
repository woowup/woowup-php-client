<?php

namespace WoowUp\Cleansers;

/**
 * Generic character cleaning utilities
 *
 * Provides reusable methods for cleaning and normalizing characters
 * from strings. No business logic - pure character manipulation.
 *
 * Can be used by: TelephoneCleanser, EmailCleanser, StreetCleanser, etc.
 */
class CharacterCleanser
{
    /**
     * Remove all non-digit characters, keeping only 0-9
     *
     * @param string $input Input string
     * @return string String with digits only
     */
    public function removeNonDigits(string $input): string
    {
        return preg_replace('/[^0-9]/', '', $input);
    }

    /**
     * Keep only digits and the + symbol (useful for international phone numbers)
     *
     * @param string $input Input string
     * @return string String with digits and + only
     */
    public function keepDigitsAndPlus(string $input): string
    {
        return preg_replace('/[^0-9+]/', '', $input);
    }

    /**
     * Remove specific characters from input
     *
     * @param string $input Input string
     * @param array $chars Array of characters to remove
     * @return string String without specified characters
     */
    public function removeChars(string $input, array $chars): string
    {
        return str_replace($chars, '', $input);
    }

    /**
     * Remove common formatting characters (spaces, dashes, parentheses, dots)
     *
     * @param string $input Input string
     * @return string String without formatting characters
     */
    public function removeFormatting(string $input): string
    {
        return str_replace([' ', '-', '(', ')', '.'], '', $input);
    }

    /**
     * Keep only alphanumeric characters
     *
     * @param string $input Input string
     * @return string String with alphanumeric characters only
     */
    public function keepAlphanumeric(string $input): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $input);
    }

    /**
     * Normalize whitespace (convert multiple spaces to single space and trim)
     *
     * @param string $input Input string
     * @return string String with normalized whitespace
     */
    public function normalizeWhitespace(string $input): string
    {
        return trim(preg_replace('/\s+/', ' ', $input));
    }

    /**
     * Removes accents and diacritical marks from text.
     * 
     * Uses Unicode normalization to decompose characters and removes combining marks.
     * 
     * @param string $text The text to remove accents from
     * @return string Text without accents
     */
    public function removeAccents(string $text): string
    {
        $normalized = \Normalizer::normalize($text, \Normalizer::FORM_D);
        return preg_replace('/\p{Mn}/u', '', $normalized);
    }
}