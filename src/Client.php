<?php
namespace WoowUp;

use WoowUp\Endpoints\AbandonedCarts;
use Woowup\Endpoints\UserEvents;
use WoowUp\Endpoints\Products;
use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\Users;

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
    }
}
