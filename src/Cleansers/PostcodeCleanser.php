<?php
namespace WoowUp\Cleansers;

class PostcodeCleanser
{
    const MAX_LENGTH = 16;

    /**
     * Sanitize postcode: truncate to 16 characters
     *
     * The API rejects a longer postcode with a 400 (maxLength 16), and on user creation that
     * rejection drops the customer and every purchase that needs it. Stores that type the
     * commune or a pickup point in the zip field ("PEDRO AGUIRRE CERDA") hit it routinely.
     *
     * @param string $postcode Postcode value to sanitize
     * @return string Sanitized postcode (max 16 chars)
     */
    public function sanitize($postcode)
    {
        return $this->truncate($postcode);
    }

    /**
     * Truncate postcode field to maximum allowed length
     * Considers JSON serialization length to handle special characters
     *
     * @param string $postcode
     * @return string
     */
    public function truncate($postcode)
    {
        if (!is_string($postcode)) {
            return '';
        }

        if ($this->isWithinLimit($postcode)) {
            return $postcode;
        }

        return $this->truncateToFit($postcode);
    }

    /**
     * Check if string is within JSON length limit
     *
     * @param string $postcode
     * @return bool
     */
    private function isWithinLimit($postcode)
    {
        return $this->getJsonLength($postcode) <= self::MAX_LENGTH;
    }

    /**
     * Get the length of the string when JSON-encoded, excluding quotes.
     *
     * @param string $postcode
     * @return int Length of the string in JSON bytes, excluding surrounding quotes
     */
    private function getJsonLength($postcode)
    {
        return strlen(json_encode($postcode)) - 2;
    }

    /**
     * Truncate the string to fit within the JSON length limit (binary search).
     *
     * @param string $postcode
     * @return string Truncated postcode that fits within MAX_LENGTH when JSON-encoded
     */
    private function truncateToFit($postcode)
    {
        $low = 0;
        $high = mb_strlen($postcode);

        while ($low < $high) {
            $mid = (int)(($low + $high + 1) / 2);
            $candidate = mb_substr($postcode, 0, $mid);

            if ($this->getJsonLength($candidate) <= self::MAX_LENGTH) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return rtrim(mb_substr($postcode, 0, $low));
    }
}
