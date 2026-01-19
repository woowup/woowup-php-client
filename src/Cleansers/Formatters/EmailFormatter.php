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

    public function clean(string $email): string
    {
        if ($this->hasInvalidSpanishChars($email)) {
            // Devolvemos vacío para que el caller trate el email como inválido.
            return '';
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

    private function hasInvalidSpanishChars(string $input): bool
    {
        return (strpos($input, 'ñ') !== false);
    }
}
