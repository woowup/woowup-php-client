<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates against known generic/placeholder phone numbers
 *
 * Generic phones are technically valid numbers but are used as placeholders
 * across multiple profiles (e.g., store phone, salesperson phone, etc.)
 *
 * These numbers are commonly repeated hundreds or thousands of times and
 * should be rejected to maintain data quality.
 *
 * This list can be updated as Analytics detects new generic numbers
 * based on usage frequency across profiles.
 */
class GenericPhoneValidator implements ValidatorInterface
{
	/**
	 * Known generic/placeholder phone numbers to reject
	 *
	 * These numbers have been identified as frequently repeated across
	 * multiple customer profiles (e.g., Bata Perú case with 300-500+ occurrences)
	 *
	 * @var array
	 */
	private const GENERIC_PHONES = [
		'963258741',
		'987456321',
		'963852741',
		'987147254',
		'985632562',
		'987456123',
		'985963654',
		'987845896',
	];

	/**
	 * Validate that the telephone is not a known generic/placeholder number
	 *
	 * @param string $telephone Cleaned telephone number (digits only)
	 * @return bool True if valid (not generic), false if generic
	 */
	public function validate(string $telephone): bool
	{
		return !in_array($telephone, self::GENERIC_PHONES, true);
	}
}