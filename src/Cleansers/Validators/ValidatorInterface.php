<?php
namespace WoowUp\Cleansers\Validators;

interface ValidatorInterface
{
    /**
     * Validate input string
     *
     * @param string $input The string to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $input): bool;
}