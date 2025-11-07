<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Generic length validator
 *
 * Validates that a string's length is within specified min/max bounds.
 */
class LengthValidator implements ValidatorInterface
{
	/**
	 * @var int Minimum allowed length
	 */
	private $minLength;

	/**
	 * @var int Maximum allowed length
	 */
	private $maxLength;

	/**
	 * Constructor
	 *
	 * @param int $minLength Minimum allowed length
	 * @param int $maxLength Maximum allowed length
	 */
	public function __construct(int $minLength, int $maxLength)
	{
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}

	/**
	 * Validate input length
	 *
	 * @param string $input The string to validate
	 * @return bool True if length is valid, false otherwise
	 */
	public function validate(string $input): bool
	{
		$length = strlen($input);

		if ($length < $this->minLength || $length > $this->maxLength) {
			return false;
		}

		return true;
	}
}