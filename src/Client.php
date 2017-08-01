<?php
namespace WoowUp;

use WoowUp\Endpoints\Purchases;
use WoowUp\Endpoints\Users;

class Client {
	//const HOST = 'https://api.woowup.com';
	const HOST = 'http://admin.woowuplocal.com:8080';
	const VERSION = 'apiv3';

	protected $http;

	public $purchases;
	public $mailings;
	public $users;
	public $segments;
	public $products;

	function __construct($apikey)
	{
		$this->purchases = new Purchases(self::HOST.'/'.self::VERSION, $apikey);
		$this->users = new Users(self::HOST.'/'.self::VERSION, $apikey);
	}
}

?>