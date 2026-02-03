<?php

namespace WoowUp\Cleansers\Formatters;

use DateTime;

/**
 * Formatter for birthdate strings.
 *
 * Parses date strings in multiple formats and normalizes them to WoowUp
 * standard format (Y-m-d). Validates month values and filters out
 * placeholder dates commonly used as null equivalents.
 *
 * @package WoowUp\Cleansers\Formatters
 */
class BirthdateFormatter
{
    /** @var string WoowUp standard date format */
    private const WOOWUP_FORMAT = 'Y-m-d';

    /** @var string Minimum allowed birthdate (WoowUp API requirement) */
    private const MIN_BIRTHDATE = '1900-01-01';

    /**
     * Common placeholder dates used as null equivalents in legacy systems.
     *
     * @var string[]
     */
    private const PLACEHOLDER_DATES = [
        '1900-01-01',
        '1901-01-01',
        '0001-01-01',
        '1970-01-01',
        '1969-12-31',
    ];

    /**
     * Supported date formats for parsing, ordered by specificity.
     *
     * @var string[]
     */
    private const SUPPORTED_FORMATS = [
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:s.v',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'Y-m-d H:i:s.u',
        'd/m/Y H:i:s',
        'd/m/Y',
        'd-m-Y G:i:s',
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd/m/Y H:i',
        'd-m-Y',
        'Y/m/d H:i:s',
        'Y/m/d',
        'Ymd His',
        'Ymd',
        'Y.m.d H:i:s',
        'd.m.Y H:i:s',
        'd.m.Y',
        'Y.m.d',
    ];

    /**
     * Formats a date string to WoowUp standard format (Y-m-d).
     *
     * Attempts to parse the input using supported formats, validates
     * month values (1-12), and filters placeholder dates.
     *
     * @param string $dateString The date string to format
     * @return string|null The formatted date (Y-m-d) or null if invalid
     */
    public function format(string $dateString): ?string
    {
        $dateString = trim($dateString);

        if ($dateString === '') {
            return null;
        }

        if ($this->isInvalidYear($dateString)) {
            return null;
        }

        if (!$this->hasValidMonth($dateString)) {
            return null;
        }

        foreach (self::SUPPORTED_FORMATS as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                $formatted = $date->format(self::WOOWUP_FORMAT);
                return $this->isValidDate($formatted) ? $formatted : null;
            }
        }

        $timestamp = @strtotime($dateString);
        if ($timestamp !== false && $timestamp > 0) {
            $formatted = date(self::WOOWUP_FORMAT, $timestamp);
            return $this->isValidDate($formatted) ? $formatted : null;
        }

        return null;
    }

    /**
     * Validates that a formatted date is acceptable.
     *
     * @param string $date The formatted date (Y-m-d) to validate
     * @return bool True if the date is valid
     */
    private function isValidDate(string $date): bool
    {
        if ($this->isPlaceholderDate($date)) {
            return false;
        }

        if ($this->isBeforeMinDate($date)) {
            return false;
        }

        if ($this->isAfterToday($date)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the date is before the minimum allowed birthdate.
     *
     * @param string $date The formatted date (Y-m-d) to check
     * @return bool True if the date is before minimum
     */
    private function isBeforeMinDate(string $date): bool
    {
        return $date < self::MIN_BIRTHDATE;
    }

    /**
     * Checks if the date is after today (future birthdate).
     *
     * @param string $date The formatted date (Y-m-d) to check
     * @return bool True if the date is in the future
     */
    private function isAfterToday(string $date): bool
    {
        return $date > date(self::WOOWUP_FORMAT);
    }

    /**
     * Checks if the date string contains an invalid year (0000).
     *
     * @param string $dateString The date string to check
     * @return bool True if the year is invalid
     */
    private function isInvalidYear(string $dateString): bool
    {
        return strpos($dateString, '0000') !== false;
    }

    /**
     * Checks if the date is a common placeholder date.
     *
     * @param string $date The formatted date (Y-m-d) to check
     * @return bool True if the date is a placeholder
     */
    private function isPlaceholderDate(string $date): bool
    {
        return in_array($date, self::PLACEHOLDER_DATES, true);
    }

    /**
     * Validates that the month value is between 1 and 12.
     *
     * Prevents PHP's DateTime from rolling over invalid months
     * (e.g., month 13 becoming January of next year).
     *
     * @param string $dateString The date string to validate
     * @return bool True if the month is valid or cannot be determined
     */
    private function hasValidMonth(string $dateString): bool
    {
        // Pattern for formats: Y-m-d, Y/m/d, Ymd (month in position 2)
        if (preg_match('/^(\d{4})[-\/]?(\d{2})[-\/]?\d{2}/', $dateString, $matches)) {
            $month = (int) $matches[2];
            return $month >= 1 && $month <= 12;
        }

        // Pattern for formats: d-m-Y, d/m/Y, d.m.Y (month in position 2)
        if (preg_match('/^\d{1,2}[-\/.](\d{1,2})[-\/.]\d{4}/', $dateString, $matches)) {
            $month = (int) $matches[1];
            return $month >= 1 && $month <= 12;
        }

        return true;
    }
}
