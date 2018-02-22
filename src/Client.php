<?php
namespace WoowUp;

use WoowUp\Endpoints\Products;
use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\Users;

class Client
{
    protected $http;

    public $purchases;
    public $mailings;
    public $users;
    public $segments;
    public $products;

    public function __construct($apikey, $host = 'https://api.woowup.com', $version = 'apiv3')
    {
        $url = $host . '/' . $version;

        $this->purchases = new Purchases($url, $apikey);
        $this->users     = new Users($url, $apikey);
        $this->products  = new Products($url, $apikey);
    }
}
