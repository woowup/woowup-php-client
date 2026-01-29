<?php
namespace WoowUp\Cleansers;

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
     * Initialize all cleansers
     *
     * Creates instances of all specialized cleanser classes
     */
    public function __construct()
    {
        $this->street = new StreetCleanser();
        $this->telephone = new TelephoneCleanser();
        $this->tags = new TagsCleanser();
        $this->email = new EmailCleanser();
        $this->gender = new GenderCleanser();
        $this->birthdate = new BirthdateCleanser();
    }
}