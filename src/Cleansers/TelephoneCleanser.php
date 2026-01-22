<?php
namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Formatters\TelephoneFormatter;
use WoowUp\Cleansers\Validators\GenericPhoneValidator;
use WoowUp\Cleansers\Validators\LengthValidator;
use WoowUp\Cleansers\Validators\NumericValidator;
use WoowUp\Cleansers\Validators\RepeatedValidator;
use WoowUp\Cleansers\Validators\SequenceValidator;

/**
 * Telephone number sanitizer and validator
 *
 * Process flow:
 * 1. Type validation and normalization
 * 2. Format and clean using TelephoneFormatter
 * 3. Validate using all validators
 * 4. Return sanitized number or false if invalid
 */
class TelephoneCleanser
{
    private $formatter;
    private $validators;

    /**
     * Initialize formatter and validators
     */
    public function __construct()
    {
        $this->formatter = new TelephoneFormatter();
        $this->validators = [
            new NumericValidator(),
            new LengthValidator(8, 15),
            new RepeatedValidator(5, true),
            new SequenceValidator(8, true),
            new GenericPhoneValidator(),
        ];

        return $this;
    }

    /**
     * Sanitize telephone number
     *
     * Steps:
     * 1. Type validation and normalization
     * 2. Format and clean telephone
     * 3. Validate through all validators
     *
     * @param mixed $telephone Raw telephone input
     * @return string|false Returns sanitized telephone (digits only) or false on failure
     */
    public function sanitize($telephone)
    {
        if (!is_string($telephone) && !is_numeric($telephone)) {
            return false;
        }

        $telephone = (string) $telephone;
        $telephone = trim($telephone);

        if ($telephone === '') {
            return false;
        }

        if ($this->hasInvalidArithmeticOperators($telephone)) {
            return false;
        }

        $cleanedTelephone = $this->formatter->clean($telephone);

        if ($cleanedTelephone === '') {
            return false;
        }

        foreach ($this->validators as $validator) {
            if (!$validator->validate($cleanedTelephone)) {
                return false;
            }
        }

        return $cleanedTelephone;
    }

    /**
     * Check if telephone is valid
     *
     * @param mixed $telephone
     * @return bool
     */
    public function isValid($telephone): bool
    {
        return $this->sanitize($telephone) !== false;
    }

    /**
     * Check if telephone contains patterns that the API rejects
     *
     * These patterns cause the API to reject the entire request, so we need
     * to detect them early and skip processing entirely (no tags, no validation).
     *
     * @param mixed $telephone Telephone to check
     * @return bool True if contains API-rejected patterns, false otherwise
     */
    public function hasApiRejectedPatterns($telephone): bool
    {
        if (!is_string($telephone) && !is_numeric($telephone)) {
            return false;
        }

        $telephone = (string) $telephone;
        $telephone = trim($telephone);

        if ($telephone === '') {
            return false;
        }

        return $this->hasInvalidArithmeticOperators($telephone);
    }

    /**
     * Check if telephone contains invalid arithmetic operators
     *
     * @param string $telephone Telephone to check
     * @return bool True if contains invalid operators, false otherwise
     */
    private function hasInvalidArithmeticOperators(string $telephone): bool
    {
        if (preg_match('/\d\+\d/', $telephone)) {
            return true;
        }

        return false;
    }
}
