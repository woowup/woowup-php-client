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
use WoowUp\Endpoints\Banks;

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
    public $customAttributes;

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
     * Integration banks endpoint wrapper
     * @var  WoowUp\Endpoints\Banks
     */
    public $banks;
    /**
     * Client constructor
     * @param string $apikey Account's apikey
     * @param string $host   WoowUp API host
     * @param string $version   WoowUp API version
     */
    public function __construct($apikey, $host = 'https://api.woowup.com', $version = 'apiv3', \GuzzleHttp\ClientInterface $http = null)
    {
        $url = $host . '/' . $version;

        $this->purchases        = new Purchases($url, $apikey, $http);
        $this->users            = new Users($url, $apikey, $http);
        $this->products         = new Products($url, $apikey, $http);
        $this->abandonedCarts   = new AbandonedCarts($url, $apikey, $http);
        $this->events           = new Events($url, $apikey, $http);
        $this->userEvents       = new UserEvents($url, $apikey, $http);
        $this->branches         = new Branches($url, $apikey, $http);
        $this->account          = new Account($url, $apikey, $http);
        $this->multiusers       = new Multiusers($url, $apikey, $http);
        $this->blacklist        = new Blacklist($url, $apikey, $http);
        $this->stats            = new Stats($url, $apikey, $http);
        $this->banks            = new Banks($url, $apikey, $http);
        $this->customAttributes = new CustomAttributes($url, $apikey, $http);
    }
}
