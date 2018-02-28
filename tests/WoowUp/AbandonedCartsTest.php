<?php
namespace WoowUpTest\WoowUp;

use WoowUp\Client as WoowUp;

/**
 *
 */
class AbandonedCartsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateAbandonedCart()
    {
        $woowup = new WoowUp($_ENV['WOOWUP_API_KEY'], $_ENV['WOOWUP_API_HOST'], $_ENV['WOOWUP_API_VERSION']);

        $email = md5(microtime()) . '@email.com';

        $r = $woowup->users->create([
            'service_uid' => $email,
            'email'       => $email,
            'first_name'  => 'John',
            'last_name'   => 'Doe',
        ]);

        $this->assertEquals($r, true);

        $cart = [
            "service_uid" => $email,
            "total_price" => 50,
            "external_id" => "999",
            "source"      => 'api',
            "recovered"   => false,
            "recover_url" => 'https://www.myecommerce.com/abandoned-cart/999',
            "products"    => [
                [
                    "sku"        => "abc",
                    "quantity"   => 3,
                    "unit_price" => 10,
                ],
                [
                    "sku"        => "def",
                    "quantity"   => 1,
                    "unit_price" => 20,
                ],
            ],
            "createtime"  => date('c'),
        ];

        $r = $woowup->abandonedCarts->create($email, $cart);

        $this->assertEquals($r, true);
    }
}
