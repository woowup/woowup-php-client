<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that email doesn't contain generic/invalid terms.
 *
 * Rejects emails containing terms like 'test', 'example', 'noemail', etc.
 * Uses smart detection to avoid false positives:
 * - Accepts embedded terms in long usernames (>=10 chars)
 * - Accepts words that contain the term but are different words (e.g., "testamento")
 * - Rejects terms with separators, simple numeric suffixes, or short usernames
 */
class GenericEmailValidator implements ValidatorInterface
{
    private const GENERIC_EMAILS = [
        'sincorreo',
        'nocorreo',
        'correo',
        'notiene',
        'notienemail',
        'nocontacto',
        'unavailable',
        'noautoriz',
        'example',
        'ejemplo',
        'facturacion',
        'factura',
        'noemail',
        'prueba',
        'nomail',
        'notmail',
        'ninguno',
        'nosustent',
        'noregistra',
        'nomaneja',
        'nopresenta',
        'test',
        'user',
        'nombre',
        'apellido',
        'usuario',
        'email',
        'nocuenta',
        'sinmail',
        'noposee',
        'notengo',
        'noreply',
    ];

    private const SEPARATORS = ['.', '-', '_'];
    private const MIN_USERNAME_LENGTH = 10;
    private const MAX_SIMPLE_DIGITS = 4;

    /**
     * Validates that email doesn't contain generic/invalid terms.
     *
     * @param string $email The email username to validate
     * @return bool true if valid (no generic terms found), false otherwise
     */
    public function validate(string $email): bool
    {
        $emailLower = strtolower($email);

        foreach (self::GENERIC_EMAILS as $term) {
            if ($this->isGenericEmail($emailLower, $term)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if the email username is generic based on a specific term.
     *
     * REJECTS if:
     * - Term equals the entire username
     * - Term is separated by delimiters (., -, _)
     * - Term at start + simple numbers (1-4 digits)
     * - Short username (<10 chars) with term at start/end + only numbers
     *
     * ACCEPTS if:
     * - Username >=10 chars with term embedded (no separators)
     * - Term is part of a different word (has letters after it)
     */
    private function isGenericEmail(string $email, string $term): bool
    {
        // Term not in email at all
        if (strpos($email, $term) === false) {
            return false;
        }

        // 1. Term = entire username -> REJECT
        if ($email === $term) {
            return true;
        }

        // 2. Term with separators -> REJECT
        if ($this->hasTermWithSeparator($email, $term)) {
            return true;
        }

        // 3. Term at start + simple numbers (1-4 digits) -> REJECT
        if ($this->isTermWithSimpleNumbers($email, $term)) {
            return true;
        }

        // 4. Short username (<10 chars) with term at start/end + only numbers -> REJECT
        if ($this->isShortWithTermAndNumbers($email, $term)) {
            return true;
        }

        // If we get here: term is embedded with letters or username >=10 chars -> ACCEPT
        return false;
    }

    /**
     * Check if term appears isolated with separators.
     * Only rejects if term is followed by separator, digit, or end of string.
     * Allows: julieta.teston (teston != test)
     * Rejects: julieta.test, julieta.test123, test.maria
     */
    private function hasTermWithSeparator(string $email, string $term): bool
    {
        $termLen = strlen($term);
        $emailLen = strlen($email);

        foreach (self::SEPARATORS as $sep) {
            // Check sep + term (term after separator)
            $patternAfter = $sep . $term;
            $pos = strpos($email, $patternAfter);
            if ($pos !== false) {
                $afterTermPos = $pos + strlen($sep) + $termLen;
                // Term is at end, or followed by separator or digit -> REJECT
                if ($afterTermPos >= $emailLen) {
                    return true;
                }
                $nextChar = $email[$afterTermPos];
                if (in_array($nextChar, self::SEPARATORS) || ctype_digit($nextChar)) {
                    return true;
                }
                // If followed by letter, it's a different word -> continue checking
            }

            // Check term + sep (term before separator)
            $patternBefore = $term . $sep;
            $pos = strpos($email, $patternBefore);
            if ($pos !== false) {
                // Term at start or after separator -> REJECT
                if ($pos === 0) {
                    return true;
                }
                $prevChar = $email[$pos - 1];
                if (in_array($prevChar, self::SEPARATORS) || ctype_digit($prevChar)) {
                    return true;
                }
                // If preceded by letter, it's a different word -> continue
            }
        }

        return false;
    }

    /**
     * Detects: test1, test12, test123, test1234 (term at start + 1-4 digits only)
     */
    private function isTermWithSimpleNumbers(string $email, string $term): bool
    {
        if (strpos($email, $term) !== 0) {
            return false;
        }

        $suffix = substr($email, strlen($term));
        if ($suffix === '') {
            return false;
        }

        return preg_match('/^\d{1,' . self::MAX_SIMPLE_DIGITS . '}$/', $suffix) === 1;
    }

    /**
     * For short usernames (<10 chars): reject if term at start/end with only digits.
     * This allows "testamento" (9 chars) because it has LETTERS after the term.
     */
    private function isShortWithTermAndNumbers(string $email, string $term): bool
    {
        if (strlen($email) >= self::MIN_USERNAME_LENGTH) {
            return false;
        }

        // Term at start + only digits after -> REJECT
        if (strpos($email, $term) === 0) {
            $suffix = substr($email, strlen($term));
            if ($suffix !== '' && preg_match('/^\d+$/', $suffix)) {
                return true;
            }
        }

        // Term at end + only digits before -> REJECT
        $termLen = strlen($term);
        if (substr($email, -$termLen) === $term) {
            $prefix = substr($email, 0, -$termLen);
            if ($prefix !== '' && preg_match('/^\d+$/', $prefix)) {
                return true;
            }
        }

        return false;
    }
}
