<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input contains only numeric characters
 */
class NumericValidator implements ValidatorInterface
{
    /**
     * Validate that input is numeric (accepts optional + prefix)
     *
     * @param string $input The input to validate
     * @return bool True if numeric (or numeric with + prefix), false otherwise
     */
    public function validate(string $input): bool
    {
        if ($input[0] === '+') {
            return ctype_digit(substr($input, 1));
        }
        return ctype_digit($input);
    }
}