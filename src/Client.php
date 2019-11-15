<?php
namespace WoowUp;

use WoowUp\Endpoints\AbandonedCarts;
use WoowUp\Endpoints\Blacklist;
use WoowUp\Endpoints\Branches;
use WoowUp\Endpoints\CustomAttributes;
use WoowUp\Endpoints\Events;
use WoowUp\Endpoints\Products;
use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\UserEvents;
use WoowUp\Endpoints\Users;
use WoowUp\Endpoints\Account;
use WoowUp\Endpoints\Multiusers;
use WoowUp\Endpoints\Stats;

class Client
{
    protected $http;

    /**
     * Purchases endpoint wrapper
     * @var WoowUp\Endpoints\Purchases
     */
    public $purchases;

    /**
     * Users endpoint wrapper
     * @var WoowUp\Endpoints\Users
     */
    public $users;

    /**
     * Products endpoint wrapper
     * @var WoowUp\Endpoints\Products
     */
    public $products;

    /**
     * Abandoned Cart endpoint wrapper
     * @var WoowUp\Endpoints\AbandonedCarts
     */
    public $abadonedCarts;

    /**
     * Events endpoint wrapper
     * @var WoowUp\Endpoints\Events
     */
    public $events;

    /**
     * UserEvents endpoint wrapper
     * @var WoowUp\Endpoints\UserEvents
     */
    public $userEvents;

    /**
     * Branches endpoint wrapper
     * @var WoowUp\Endpoints\Branches
     */
    public $branches;

    /**
     * CustomAttributes endpoint wrapper
     * @var WoowUp\Endpoints\CustomAttributes
     */
    public $cAttributes;

    /**
     * Account endpoint wrapper
     * @var WoowUp\Endpoints\Account
     */
    public $account;

    /**
     * Multi-identifier endpoint wrapper
     * @var WoowUp\Endpoints\Multiusers
     */
    public $multiusers;

    /**
     * Blacklist endpoint wrapper
     * @var WoowUp\Endpoints\Blacklist
     */
    public $blacklist;

    /**
     * Integration stats endpoint wrapper
     * @var  WoowUp\Endpoints\Stats
     */
    public $stats;

    /**
     * Client constructor
     * @param string $apikey Account's apikey
     * @param string $host   WoowUp API host
     * @param string $version   WoowUp API version
     */
    public function __construct($apikey, $host = 'https://api.woowup.com', $version = 'apiv3')
    {
        $url = $host . '/' . $version;

        $this->purchases      = new Purchases($url, $apikey);
        $this->users          = new Users($url, $apikey);
        $this->products       = new Products($url, $apikey);
        $this->abandonedCarts = new AbandonedCarts($url, $apikey);
        $this->events         = new Events($url, $apikey);
        $this->userEvents     = new UserEvents($url, $apikey);
        $this->branches       = new Branches($url, $apikey);
        $this->cAttributes    = new CustomAttributes($url, $apikey);
        $this->account        = new Account($url, $apikey);
        $this->multiusers     = new Multiusers($url, $apikey);
        $this->blacklist      = new Blacklist($url, $apikey);
        $this->stats          = new Stats($url, $apikey);
    }
}
