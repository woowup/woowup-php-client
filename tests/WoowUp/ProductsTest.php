<?php
namespace WoowUpTest\WoowUp;

use WoowUp\Client as WoowUp;

/**
 *
 */
class ProductsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateProduct()
    {
        $woowup = new WoowUp($_ENV['WOOWUP_API_KEY'], $_ENV['WOOWUP_API_HOST'], $_ENV['WOOWUP_API_VERSION']);

        $sku = md5(microtime());

        $r = $woowup->products->create([
            'sku'  => $sku,
            'name' => 'test product: '.$sku,
        ]);

        $this->assertEquals($r, true);
    }
}
