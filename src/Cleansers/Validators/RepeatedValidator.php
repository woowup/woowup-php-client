<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that input doesn't have unrealistic character repetitions
 *
 * Can validate either digits only or any character.
 * Helps identify fake or test data.
 */
class RepeatedValidator implements ValidatorInterface
{
    private int $maxRepetitions;
    private bool $digitsOnly;

    /**
     * Constructor
     *
     * @param int $maxRepetitions Maximum consecutive repetitions allowed
     * @param bool $digitsOnly If true, only validates digits. If false, validates any character
     */
    public function __construct(int $maxRepetitions = 5, bool $digitsOnly = true) {
        $this->maxRepetitions = $maxRepetitions;
        $this->digitsOnly = $digitsOnly;
    }

    /**
     * Validate that input doesn't have excessive repeated characters
     *
     * @param string $input The input to validate
     * @return bool True if valid, false if too many repeated characters
     */
	public function validate(string $input): bool
	{
		if (strlen($input) === 0) {
			return true;
		}

        $charClass = $this->digitsOnly ? '\d' : '.';
		$pattern = '/('. $charClass .')\1{' . ($this->maxRepetitions - 1) . ',}/';

		if (preg_match($pattern, $input)) {
			return false;
		}

		return true;
	}
}
