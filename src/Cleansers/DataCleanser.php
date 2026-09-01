<?php
namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Tld\TldCorrector;

/**
 * DataCleanser
 *
 * Facade pattern: Provides unified access to all specialized cleansers
 * Acts as a single point of access for data sanitization functionality
 */
class DataCleanser
{
    /**
     * @var StreetCleanser Street/address field cleanser
     */
    public $street;

    /**
     * @var TelephoneCleanser Telephone field cleanser
     */
    public $telephone;

    /**
     * @var TagsCleanser Tags management cleanser
     */
    public $tags;

    /**
     * @var EmailCleanser Email field cleanser
     */
    public $email;

    /**
     * @var GenderCleanser Gender field cleanser
     */
    public $gender;

    /**
     * @var BirthdateCleanser Birthdate field cleanser
     */
    public $birthdate;

    /**
     * @var CustomAttributeCleanser Custom attribute name cleanser
     */
    public $customAttributes;

    /** @var TldCorrector|null */
    private static $globalTldCorrector = null;

    /**
     * Configure a TldCorrector that will be used by all DataCleanser instances created afterwards.
     * Call once at application startup (e.g. from a feature-flag check in the Pimple provider).
     * Pass null to clear it — required by callers that process several accounts in the same
     * process without forking (e.g. a command run with no parallelism), so a corrector configured
     * for one account doesn't leak into the next one that has the feature flag off.
     * @return void
     */
    public static function configureGlobalTldCorrector(TldCorrector $corrector = null)
    {
        self::$globalTldCorrector = $corrector;
    }

    /**
     * Lets a caller outside the DataCleanser/EmailCleanser chain (e.g. a find-path identity
     * builder that can't share an instance with the write path) mirror whatever corrector is
     * currently configured, instead of reimplementing/caching its own and drifting out of sync.
     * @return TldCorrector|null
     */
    public static function getGlobalTldCorrector()
    {
        return self::$globalTldCorrector;
    }

    /** @return void */
    public function setTldCorrector(TldCorrector $corrector)
    {
        $this->email = new EmailCleanser($corrector);
    }

    /**
     * Initialize all cleansers
     *
     * Creates instances of all specialized cleanser classes
     */
    public function __construct()
    {
        $this->street = new StreetCleanser();
        $this->telephone = new TelephoneCleanser();
        $this->tags = new TagsCleanser();
        $this->email = new EmailCleanser(self::$globalTldCorrector);
        $this->gender = new GenderCleanser();
        $this->birthdate = new BirthdateCleanser();
        $this->customAttributes = new CustomAttributeCleanser();
    }
}