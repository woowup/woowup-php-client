<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input contains only numeric characters
 */
class NumericValidator implements ValidatorInterface
{
	/**
	 * Validate that input is numeric
	 *
	 * @param string $input The input to validate
	 * @return bool True if numeric, false otherwise
	 */
	public function validate(string $input): bool
	{
		return ctype_digit($input);
	}
}