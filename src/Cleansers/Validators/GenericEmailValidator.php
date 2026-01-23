<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that email doesn't contain generic/invalid terms.
 *
 * Rejects emails containing terms like 'test', 'example', 'noemail', etc.
 * Only matches if the term is at the start or after a separator (., -, _)
 * to avoid false positives like "deliztestta" matching "test".
 */
class GenericEmailValidator implements ValidatorInterface
{
    private const GENERIC_EMAILS = [
        'correo',
        'notiene',
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

    /**
     * Validates that email doesn't contain generic/invalid terms.
     *
     * Only rejects if the term is at the start or after a separator,
     * not if it's embedded within a word.
     *
     * @param string $email The email to validate
     * @return bool true if valid (no generic terms found), false otherwise
     */
    public function validate(string $email): bool
    {
        $emailLower = strtolower($email);

        foreach (self::GENERIC_EMAILS as $term) {
            if ($this->hasGenericTerm($emailLower, $term)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a generic term appears at a word boundary.
     *
     * A term is considered a match if it:
     * - Is at the start of the email
     * - Is at the end of the email
     * - Comes after a separator (., -, _)
     * - Comes before a separator (., -, _)
     *
     * This prevents "deliztestta" from matching "test" while still
     * catching "test123", "mi.test", "julietatest", "test_algo".
     */
    private function hasGenericTerm(string $email, string $term): bool
    {
        // Check if term is at the start
        if (strpos($email, $term) === 0) {
            return true;
        }

        // Check if term is at the end
        if (substr($email, -strlen($term)) === $term) {
            return true;
        }

        // Check if term appears after any separator
        foreach (self::SEPARATORS as $separator) {
            if (strpos($email, $separator . $term) !== false) {
                return true;
            }
        }

        // Check if term appears before any separator
        foreach (self::SEPARATORS as $separator) {
            if (strpos($email, $term . $separator) !== false) {
                return true;
            }
        }

        return false;
    }
}
