<?php

namespace WoowUp\Cleansers;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use WoowUp\Cleansers\Formatters\BirthdateFormatter;

/**
 * Cleanser for birthdate field sanitization.
 *
 * Handles multiple input types (DateTime, UTCDateTime, string) and delegates
 * string formatting to BirthdateFormatter. Returns null for invalid dates
 * to allow customer creation without birthdate.
 *
 * @package WoowUp\Cleansers
 */
class BirthdateCleanser
{
    /** @var string WoowUp standard date format */
    private const WOOWUP_FORMAT = 'Y-m-d';

    /** @var BirthdateFormatter */
    private $formatter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->formatter = new BirthdateFormatter();
    }

    /**
     * Sanitizes a birthdate value to WoowUp format (Y-m-d).
     *
     * Accepts DateTime objects, MongoDB UTCDateTime objects, or date strings
     * in various formats. Returns null for invalid or placeholder dates.
     *
     * @param mixed $birthdate The birthdate value to sanitize (DateTime|UTCDateTime|string|mixed)
     * @return string|null The formatted date (Y-m-d) or null if invalid
     */
    public function sanitize($birthdate): ?string
    {
        if ($birthdate instanceof DateTime) {
            return $this->formatter->format($birthdate->format(self::WOOWUP_FORMAT));
        }

        if ($birthdate instanceof UTCDateTime) {
            return $this->formatter->format($birthdate->toDateTime()->format(self::WOOWUP_FORMAT));
        }

        if (!is_string($birthdate)) {
            return null;
        }

        return $this->formatter->format($birthdate);
    }
}
