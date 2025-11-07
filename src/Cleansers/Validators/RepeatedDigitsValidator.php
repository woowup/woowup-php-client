<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input doesn't have unrealistic digit repetitions
 *
 * Rejects patterns like:
 * - All same digit: 5555555555, 0000000000
 * - 5+ same digits anywhere: 5555532423, 12345555556
 *
 * Helps identify fake or test data
 */
class RepeatedDigitsValidator implements ValidatorInterface
{
	const MAX_REPETITIONS = 5;

	/**
	 * Validate that input doesn't have excessive repeated digits
	 *
	 * @param string $input The input to validate
	 * @return bool True if valid, false if too many repeated digits
	 */
	public function validate(string $input): bool
	{
		if (strlen($input) === 0) {
			return true;
		}

		$pattern = '/(\d)\1{' . (self::MAX_REPETITIONS - 1) . ',}/';

		if (preg_match($pattern, $input)) {
			return false;
		}

		return true;
	}
}