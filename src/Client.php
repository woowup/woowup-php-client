<?php
namespace WoowUp;

use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\Users;
use WoowUp\Endpoints\Products;

class Client
{
    const HOST    = 'https://api.woowup.com';
    const VERSION = 'apiv3';

    protected $http;

    public $purchases;
    public $mailings;
    public $users;
    public $segments;
    public $products;

    public function __construct($apikey)
    {
        $this->purchases = new Purchases(self::HOST . '/' . self::VERSION, $apikey);
        $this->users     = new Users(self::HOST . '/' . self::VERSION, $apikey);
        $this->products  = new Products(self::HOST . '/' . self::VERSION, $apikey);
    }
}
