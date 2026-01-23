<?php

namespace WoowUp\Cleansers;

/**
 * Gender sanitizer
 *
 * WoowUp API only accepts: 'f', 'F', 'm', 'M' or empty
 * Pattern: ^[fFmM]{0,1}$
 */
class GenderCleanser
{
    const FEMALE_VALUES = ['f', 'femenino', 'female'];
    const MALE_VALUES = ['m', 'masculino', 'male'];

    /**
     * Sanitizes a gender value to WoowUp accepted format.
     *
     * @param mixed $gender The gender value to sanitize
     * @return string|null Returns 'f', 'm', or null if invalid
     */
    public function sanitize($gender): ?string
    {
        if ($gender === null || $gender === '') {
            return null;
        }

        $value = strtolower(trim((string)$gender));

        if (in_array($value, self::FEMALE_VALUES, true)) {
            return 'f';
        }

        if (in_array($value, self::MALE_VALUES, true)) {
            return 'm';
        }

        return null;
    }
}