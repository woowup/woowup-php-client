<?php

namespace WoowUp\Support\Sanitizer;

class EmailSanitizer
{
    const BLACKLISTED_EMAIL_PATTERNS = [
        '*noreply@mercadolibre.com',
        '*mail.mercadolibre.com',
        "ct.vtex.com.br",
        'notiene@*'
    ];

    const WHITELISTED_EMAIL_PATTERNS = [
        '@gmail.com',
        '@hotmail',
        '@yahoo',
        '@outlook',
        '@live',
        '@icloud',
        '@msn',
        '@protonmail.com'
    ];

    const REPLACEMENT_EMAIL = 'noemail@noemail.com';

    const ENABLED_VALUE = "enabled";
    const DISABLED_VALUE = "disabled";
    const DISABLED_REASON_VALUE = "other";

    public static function sanitize(array $data): array
    {
        if (isset($data['customer']) && is_array($data['customer'])) {
            return self::sanitizePurchase($data);
        }

        return self::sanitizeCustomer($data);
    }

    public static function sanitizePurchase(array $purchase): array
    {
        if (isset($purchase['customer']) && !empty($purchase['customer']['email'])) {
            $purchase['customer'] = self::sanitizeCustomer($purchase['customer']);
            $purchase['email'] = $purchase['customer']['email'];
            return $purchase;
        }

        if (!empty($purchase['email'])) {
            $email = trim($purchase['email']);

            if (self::isBlacklistedEmail($email)) {
                $purchase['email'] = self::REPLACEMENT_EMAIL;
            } else {
                $validatedEmail = self::validateEmail($email);
                $purchase['email'] = $validatedEmail ?? $email;
            }
        }

        return $purchase;
    }

    public static function sanitizeCustomer(array $customer): array
    {
        if (empty($customer) || empty($customer['email'])) {
            return $customer;
        }

        $email = trim($customer['email']);

        if (self::isBlacklistedEmail($email)) {
            if (self::hasOnlyEmail($customer)) {
                $customer['email'] = $email;
                $customer['mailing_enabled'] = self::DISABLED_VALUE;
                $customer['mailing_disabled_reason'] = self::DISABLED_REASON_VALUE;
            } else {
                $customer['email'] = self::REPLACEMENT_EMAIL;
            }
            return $customer;
        }

        if (!self::isWhitelistedDomain($email)) {
            $customer = self::addReviewEmailTag($customer);
        }

        $validatedEmail = self::validateEmail($email);
        $customer['email'] = $validatedEmail ?? $email;

        return $customer;
    }

    public static function isBlacklistedEmail(string $email): bool
    {
        foreach (self::BLACKLISTED_EMAIL_PATTERNS as $pattern) {
            if (fnmatch($pattern, strtolower($email))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the email domain is whitelisted. If it is whitelisted, it returns true,
     * otherwise it returns false.
     *
     * @param string $email
     * @return bool
     */
    public static function isWhitelistedDomain(string $email): bool
    {
        foreach (self::WHITELISTED_EMAIL_PATTERNS as $pattern) {
            if (stripos($email, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function addReviewEmailTag(array $customer): array
    {
        if (array_key_exists('tags', $customer) && !empty($customer['tags'])) {
            $customer['tags'] .= ',review_email';
        } else {
            $customer['tags'] = 'review_email';
        }
        return $customer;
    }

    private static function hasOnlyEmail(array $customer): bool
    {
        return !empty($customer['email'])
            && empty($customer['telephone'])
            && empty($customer['document'])
            && empty($customer['service_uid']);
    }

    private static function validateEmail(string $email): ?string
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return mb_strtolower($email);
        }
        return null;
    }
}