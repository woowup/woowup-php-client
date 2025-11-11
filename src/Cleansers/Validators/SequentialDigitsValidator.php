<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input doesn't contain long sequential digit patterns
 *
 * Rejects unrealistic patterns like:
 * - Ascending: 1234567890, 12345678, 0123456789
 * - Descending: 9876543210, 87654321, 9876543210
 *
 * Helps identify fake or test data
 */
class SequentialDigitsValidator implements ValidatorInterface
{
	const MIN_SEQUENCE_LENGTH = 8;

	/**
	 * Validate that input doesn't contain long sequential patterns
	 *
	 * @param string $input The input to validate
	 * @return bool True if valid, false if contains long sequences
	 */
	public function validate(string $input): bool
	{
		if ($this->hasAscendingSequence($input)) {
			return false;
		}

		if ($this->hasDescendingSequence($input)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if input contains ascending sequential digits
	 *
	 * @param string $input Input to check
	 * @return bool True if contains ascending sequence
	 */
	private function hasAscendingSequence(string $input): bool
	{
		$sequenceCount = 1;

		for ($i = 1; $i < strlen($input); $i++) {
			$current = (int)$input[$i];
			$previous = (int)$input[$i - 1];

			if ($current === ($previous + 1) % 10) {
				$sequenceCount++;
				if ($sequenceCount >= self::MIN_SEQUENCE_LENGTH) {
					return true;
				}
			} else {
				$sequenceCount = 1;
			}
		}

		return false;
	}

	/**
	 * Check if input contains descending sequential digits
	 *
	 * @param string $input Input to check
	 * @return bool True if contains descending sequence
	 */
	private function hasDescendingSequence(string $input): bool
	{
		$sequenceCount = 1;

		for ($i = 1; $i < strlen($input); $i++) {
			$current = (int)$input[$i];
			$previous = (int)$input[$i - 1];

			if ($current === ($previous - 1 + 10) % 10) {
				$sequenceCount++;
				if ($sequenceCount >= self::MIN_SEQUENCE_LENGTH) {
					return true;
				}
			} else {
				$sequenceCount = 1;
			}
		}

		return false;
	}
}