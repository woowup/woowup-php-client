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
     */
    public static function configureGlobalTldCorrector(TldCorrector $corrector): void
    {
        self::$globalTldCorrector = $corrector;
    }

    public function setTldCorrector(TldCorrector $corrector): void
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