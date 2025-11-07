<?php
namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Telephone\TelephoneFormatter;
use WoowUp\Cleansers\Validators\ConsecutiveDigitsValidator;
use WoowUp\Cleansers\Validators\GenericPhoneValidator;
use WoowUp\Cleansers\Validators\LengthValidator;
use WoowUp\Cleansers\Validators\NumericValidator;
use WoowUp\Cleansers\Validators\RepeatedDigitsValidator;
use WoowUp\Cleansers\Validators\SequentialDigitsValidator;

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
            new RepeatedDigitsValidator(),
            new ConsecutiveDigitsValidator(),
            new SequentialDigitsValidator(),
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
}
