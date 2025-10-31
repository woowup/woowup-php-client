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
     * Initialize all cleansers
     *
     * Creates instances of all specialized cleanser classes
     */
    public function __construct()
    {
        $this->street = new StreetCleanser();
    }
}