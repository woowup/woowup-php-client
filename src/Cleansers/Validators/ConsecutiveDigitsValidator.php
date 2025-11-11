<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input doesn't contain too many consecutive identical digits
 *
 * Rejects patterns like: 1111111111, 0000000000
 * Helps identify fake or test data
 */
class ConsecutiveDigitsValidator implements ValidatorInterface
{
	const MAX_CONSECUTIVE = 5;

	/**
	 * Validate that input doesn't have excessive consecutive digits
	 *
	 * @param string $input The input to validate
	 * @return bool True if valid, false if too many consecutive digits
	 */
	public function validate(string $input): bool
	{
		$pattern = '/(\d)\1{' . (self::MAX_CONSECUTIVE) . ',}/';

		return !preg_match($pattern, $input);
	}
}