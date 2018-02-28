<?php
namespace WoowUpTest\WoowUp;

use WoowUp\Client as WoowUp;

/**
 *
 */
class PurchasesTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatePurchase()
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

        $invoiceNumber = rand(999, 99999);
        $r = $woowup->purchases->create([
            "service_uid"     => $email,
            "points"          => 24,
            "invoice_number"  => "$invoiceNumber",
            "purchase_detail" => [
                [
                    "sku"          => "2907362",
                    "product_name" => "Heladera Patrick",
                    "quantity"     => 1,
                    "unit_price"   => 1999.00,
                    "variations"   => [
                        [
                            "name"  => "Talle",
                            "value" => "XL",
                        ],
                    ],
                ],
            ],
            "prices"          => [
                "cost"     => 123.00,
                "shipping" => 123.00,
                "gross"    => 123.00,
                "tax"      => 123.00,
                "discount" => 123.00,
                "total"    => 123.00,
            ],
            "branch_name"     => "Palermo I",
            "createtime"      => date('c'), // ISO8601
        ]);

        $this->assertEquals($r, true);
    }
}
