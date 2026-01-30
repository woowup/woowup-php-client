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
class SequenceValidator implements ValidatorInterface
{
    private int $minDigitSequence;
    private int $minLetterSequence;
    private bool $digitsOnly;

    const KEYBOARD_ROWS = [
        'qwertyuiop',
        'asdfghjkl',
        'zxcvbnm'
    ];

    /**
     * Constructor.
     *
     * @param int $minDigitSequence Minimum digit sequence length to reject (default: 7)
     * @param int $minLetterSequence Minimum letter sequence length to reject (default: 6)
     * @param bool $digitsOnly Only validate if input is purely numeric (default: true)
     */
    public function __construct(int $minDigitSequence = 7, int $minLetterSequence = 6, bool $digitsOnly = true) {
        $this->minDigitSequence = $minDigitSequence;
        $this->minLetterSequence = $minLetterSequence;
        $this->digitsOnly = $digitsOnly;
    }

    /**
     * Validate that input doesn't contain long sequential patterns
     *
     * @param string $input The input to validate
     * @return bool True if valid, false if contains long sequences
     */
    public function validate(string $input): bool
    {

        if ($this->digitsOnly) {
            if (!ctype_digit($input)) {
                return true;
            }

            if ($this->hasAscendingDigitSequence($input) || $this->hasDescendingDigitSequence($input)) {
                return false;
            }
            return true;
        }

        $inputLower = mb_strtolower($input);

        if ($this->hasAscendingDigitSequence($inputLower) || $this->hasDescendingDigitSequence($inputLower)) {
            return false;
        }

        if ($this->hasAscendingSequence($inputLower) || $this->hasDescendingSequence($inputLower)){
            return false;
        }

        if ($this->hasKeyboardAscendingSequence($inputLower) || $this->hasKeyboardDescendingSequence($inputLower)) {
            return false;
        }

        return true;
    }

    private function hasAscendingDigitSequence(string $input): bool
    {
        return $this->hasSequentialDigitSequence($input, 1);
    }

    private function hasDescendingDigitSequence(string $input): bool
    {
        return $this->hasSequentialDigitSequence($input, -1);
    }

    private function hasSequentialDigitSequence(string $input, int $step): bool
    {
        $sequenceCount = 1;
        $length = strlen($input);

        for ($i = 1; $i < $length; $i++) {
            // Only count sequences of actual digits
            if (!ctype_digit($input[$i]) || !ctype_digit($input[$i - 1])) {
                $sequenceCount = 1;
                continue;
            }

            $current = (int) $input[$i];
            $previous = (int) $input[$i - 1];

            if ($current === ($previous + $step + 10) % 10) {
                $sequenceCount++;

                if ($sequenceCount >= $this->minDigitSequence) {
                    return true;
                }
            } else {
                $sequenceCount = 1;
            }
        }

        return false;
    }

    private function hasAscendingSequence(string $input): bool
    {
        return $this->checkSequence($input, 1);
    }

    private function hasDescendingSequence(string $input): bool
    {
        return $this->checkSequence($input, -1);
    }

    /**
     * Check for alphabetic sequences only (not digits).
     */
    private function checkSequence(string $input, int $direction): bool
    {
        $input = strtolower($input);
        $length = strlen($input);
        $consecutive = 1;

        for ($i = 1; $i < $length; $i++) {
            // Only check letter sequences, skip digits
            if (!ctype_alpha($input[$i]) || !ctype_alpha($input[$i - 1])) {
                $consecutive = 1;
                continue;
            }

            $current = ord($input[$i]);
            $previous = ord($input[$i - 1]);

            if ($current - $previous === $direction) {
                $consecutive++;
                if ($consecutive >= $this->minLetterSequence) {
                    return true;
                }
            } else {
                $consecutive = 1;
            }
        }

        return false;
    }

    private function hasKeyboardAscendingSequence(string $input): bool
    {
        return $this->hasKeyboardSequence($input);
    }

    private function hasKeyboardDescendingSequence(string $input): bool
    {
        return $this->hasKeyboardSequence(strrev($input));
    }

    private function hasKeyboardSequence(string $input): bool
    {
        $input = strtolower($input);
        $min = $this->minLetterSequence;

        foreach (self::KEYBOARD_ROWS as $row) {
            // Ascendente (qwerty)
            if ($this->containsSequence($input, $row, $min)) {
                return true;
            }

            // Descendente (ytrewq)
            $reversedRow = strrev($row);
            if ($this->containsSequence($input, $reversedRow, $min)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if input contains at least N consecutive characters from a reference string.
     *
     * @param string $input The input to check
     * @param string $reference The reference string to search for sequences
     * @param int $min Minimum sequence length
     * @return bool true if sequence found, false otherwise
     */
    private function containsSequence(string $input, string $reference, int $min): bool
    {
        $refLen = strlen($reference);

        for ($i = 0; $i <= $refLen - $min; $i++) {
            $slice = substr($reference, $i, $min);
            if (strpos($input, $slice) !== false) {
                return true;
            }
        }

        return false;
    }

}