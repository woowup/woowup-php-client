<?php
namespace WoowUp;

use WoowUp\Endpoints\AbandonedCarts;
use WoowUp\Endpoints\Events;
use WoowUp\Endpoints\Products;
use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\UserEvents;
use WoowUp\Endpoints\Users;
use WoowUp\Endpoints\Account;

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
     * Account endpoint wrapper
     * @var WoowUp\Endpoints\Account
     */
    public $account;

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
        $this->account     = new Account($url, $apikey);
    }
}
