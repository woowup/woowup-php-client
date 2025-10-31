<?php
namespace WoowUp\Cleansers;

class StreetCleanser
{
    const MAX_LENGTH = 100;

    /**
     * Sanitize street: truncate to 100 characters
     *
     * Main method called by Endpoint for street sanitization
     *
     * @param string $street Street value to sanitize
     * @return string Sanitized street (max 100 chars)
     */
    public function sanitize($street)
    {
        return $this->truncate($street);
    }

    /**
     * Truncate street field to maximum allowed length
     * Considers JSON serialization length to handle special characters
     *
     * @param string $street
     * @return string
     */
    public function truncate($street)
    {
        if (!is_string($street)) {
            return '';
        }

        if ($this->isWithinLimit($street)) {
            return $street;
        }

        return $this->truncateToFit($street);
    }

    /**
     * Check if string is within JSON length limit
     *
     * @param string $street
     * @return bool
     */
    private function isWithinLimit($street)
    {
        return $this->getJsonLength($street) <= self::MAX_LENGTH;
    }

    /**
     * Get the length of the string when JSON-encoded, excluding quotes.
     * Some multibyte characters and special characters may occupy more
     * bytes in JSON, so simple character count is not enough.
     *
     * @param string $street
     * @return int Length of the string in JSON bytes, excluding surrounding quotes
     */
    private function getJsonLength($street)
    {
        return strlen(json_encode($street)) - 2;
    }

    /**
     * Efficiently truncate the string to fit within the JSON length limit.
     * Uses a binary search approach to minimize iterations.
     *
     * @param string $street
     * @return string Truncated street string that fits within MAX_LENGTH when JSON-encoded
     */
    private function truncateToFit($street)
    {
        $low = 0;
        $high = mb_strlen($street);

        while ($low < $high) {
            $mid = (int)(($low + $high + 1) / 2);
            $candidate = mb_substr($street, 0, $mid);

            if ($this->getJsonLength($candidate) <= self::MAX_LENGTH) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return mb_substr($street, 0, $low);
    }
}